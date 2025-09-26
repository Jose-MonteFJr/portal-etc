// Seleciona elementos do DOM que serão manipulados
const calendar = document.querySelector(".calendar"),
    date = document.querySelector(".date"), // Mostra o mês e ano
    daysContainer = document.querySelector(".days"), // Contêiner dos dias
    prev = document.querySelector(".prev"), // Botão mês anterior
    next = document.querySelector(".next"), // Botão mês seguinte
    todayBtn = document.querySelector(".today-btn"), // Botão para ir para hoje
    gotoBtn = document.querySelector(".goto-btn"), // Botão para ir para data específica
    dateInput = document.querySelector(".date-input"), // Input para digitar data
    eventDay = document.querySelector(".event-day"), // Exibe o dia da semana do evento
    eventDate = document.querySelector(".event-date"), // Exibe a data do evento
    eventsContainer = document.querySelector(".events"), // Contêiner dos eventos
    addEventBtn = document.querySelector(".add-event"), // Botão para adicionar evento
    addEventWrapper = document.querySelector(".add-event-wrapper "), // Wrapper do formulário de evento
    addEventCloseBtn = document.querySelector(".close "), // Botão para fechar formulário de evento
    addEventTitle = document.querySelector(".event-name "), // Input do nome do evento
    addEventFrom = document.querySelector(".event-time-from "), // Input do horário inicial
    addEventTo = document.querySelector(".event-time-to "), // Input do horário final
    addEventSubmit = document.querySelector(".add-event-btn "); // Botão para submeter evento

let today = new Date(); // Data atual
let activeDay; // Dia ativo selecionado
let month = today.getMonth(); // Mês atual
let year = today.getFullYear(); // Ano atual

// Array com nomes dos meses
const months = [
    "Janeiro",
    "Fevereiro",
    "Março",
    "Abril",
    "Maio",
    "Junho",
    "Julho",
    "Agosto",
    "Setembro",
    "Outubro",
    "Novembro",
    "Dezembro",
];

// Array de eventos
const eventsArr = [];
getEvents(); // Carrega eventos do localStorage
console.log(eventsArr);

// Função para inicializar o calendário e renderizar os dias
function initCalendar() {
    const firstDay = new Date(year, month, 1); // Primeiro dia do mês
    const lastDay = new Date(year, month + 1, 0); // Último dia do mês
    const prevLastDay = new Date(year, month, 0); // Último dia do mês anterior
    const prevDays = prevLastDay.getDate(); // Quantidade de dias do mês anterior
    const lastDate = lastDay.getDate(); // Último dia do mês atual
    const day = firstDay.getDay(); // Dia da semana do primeiro dia
    const nextDays = 7 - lastDay.getDay() - 1; // Dias do próximo mês para completar a semana

    date.innerHTML = months[month] + " " + year; // Exibe mês e ano

    let days = "";

    // Adiciona dias do mês anterior
    for (let x = day; x > 0; x--) {
        days += `<div class="day prev-date">${prevDays - x + 1}</div>`;
    }

    // Adiciona dias do mês atual
    for (let i = 1; i <= lastDate; i++) {
        // Verifica se há evento nesse dia
        let event = false;
        eventsArr.forEach((eventObj) => {
            if (
                eventObj.day === i &&
                eventObj.month === month + 1 &&
                eventObj.year === year
            ) {
                event = true;
            }
        });
        // Verifica se é o dia atual
        if (
            i === new Date().getDate() &&
            year === new Date().getFullYear() &&
            month === new Date().getMonth()
        ) {
            activeDay = i;
            getActiveDay(i);
            updateEvents(i);
            if (event) {
                days += `<div class="day today active event">${i}</div>`;
            } else {
                days += `<div class="day today active">${i}</div>`;
            }
        } else {
            if (event) {
                days += `<div class="day event">${i}</div>`;
            } else {
                days += `<div class="day ">${i}</div>`;
            }
        }
    }

    // Adiciona dias do próximo mês
    for (let j = 1; j <= nextDays; j++) {
        days += `<div class="day next-date">${j}</div>`;
    }
    daysContainer.innerHTML = days; // Renderiza os dias no DOM
    addListner(); // Adiciona listeners aos dias
}

// Função para ir ao mês anterior
function prevMonth() {
    month--;
    if (month < 0) {
        month = 11;
        year--;
    }
    initCalendar();
}

// Função para ir ao próximo mês
function nextMonth() {
    month++;
    if (month > 11) {
        month = 0;
        year++;
    }
    initCalendar();
}

// Listeners dos botões de navegação
prev.addEventListener("click", prevMonth);
next.addEventListener("click", nextMonth);

initCalendar(); // Inicializa o calendário

