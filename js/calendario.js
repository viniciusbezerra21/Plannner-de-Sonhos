const API_URL = "api_eventos.php";

const btnJanelaModal = document.getElementById("criar-novo-evento");
const janelaModal = document.getElementById("janela-modal");
const btnCancelar = document.getElementById("btnCancelar");
const btnSalvar = document.getElementById("btnSalvarModal");

const janelaModalDay = document.getElementById("janela-modal-day");
const btnCancelarDay = janelaModalDay.querySelector("#btnCancelar");
const btnSalvarDay = janelaModalDay.querySelector("#btnSalvar");

const inputNomeDay = janelaModalDay.querySelector("#nome");
const inputHoraDay = janelaModalDay.querySelector("#hora");
const inputLocalDay = janelaModalDay.querySelector("#local");

const headerRow = document.querySelector(".calendar-header-row");
const diasContainer = document.querySelector(".calendar-days");
const monthLabel = document.querySelector(".calendar-month");
const btnPrev = document.querySelector(".prev");
const btnNext = document.querySelector(".next");

const diasSemana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
const meses = [
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

let dataAtual = new Date();
let diaSelecionado = null;

let eventos = {};

let displayedEvents = [];

async function carregarEventos() {
  try {
    const response = await fetch(`${API_URL}?action=eventos`);
    if (!response.ok) {
      throw new Error("Erro ao carregar eventos");
    }

    const dadosEventos = await response.json();
    eventos = {};

    if (typeof dadosEventos === "object" && !Array.isArray(dadosEventos)) {
      eventos = dadosEventos;
    } else if (Array.isArray(dadosEventos)) {
      dadosEventos.forEach((evento) => {
        const dataStr = evento.data;
        if (!eventos[dataStr]) {
          eventos[dataStr] = [];
        }
        eventos[dataStr].push(evento);
      });
    }

    renderCalendar(dataAtual);
    renderUpcomingEvents();
  } catch (error) {
    console.error("Erro ao carregar eventos:", error);
    alert("Erro ao carregar eventos. Verifique sua conexão.");
  }
}

async function salvarEvento(dadosEvento) {
  try {
    const response = await fetch(`${API_URL}?action=criar_evento`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(dadosEvento),
    });

    const resultado = await response.json();

    if (!response.ok) {
      throw new Error(resultado.error || "Erro ao salvar evento");
    }

    return resultado;
  } catch (error) {
    console.error("Erro ao salvar evento:", error);
    throw error;
  }
}

async function atualizarEvento(id, dadosEvento) {
  try {
    const response = await fetch(`${API_URL}?action=atualizar_evento`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id, ...dadosEvento }),
    });

    const resultado = await response.json();

    if (!response.ok) {
      throw new Error(resultado.error || "Erro ao atualizar evento");
    }

    return resultado;
  } catch (error) {
    console.error("Erro ao atualizar evento:", error);
    throw error;
  }
}

async function deletarEvento(id) {
  try {
    const response = await fetch(`${API_URL}?action=deletar_evento`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id }),
    });

    const resultado = await response.json();

    if (!response.ok) {
      throw new Error(resultado.error || "Erro ao deletar evento");
    }

    return resultado;
  } catch (error) {
    console.error("Erro ao deletar evento:", error);
    throw error;
  }
}

const getEventTypeClass = (type) => {
  if (!type) return "default";
  const normalizedType = type.toLowerCase();
  switch (normalizedType) {
    case "reunião":
      return "meeting";
    case "degustação":
      return "tasting";
    case "prova":
      return "fitting";
    default:
      return normalizedType;
  }
};

