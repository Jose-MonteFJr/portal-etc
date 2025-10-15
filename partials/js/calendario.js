document.addEventListener('DOMContentLoaded', function () {
    // --- ELEMENTOS DO DOM ---
    const monthYearHeader = document.getElementById('current-month-year');
    const daysContainer = document.getElementById('calendar-days');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');
    const selectedDateHeader = document.getElementById('selected-date-header');
    const eventsList = document.getElementById('events-list');
    const addEventForm = document.getElementById('add-event-form');
    const addEventBtn = document.getElementById('add-event-btn');
    const eventIdInput = document.getElementById('event-id');
    const addEventFormTitle = addEventForm.previousElementSibling.querySelector('h6');
    const todayBtn = document.getElementById('today-btn');
    const gotoBtn = document.getElementById('goto-btn');
    const dateInput = document.getElementById('date-input');
    const prepareAddBtn = document.getElementById('prepare-add-btn');

    // --- ESTADO DO CALENDÁRIO ---
    let currentDate = new Date();
    let eventsArr = [];
    let selectedDate = new Date();

    const months = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

    // --- FUNÇÕES ---

    // Busca eventos do servidor via AJAX
    const fetchEvents = async () => {
        try {
            const response = await fetch('/portal-etc/calendario/get_eventos.php'); // Ajuste o caminho
            const data = await response.json();
            eventsArr = data.map(event => ({
                id: event.id_evento,
                id_usuario_criador: event.id_usuario_criador,
                titulo: event.titulo,
                hora_inicio: event.hora_inicio,
                hora_fim: event.hora_fim,
                data_evento: event.data_evento,
                tipo: event.tipo
            }));
            renderCalendar();
            renderEventsForDate(selectedDate); // Garante que a lista de eventos seja renderizada após a busca
        } catch (error) {
            console.error("Erro ao buscar eventos:", error);
        }
    };

    // Renderiza o calendário (dias, eventos, etc.)
    const renderCalendar = () => {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthYearHeader.textContent = `${months[month]} ${year}`;
        daysContainer.innerHTML = '';

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const prevLastDay = new Date(year, month, 0);

        const firstDayIndex = firstDay.getDay();
        const lastDate = lastDay.getDate();
        const prevDays = prevLastDay.getDate();

        // Dias do mês anterior
        for (let i = firstDayIndex; i > 0; i--) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-cell other-month';
            dayCell.textContent = prevDays - i + 1;
            daysContainer.appendChild(dayCell);
        }

        // Dias do mês atual
        for (let i = 1; i <= lastDate; i++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-cell';

            const dayNumber = document.createElement('span');
            dayNumber.textContent = i;
            dayCell.appendChild(dayNumber);

            const today = new Date();
            if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                dayCell.classList.add('today');
            }
            if (selectedDate && i === selectedDate.getDate() && month === selectedDate.getMonth() && year === selectedDate.getFullYear()) {
                dayCell.classList.add('active');
            }

            // --- LÓGICA DE MARCAÇÃO ATUALIZADA ---
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const dayEvents = eventsArr.filter(event => event.data_evento === dateString);

            if (dayEvents.length > 0) {
                // Se QUALQUER evento do dia for 'global', a marcação será global.
                if (dayEvents.some(event => event.tipo === 'global')) {
                    dayCell.classList.add('has-global-event');
                } else {
                    // Senão, se houver apenas eventos pessoais, a marcação será pessoal.
                    dayCell.classList.add('has-personal-event');
                }
            }
            // --- FIM DA LÓGICA DE MARCAÇÃO ---

            dayCell.addEventListener('click', () => {
                selectedDate = new Date(year, month, i);
                document.querySelectorAll('.day-cell.active').forEach(d => d.classList.remove('active'));
                dayCell.classList.add('active');
                renderEventsForDate(selectedDate);
            });

            daysContainer.appendChild(dayCell);
        }

        // Código para os dias do próximo mês...
        const nextDaysCount = 42 - daysContainer.children.length;
        for (let j = 1; j <= nextDaysCount; j++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-cell other-month';
            dayCell.textContent = j;
            daysContainer.appendChild(dayCell);
        }
    };

    // Renderiza a lista de eventos para o dia selecionado
    // SUBSTITUA A FUNÇÃO INTEIRA NO SEU calendario.js
    const renderEventsForDate = (date) => {
        selectedDateHeader.textContent = date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long' });
        eventsList.innerHTML = '';

        const dateString = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        const dayEvents = eventsArr.filter(event => event.data_evento === dateString);

        if (dayEvents.length === 0) {
            eventsList.innerHTML = `
            <div class="list-group-item text-center text-muted no-event-placeholder">
                <i class="bi bi-calendar-x fs-1"></i>
                <p class="mb-0 mt-2">Nenhum lembrete para este dia.</p>
            </div>
        `;
            return;
        }

        dayEvents.forEach(event => {
            const eventItem = document.createElement('div');
            const isGlobal = event.tipo === 'global';

            // Define o ícone e a cor com base no tipo de evento
            const iconClass = isGlobal ? 'bi-megaphone-fill' : 'bi-person-circle';
            const borderColorClass = isGlobal ? 'event-global' : 'event-pessoal';

            eventItem.className = `list-group-item event-item ${borderColorClass}`;

            eventItem.innerHTML = `
            <div class="d-flex align-items-start gap-3">
                <div class="event-item-icon">
                    <i class="bi ${iconClass}"></i>
                </div>
                <div class="event-item-details">
                    <strong class="d-block event-title">${htmlspecialchars(event.titulo)}</strong>
                    <small class="text-muted">${event.hora_inicio} - ${event.hora_fim}</small>
                </div>
            </div>
        `;

            // Adiciona o botão de deletar se o usuário for o criador
            if (event.id_usuario_criador === LOGGED_IN_USER_ID) {
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'event-actions mt-2'; // Um container para os botões

                // --- Botão Editar ---
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-secondary me-2';
                editBtn.innerHTML = '<i class="bi bi-pencil"></i> Editar';
                // A MÁGICA: Ao clicar, chama a função para iniciar a edição
                editBtn.onclick = () => startEditEvent(event);

                // --- Botão Excluir ---
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-outline-danger';
                deleteBtn.innerHTML = '<i class="bi bi-trash"></i> Excluir';
                // A MÁGICA: Ao clicar, chama a função para deletar
                deleteBtn.onclick = () => deleteEvent(event.id);

                actionsDiv.appendChild(editBtn);
                actionsDiv.appendChild(deleteBtn);
                eventItem.appendChild(actionsDiv); // Adiciona os botões ao item do lembrete
            }

            eventsList.appendChild(eventItem);
        });
    };

    const prepareFormForAdd = () => {
        eventIdInput.value = ''; // O mais importante: limpa o ID do evento
        addEventBtn.textContent = 'Adicionar Lembrete';

        // Garante que o título do card de lembretes seja resetado
        if (selectedDate) {
            selectedDateHeader.textContent = selectedDate.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long' });
        }

        // Limpa os campos
        document.getElementById('event-title').value = '';
        document.getElementById('event-start-time').value = '';
        document.getElementById('event-end-time').value = '';
        const globalCheck = document.getElementById('event-global-check');
        if (globalCheck) {
            globalCheck.checked = false;
        }
    };

    // NOVA FUNÇÃO para preparar o formulário para edição
    const startEditEvent = (event) => {
        // Pega a instância do componente Collapse do Bootstrap
        const formCollapseInstance = bootstrap.Collapse.getOrCreateInstance(addEventForm);
        const isFormOpen = addEventForm.classList.contains('show');
        const isEditingSameEvent = eventIdInput.value == event.id;

        // --- NOVA LÓGICA DE TOGGLE ---
        // Se o formulário já estiver aberto E for para o mesmo evento, fecha e reseta.
        if (isFormOpen && isEditingSameEvent) {
            formCollapseInstance.hide();
            prepareFormForAdd(); // Usa a função de reset que já temos
            return; // Encerra a função aqui
        }

        // --- LÓGICA ANTIGA (se o formulário estiver fechado ou for para outro evento) ---
        // Preenche o campo oculto com o ID do evento
        eventIdInput.value = event.id;

        // Preenche os campos visíveis do formulário com os dados do evento
        document.getElementById('event-title').value = event.titulo;
        document.getElementById('event-start-time').value = event.hora_inicio;
        document.getElementById('event-end-time').value = event.hora_fim;

        const globalCheck = document.getElementById('event-global-check');
        if (globalCheck) {
            globalCheck.checked = event.tipo === 'global';
        }

        // Muda os textos para o modo de edição
        addEventBtn.textContent = 'Salvar Alterações';

        // Abre o formulário
        formCollapseInstance.show();
    };

    // Deleta um evento
    const deleteEvent = async (eventId) => {
        if (!confirm("Tem certeza que deseja excluir este lembrete?")) return;

        const formData = new FormData();
        formData.append('id_evento', eventId);

        try {
            await fetch('/portal-etc/calendario/delete_evento.php', { method: 'POST', body: formData });
            await fetchEvents(); // Recarrega todos os eventos
            renderEventsForDate(selectedDate); // Renderiza a lista do dia atual
        } catch (error) {
            console.error("Erro ao deletar evento:", error);
        }
    };

    // Adiciona um novo evento
    addEventBtn.addEventListener('click', async () => {
        if (!selectedDate) {
            alert("Por favor, selecione um dia no calendário primeiro.");
            return;
        }

        const title = document.getElementById('event-title').value;
        const startTime = document.getElementById('event-start-time').value;
        const endTime = document.getElementById('event-end-time').value;
        const isGlobal = document.getElementById('event-global-check') ? document.getElementById('event-global-check').checked : false;

        if (!title || !startTime || !endTime) {
            alert("Preencha todos os campos do lembrete.");
            return;
        }

        // --- LÓGICA DE DECISÃO ---
        const eventId = eventIdInput.value; // Pega o ID do campo oculto
        const isEditing = eventId && eventId > 0; // Se tiver um ID, estamos editando

        // Decide para qual script PHP enviar os dados
        const url = isEditing ? '/portal-etc/calendario/edit_evento.php' : '/portal-etc/calendario/add_evento.php';

        const formData = new FormData();
        formData.append('titulo', title);
        formData.append('hora_inicio', startTime);
        formData.append('hora_fim', endTime);

        if (isEditing) {
            formData.append('id_evento', eventId);
            formData.append('is_global', isGlobal);
        } else {
            const dateString = `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`;
            formData.append('data_evento', dateString);
            formData.append('is_global', isGlobal);
        }

        try {
            await fetch(url, { method: 'POST', body: formData });

            prepareFormForAdd(); // Reseta o formulário
            bootstrap.Collapse.getOrCreateInstance(addEventForm).hide();

            await fetchEvents();

        } catch (error) {
            console.error("Erro ao salvar evento:", error);
        }
    });

    // --- NOVO: LÓGICA PARA OS NOVOS BOTÕES ---
    todayBtn.addEventListener('click', () => {
        currentDate = new Date(); // Navega a visão do calendário para o mês/ano atual
        selectedDate = new Date(); // Define o dia selecionado como hoje

        renderCalendar(); // Redesenha o calendário (que agora vai destacar o dia de hoje)
        renderEventsForDate(selectedDate); // Atualiza a lista de lembretes para mostrar os de hoje
    });

    gotoBtn.addEventListener('click', () => {
        const dateArr = dateInput.value.split("/");
        if (dateArr.length === 2) {
            const month = parseInt(dateArr[0], 10);
            const year = parseInt(dateArr[1], 10);
            if (month >= 1 && month <= 12 && String(year).length === 4) {
                currentDate = new Date(year, month - 1, 1);
                selectedDate = new Date(year, month - 1, 1);
                fetchEvents();
                return;
            }
        }
        alert("Data inválida. Use o formato MM/AAAA.");
    });

    dateInput.addEventListener("input", (e) => {
        dateInput.value = dateInput.value.replace(/[^0-9/]/g, "");
        if (dateInput.value.length === 2 && e.inputType !== 'deleteContentBackward') {
            dateInput.value += "/";
        }
        if (dateInput.value.length > 7) {
            dateInput.value = dateInput.value.slice(0, 7);
        }
    });

    // --- EVENT LISTENERS DE NAVEGAÇÃO ---
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        fetchEvents();
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        fetchEvents();
    });

    // Função para evitar injeção de HTML
    const htmlspecialchars = (str) => {
        return str.replace(/[&<>"']/g, (match) => {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }

    if (prepareAddBtn) {
        prepareAddBtn.addEventListener('click', prepareFormForAdd);
    }

    // --- INICIALIZAÇÃO ---
    fetchEvents();
});