// Função para adicionar classe 'active' ao dia selecionado
function addListner() {
    const days = document.querySelectorAll(".day");
    days.forEach((day) => {
        day.addEventListener("click", (e) => {
            getActiveDay(e.target.innerHTML);
            updateEvents(Number(e.target.innerHTML));
            activeDay = Number(e.target.innerHTML);
            // Remove classe 'active' de todos os dias
            days.forEach((day) => {
                day.classList.remove("active");
            });
            // Se clicou em dia do mês anterior, muda para mês anterior
            if (e.target.classList.contains("prev-date")) {
                prevMonth();
                // Adiciona 'active' ao dia após mudar o mês
                setTimeout(() => {
                    const days = document.querySelectorAll(".day");
                    days.forEach((day) => {
                        if (
                            !day.classList.contains("prev-date") &&
                            day.innerHTML === e.target.innerHTML
                        ) {
                            day.classList.add("active");
                        }
                    });
                }, 100);
            } else if (e.target.classList.contains("next-date")) {
                nextMonth();
                // Adiciona 'active' ao dia após mudar o mês
                setTimeout(() => {
                    const days = document.querySelectorAll(".day");
                    days.forEach((day) => {
                        if (
                            !day.classList.contains("next-date") &&
                            day.innerHTML === e.target.innerHTML
                        ) {
                            day.classList.add("active");
                        }
                    });
                }, 100);
            } else {
                e.target.classList.add("active");
            }
        });
    });
}

// Listener do botão "Hoje"
todayBtn.addEventListener("click", () => {
    today = new Date();
    month = today.getMonth();
    year = today.getFullYear();
    initCalendar();
});

// Mascara para input de data
dateInput.addEventListener("input", (e) => {
    dateInput.value = dateInput.value.replace(/[^0-9/]/g, "");
    if (dateInput.value.length === 2) {
        dateInput.value += "/";
    }
    if (dateInput.value.length > 7) {
        dateInput.value = dateInput.value.slice(0, 7);
    }
    if (e.inputType === "deleteContentBackward") {
        if (dateInput.value.length === 3) {
            dateInput.value = dateInput.value.slice(0, 2);
        }
    }
});

// Listener do botão "Ir para data"
gotoBtn.addEventListener("click", gotoDate);

// Função para ir para data específica
function gotoDate() {
    console.log("here");
    const dateArr = dateInput.value.split("/");
    if (dateArr.length === 2) {
        if (dateArr[0] > 0 && dateArr[0] < 13 && dateArr[1].length === 4) {
            month = dateArr[0] - 1;
            year = dateArr[1];
            initCalendar();
            return;
        }
    }
    alert("Data inválida");
}

// Função para exibir dia da semana e data do evento selecionado
function getActiveDay(date) {
    const day = new Date(year, month, date);
    const dayName = day.toString().split(" ")[0];
    eventDay.innerHTML = dayName;
    eventDate.innerHTML = date + " " + months[month] + " " + year;
}

// Função para atualizar eventos do dia ativo
function updateEvents(date) {
    let events = "";
    eventsArr.forEach((event) => {
        if (
            date === event.day &&
            month + 1 === event.month &&
            year === event.year
        ) {
            event.events.forEach((event) => {
                events += `<div class="event">
                        <div class="title">
                            <i class="fas fa-circle"></i>
                            <h3 class="event-title">${event.title}</h3>
                        </div>
                        <div class="event-time">
                            <span class="event-time">${event.time}</span>
                        </div>
                </div>`;
            });
        }
    });
    if (events === "") {
        events = `<div class="no-event">
                        <h3>Sem eventos</h3>
                </div>`;
    }
    eventsContainer.innerHTML = events;
    saveEvents();
}

// Listener para abrir formulário de evento
addEventBtn.addEventListener("click", () => {
    addEventWrapper.classList.toggle("active");
});

// Listener para fechar formulário de evento
addEventCloseBtn.addEventListener("click", () => {
    addEventWrapper.classList.remove("active");
});

// Fecha formulário se clicar fora dele
document.addEventListener("click", (e) => {
    if (e.target !== addEventBtn && !addEventWrapper.contains(e.target)) {
        addEventWrapper.classList.remove("active");
    }
});

// Limita o nome do evento a 60 caracteres
addEventTitle.addEventListener("input", (e) => {
    addEventTitle.value = addEventTitle.value.slice(0, 60);
});

// Função para exibir crédito do projeto
function defineProperty() {
    var osccred = document.createElement("div");
    osccred.innerHTML =
        "A Project By <a href='https://www.youtube.com/channel/UCiUtBDVaSmMGKxg1HYeK-BQ' target=_blank>Open Source Coding</a>";
    osccred.style.position = "absolute";
    osccred.style.bottom = "0";
    osccred.style.right = "0";
    osccred.style.fontSize = "10px";
    osccred.style.color = "#ccc";
    osccred.style.fontFamily = "sans-serif";
    osccred.style.padding = "5px";
    osccred.style.background = "#fff";
    osccred.style.borderTopLeftRadius = "5px";
    osccred.style.borderBottomRightRadius = "5px";
    osccred.style.boxShadow = "0 0 5px #ccc";
    document.body.appendChild(osccred);
}

defineProperty();