function renderUpcomingEvents() {
  const upcomingEventsList = document.querySelector(".events-list");
  if (!upcomingEventsList) return;

  const now = new Date();
  let allUpcomingEvents = [];
  for (const dateStr in eventos) {
    if (eventos[dateStr] && Array.isArray(eventos[dateStr])) {
      eventos[dateStr].forEach((event) => {
        // Cria data do evento
        const eventDate = new Date(dateStr);
        if (event.hora) {
          const [hora, minuto] = event.hora.split(":");
          eventDate.setHours(parseInt(hora), parseInt(minuto));
        }

        const agora = new Date();
        if (event.hora) {
          if (eventDate >= agora) {
            allUpcomingEvents.push({ ...event, date: dateStr });
          }
        } else {
          const hoje = new Date();
          hoje.setHours(0, 0, 0, 0);
          const dataEvento = new Date(dateStr);
          dataEvento.setHours(0, 0, 0, 0);

          if (dataEvento >= hoje) {
            allUpcomingEvents.push({ ...event, date: dateStr });
          }
        }
      });
    }
  }

  allUpcomingEvents.sort((a, b) => {
    const dateTimeA = new Date(`${a.date}T${a.hora || "00:00"}`);
    const dateTimeB = new Date(`${b.date}T${b.hora || "00:00"}`);
    return dateTimeA - dateTimeB;
  });

  displayedEvents = allUpcomingEvents.slice(0, 4);
  upcomingEventsList.innerHTML = "";
  displayedEvents.forEach((event) => {
    const [year, month, day] = event.date.split("-");
    const formattedDate = `${day}/${month}/${year}`;
    const eventTypeClass = getEventTypeClass(event.tipo);

    const eventHTML = `
            <div class="event-item high-priority" data-event-id="${event.id || ""
      }">
                <div class="event-content">
                    <h4 class="event-title">${event.nome}</h4>
                    <div class="event-details">
                        <div class="event-detail">
                            <svg class="calendar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            ${formattedDate}
                        </div>
                        <div class="event-detail">
                            <svg class="clock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12,6 12,12 16,14" />
                            </svg>
                            ${event.hora || "Dia todo"}
                        </div>
                    </div>
                    <div class="event-location">
                        <svg class="map-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        ${event.local || "Não definido"}
                    </div>
                </div>
                <div class="event-actions">
                    <span class="event-type ${eventTypeClass}">${event.tipo || "Evento"
      }</span>
                    ${event.id
        ? `<button class="btn-delete-event" onclick="handleDeleteEvent(${event.id})" title="Excluir evento">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>`
        : ""
      }
                </div>
            </div>
        `;
    upcomingEventsList.insertAdjacentHTML("beforeend", eventHTML);
  });
}

diasSemana.forEach((dia) => {
  const div = document.createElement("div");
  div.classList.add("calendar-day-header");
  div.textContent = dia;
  headerRow.appendChild(div);
});

function renderCalendar(data) {
  diasContainer.innerHTML = "";
  const ano = data.getFullYear();
  const mes = data.getMonth();

  monthLabel.textContent = `${meses[mes]} ${ano}`;

  const totalDias = new Date(ano, mes + 1, 0).getDate();
  const primeiroDiaSemana = new Date(ano, mes, 1).getDay();
  const totalDiasMesAnterior = new Date(ano, mes, 0).getDate();

  for (let i = primeiroDiaSemana - 1; i >= 0; i--) {
    const div = document.createElement("div");
    div.classList.add("calendar-day", "other-month");
    div.textContent = totalDiasMesAnterior - i;
    diasContainer.appendChild(div);
  }

  for (let dia = 1; dia <= totalDias; dia++) {
    const div = document.createElement("div");
    div.classList.add("calendar-day");
    div.textContent = dia;

    const dataStr = `${ano}-${String(mes + 1).padStart(2, "0")}-${String(
      dia
    ).padStart(2, "0")}`;
    div.dataset.date = dataStr;

    if (eventos[dataStr] && eventos[dataStr].length > 0) {
      div.classList.add("has-event");
      const dot = document.createElement("span");
      dot.className = "event-dot";
      div.appendChild(dot);
    }

    diasContainer.appendChild(div);
  }

  const diasPreenchidos = diasContainer.children.length;
  const resto = diasPreenchidos % 7;
  if (resto !== 0) {
    const qtdDiasProxMes = 7 - resto;
    for (let i = 1; i <= qtdDiasProxMes; i++) {
      const div = document.createElement("div");
      div.classList.add("calendar-day", "other-month");
      div.textContent = i;
      diasContainer.appendChild(div);
    }
  }
}

btnPrev.addEventListener("click", () => {
  dataAtual.setMonth(dataAtual.getMonth() - 1);
  renderCalendar(dataAtual);
});

btnNext.addEventListener("click", () => {
  dataAtual.setMonth(dataAtual.getMonth() + 1);
  renderCalendar(dataAtual);
});

const janelaModalView = document.getElementById("janela-modal-view");
const listaEventos = document.getElementById("listaEventos");
const btnFecharView = document.getElementById("btnFecharView");

