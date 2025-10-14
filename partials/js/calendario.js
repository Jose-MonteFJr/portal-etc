document.addEventListener('DOMContentLoaded', function () {
    // --- ELEMENTOS DO DOM ---
    const monthYearHeader = document.getElementById('current-month-year');
    const daysContainer = document.getElementById('calendar-days');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');
    const selectedDateHeader = document.getElementById('selected-date-header');
    const eventsList = document.getElementById('events-list');
    const addEventBtn = document.getElementById('add-event-btn');

    // NOVO: Seleciona os novos botões
    const todayBtn = document.getElementById('today-btn');
    const gotoBtn = document.getElementById('goto-btn');
    const dateInput = document.getElementById('date-input');

    // --- ESTADO DO CALENDÁRIO ---
    let currentDate = new Date();
    let eventsArr = [];
    let selectedDate = null;

    const months = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

    // --- FUNÇÕES ---

    // Busca eventos do servidor via AJAX
    const fetchEvents = async () => {
        try {
            const response = await fetch('/portal-etc/calendario/get_eventos.php'); // Ajuste o caminho se necessário
            const data = await response.json();
            eventsArr = data;
            renderCalendar();
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

        // Dias do mês anterior
        for (let i = firstDayIndex; i > 0; i--) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-cell other-month';
            dayCell.textContent = prevLastDay.getDate() - i + 1;
            daysContainer.appendChild(dayCell);
        }

        // Dias do mês atual
        for (let i = 1; i <= lastDate; i++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-cell';
            dayCell.textContent = i;

            const today = new Date();
            if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                dayCell.classList.add('today');
            }

            if (selectedDate && i === selectedDate.getDate() && month === selectedDate.getMonth() && year === selectedDate.getFullYear()) {
                dayCell.classList.add('active');
            }

            // Verifica se o dia tem evento
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            if (eventsArr.some(event => event.data_evento === dateString)) {
                dayCell.classList.add('has-event');
            }

            dayCell.addEventListener('click', () => {
                selectedDate = new Date(year, month, i);
                document.querySelectorAll('.day-cell.active').forEach(d => d.classList.remove('active'));
                dayCell.classList.add('active');
                renderEventsForDate(selectedDate);
            });

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
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-outline-danger event-delete-btn';
                deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                deleteBtn.title = 'Excluir Lembrete';
                deleteBtn.onclick = () => deleteEvent(event.id_evento);

                // Adiciona o botão ao final do eventItem
                eventItem.querySelector('.d-flex').appendChild(deleteBtn);
            }

            eventsList.appendChild(eventItem);
        });
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

        const formData = new FormData();
        formData.append('titulo', title);
        formData.append('hora_inicio', startTime);
        formData.append('hora_fim', endTime);
        const dateString = `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`;
        formData.append('data_evento', dateString);
        formData.append('is_global', isGlobal);

        try {
            await fetch('/portal-etc/calendario/add_evento.php', { method: 'POST', body: formData });
            await fetchEvents();
            renderEventsForDate(selectedDate);
            // Limpa o formulário
            document.getElementById('event-title').value = '';
            document.getElementById('event-start-time').value = '';
            document.getElementById('event-end-time').value = '';
        } catch (error) {
            console.error("Erro ao adicionar evento:", error);
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
        renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
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

    // --- INICIALIZAÇÃO ---
    fetchEvents();
});