// Mascara para input de horário inicial
addEventFrom.addEventListener("input", (e) => {
    addEventFrom.value = addEventFrom.value.replace(/[^0-9:]/g, "");
    if (addEventFrom.value.length === 2) {
        addEventFrom.value += ":";
    }
    if (addEventFrom.value.length > 5) {
        addEventFrom.value = addEventFrom.value.slice(0, 5);
    }
});

// Mascara para input de horário final
addEventTo.addEventListener("input", (e) => {
    addEventTo.value = addEventTo.value.replace(/[^0-9:]/g, "");
    if (addEventTo.value.length === 2) {
        addEventTo.value += ":";
    }
    if (addEventTo.value.length > 5) {
        addEventTo.value = addEventTo.value.slice(0, 5);
    }
});

// Função para adicionar evento ao array de eventos
addEventSubmit.addEventListener("click", () => {
    const eventTitle = addEventTitle.value;
    const eventTimeFrom = addEventFrom.value;
    const eventTimeTo = addEventTo.value;
    if (eventTitle === "" || eventTimeFrom === "" || eventTimeTo === "") {
        alert("Preencha todos os campos");
        return;
    }

    // Verifica formato do horário (24h)
    const timeFromArr = eventTimeFrom.split(":");
    const timeToArr = eventTimeTo.split(":");
    if (
        timeFromArr.length !== 2 ||
        timeToArr.length !== 2 ||
        timeFromArr[0] > 23 ||
        timeFromArr[1] > 59 ||
        timeToArr[0] > 23 ||
        timeToArr[1] > 59
    ) {
        alert("Formato de horário inválido");
        return;
    }

    const timeFrom = convertTime(eventTimeFrom);
    const timeTo = convertTime(eventTimeTo);

    // Verifica se evento já existe
    let eventExist = false;
    eventsArr.forEach((event) => {
        if (
            event.day === activeDay &&
            event.month === month + 1 &&
            event.year === year
        ) {
            event.events.forEach((event) => {
                if (event.title === eventTitle) {
                    eventExist = true;
                }
            });
        }
    });
    if (eventExist) {
        alert("Evento já adicionado");
        return;
    }
    const newEvent = {
        title: eventTitle,
        time: timeFrom + " - " + timeTo,
    };
    console.log(newEvent);
    console.log(activeDay);
    let eventAdded = false;
    if (eventsArr.length > 0) {
        eventsArr.forEach((item) => {
            if (
                item.day === activeDay &&
                item.month === month + 1 &&
                item.year === year
            ) {
                item.events.push(newEvent);
                eventAdded = true;
            }
        });
    }

    if (!eventAdded) {
        eventsArr.push({
            day: activeDay,
            month: month + 1,
            year: year,
            events: [newEvent],
        });
    }

    console.log(eventsArr);
    addEventWrapper.classList.remove("active");
    addEventTitle.value = "";
    addEventFrom.value = "";
    addEventTo.value = "";
    updateEvents(activeDay);
    // Adiciona classe 'event' ao dia ativo se não tiver
    const activeDayEl = document.querySelector(".day.active");
    if (!activeDayEl.classList.contains("event")) {
        activeDayEl.classList.add("event");
    }
});

// Função para deletar evento ao clicar nele
eventsContainer.addEventListener("click", (e) => {
    if (e.target.classList.contains("event")) {
        if (confirm("Tem certeza que deseja excluir este evento?")) {
            const eventTitle = e.target.children[0].children[1].innerHTML;
            eventsArr.forEach((event) => {
                if (
                    event.day === activeDay &&
                    event.month === month + 1 &&
                    event.year === year
                ) {
                    event.events.forEach((item, index) => {
                        if (item.title === eventTitle) {
                            event.events.splice(index, 1);
                        }
                    });
                    // Se não houver mais eventos no dia, remove o dia do array
                    if (event.events.length === 0) {
                        eventsArr.splice(eventsArr.indexOf(event), 1);
                        // Remove classe 'event' do dia
                        const activeDayEl = document.querySelector(".day.active");
                        if (activeDayEl.classList.contains("event")) {
                            activeDayEl.classList.remove("event");
                        }
                    }
                }
            });
            updateEvents(activeDay);
        }
    }
});

// Função para salvar eventos no localStorage
function saveEvents() {
    localStorage.setItem("events", JSON.stringify(eventsArr));
}

// Função para carregar eventos do localStorage
function getEvents() {
    // Verifica se há eventos salvos no localStorage
    if (localStorage.getItem("events") === null) {
        return;
    }
    eventsArr.push(...JSON.parse(localStorage.getItem("events")));
}

// Função para converter horário para formato 12h (AM/PM)
function convertTime(time) {
    let timeArr = time.split(":");
    let timeHour = timeArr[0];
    let timeMin = timeArr[1];
    let timeFormat = timeHour >= 12 ? "PM" : "AM";
    timeHour = timeHour % 12 || 12;
    time = timeHour + ":" + timeMin + " " + timeFormat;
    return time;
}