diasContainer.addEventListener("click", (e) => {
  const alvo = e.target.closest(".calendar-day");
  if (!alvo || alvo.classList.contains("other-month")) return;

  diaSelecionado = alvo;
  const dataStr = alvo.dataset.date;

  if (eventos[dataStr] && eventos[dataStr].length > 0) {
    listaEventos.innerHTML = "";
    eventos[dataStr].forEach((ev) => {
      const div = document.createElement("div");
      div.className = "evento-item";
      div.innerHTML = `
        <div class="evento-content">
          <p><strong>${ev.nome}</strong></p>
          <p>Hora: ${ev.hora || "—"}</p>
          <p>Local: ${ev.local || "—"}</p>
          <p>Tipo: ${ev.tipo || "—"}</p>
        </div>
        ${ev.id
          ? `<button class="btn-delete-event-modal" onclick="handleDeleteEvent(${ev.id})" title="Excluir evento">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>`
          : ""
        }
        <hr>
      `;
      listaEventos.appendChild(div);
    });
    janelaModalView.style.display = "flex";
  } else {
    janelaModalDay.style.display = "flex";
  }
});

btnSalvarDay.addEventListener("click", async (e) => {
  e.preventDefault();
  if (!diaSelecionado) return;

  const dataStr = diaSelecionado.dataset.date;
  const nome = inputNomeDay.value.trim();
  const hora = inputHoraDay.value;
  const local = inputLocalDay.value.trim();

  if (!nome) {
    alert("Nome do evento é obrigatório");
    return;
  }

  try {
    const dadosEvento = {
      nome,
      data: dataStr,
      hora,
      local,
      tipo: "Evento",
    };

    const resultado = await salvarEvento(dadosEvento);

    if (resultado.id) {
      const novoEvento = { ...dadosEvento, id: resultado.id };
      if (!eventos[dataStr]) eventos[dataStr] = [];
      eventos[dataStr].push(novoEvento);
    }

    await carregarEventos();

    inputNomeDay.value = "";
    inputHoraDay.value = "";
    inputLocalDay.value = "";

    janelaModalDay.style.display = "none";

    console.log("Evento salvo com sucesso!");
  } catch (error) {
    alert("Erro ao salvar evento: " + error.message);
  }
});

btnCancelarDay.addEventListener("click", (e) => {
  e.preventDefault();
  janelaModalDay.style.display = "none";
});

btnCancelar.addEventListener("click", (e) => {
  e.preventDefault();
  janelaModal.style.display = "none";
});

btnJanelaModal.addEventListener("click", () => {
  janelaModal.style.display = "flex";
});

btnFecharView.addEventListener("click", () => {
  janelaModalView.style.display = "none";
});

window.addEventListener("click", (e) => {
  if (e.target === janelaModal) janelaModal.style.display = "none";
  if (e.target === janelaModalDay) janelaModalDay.style.display = "none";
  if (e.target === janelaModalView) janelaModalView.style.display = "none";
});

btnSalvar.addEventListener("click", async (e) => {
  e.preventDefault();

  const dataStr = document.getElementById("data").value;
  const nome = document.getElementById("nome").value.trim();
  const hora = document.getElementById("hora").value;
  const local = document.getElementById("local").value.trim();

  if (!dataStr) {
    alert("Selecione uma data.");
    return;
  }

  if (!nome) {
    alert("Digite um nome para o evento.");
    return;
  }

  try {
    const dadosEvento = {
      nome,
      data: dataStr,
      hora,
      local,
      tipo: "Evento",
    };

    const resultado = await salvarEvento(dadosEvento);

    if (resultado.id) {
      const novoEvento = { ...dadosEvento, id: resultado.id };
      if (!eventos[dataStr]) eventos[dataStr] = [];
      eventos[dataStr].push(novoEvento);
    }

    await carregarEventos();

    document.getElementById("nome").value = "";
    document.getElementById("hora").value = "";
    document.getElementById("local").value = "";
    document.getElementById("data").value = "";

    janelaModal.style.display = "none";

    console.log("Evento salvo com sucesso!");
  } catch (error) {
    alert("Erro ao salvar evento: " + error.message);
  }
});

async function handleDeleteEvent(eventId) {
  if (!confirm("Tem certeza que deseja excluir este evento?")) {
    return;
  }

  try {
    await deletarEvento(eventId);
    await carregarEventos();
    janelaModalView.style.display = "none";
    console.log("Evento deletado com sucesso!");
  } catch (error) {
    alert("Erro ao deletar evento: " + error.message);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  carregarEventos();

  renderCalendar(dataAtual);
});
