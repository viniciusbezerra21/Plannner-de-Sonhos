const tituloMes = document.getElementById("calendar-month");
const btnProximo = document.getElementById("next-month-btn");
const btnAnterior = document.getElementById("prev-month-btn");

const janelaModalDay = document.getElementById("janela-modal-day");
const createEventModal = document.getElementById("janela-modal");
const janelaModalView = document.getElementById("janela-modal-view");

const dataAtual = new Date();
let dataSelecionada = null;

function atualizarTituloMes() {
  const mes = dataAtual.toLocaleString("default", { month: "long" });
  const ano = dataAtual.getFullYear();
  tituloMes.textContent = `${mes} ${ano}`;
}

btnProximo.addEventListener("click", () => {
  dataAtual.setMonth(dataAtual.getMonth() + 1);
  atualizarTituloMes();
  criarDiasDoMes();
});

btnAnterior.addEventListener("click", () => {
  dataAtual.setMonth(dataAtual.getMonth() - 1);
  atualizarTituloMes();
  criarDiasDoMes();
});

atualizarTituloMes();

const calendarDaysContainer = document.getElementById(
  "calendar-days-container"
);
const API_BASE = "../api/eventos.php";

const eventos = {};

if (typeof eventosFromDB !== "undefined" && eventosFromDB.length > 0) {
  eventosFromDB.forEach((evento) => {
    const data = evento.data_evento;
    if (!eventos[data]) {
      eventos[data] = [];
    }
    eventos[data].push({
      id: evento.id_evento,
      nome: evento.nome_evento,
      data: evento.data_evento,
      horario: evento.horario,
      local: evento.local,
      descricao: evento.descricao,
      prioridade: evento.prioridade,
      cor_tag: evento.cor_tag,
      status: evento.status,
    });
  });

  Object.keys(eventos).forEach((data) => {
    eventos[data].forEach((evento) => {
      adicionaEventonaListaDeEventos(evento);
      corDaTag(evento.cor_tag);
    });
  });
}

function abrirEventoDoDia(data) {
  const lista = document.getElementById("eventList");
  lista.innerHTML = "";
  if (eventos[data]) {
    eventos[data].forEach((evento, index) => {
      const li = document.createElement("li");
      li.innerHTML = `
                <div class="event-content">
                    <h4 class="event-title" id="nomeEvento">${evento.nome}</h4>
                    <div class="event-details">
                      <div class="event-detail">
                        <svg class="calendar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                          <line x1="16" y1="2" x2="16" y2="6" />
                          <line x1="8" y1="2" x2="8" y2="6" />
                          <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        <p id="dataEvento">${evento.data}</p>
                      </div>
                      <div class="event-detail">
                        <svg class="clock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <circle cx="12" cy="12" r="10" />
                          <polyline points="12,6 12,12 16,14" />
                        </svg>
                        <p id="horaEvento">${evento.horario}</p>
                      </div>
                    </div>
                    <div class="event-location">
                      <svg class="map-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                        <circle cx="12" cy="10" r="3" />
                      </svg>
                      <p id="localEvento">${evento.local}</p>
                    </div>
                  </div>
                  <span class="event-type meeting ${evento.cor_tag}" id="tagEvento">${evento.descricao}</span>

                </div>
                <button onclick="removerEvento('${data}', ${index})" class="btn-outline" style="margin-left: 1rem;">Excluir</button>
            `;
      lista.appendChild(li);
    });
  } else {
    lista.innerHTML = "<li>Nenhum evento para este dia.</li>";
  }

  janelaModalView.style.display = "flex";
}

async function removerEvento(data, index) {
  const evento = eventos[data][index];

  try {
    const response = await fetch(API_BASE, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "delete",
        id: evento.id,
      }),
    });

    const result = await response.json();

    if (result.success) {
      eventos[data].splice(index, 1);
      if (eventos[data].length === 0) {
        delete eventos[data];
      }
      abrirEventoDoDia(data);
      criarDiasDoMes();
    } else {
      console.error("Erro ao remover evento:", result.error);
    }
  } catch (error) {
    console.error("Erro na requisição:", error);
  }
}

function criarDiasDoMes() {
  calendarDaysContainer.innerHTML = "";

  const ano = dataAtual.getFullYear();
  const mes = dataAtual.getMonth();

  const primeiroDiaSemana = new Date(ano, mes, 1).getDay();
  const diasNoMes = new Date(ano, mes + 1, 0).getDate();
  const diasNoMesAnterior = new Date(ano, mes, 0).getDate();

  for (let i = primeiroDiaSemana; i > 0; i--) {
    const dia = document.createElement("div");
    dia.classList.add("calendar-day", "other-month");
    dia.textContent = diasNoMesAnterior - i + 1;
    calendarDaysContainer.appendChild(dia);
  }

  for (let i = 1; i <= diasNoMes; i++) {
    const dia = document.createElement("div");
    dia.classList.add("calendar-day");
    dia.textContent = i;

    const dataFormatada = `${ano}-${String(mes + 1).padStart(2, "0")}-${String(
      i
    ).padStart(2, "0")}`;

    if (eventos[dataFormatada]) {
      dia.classList.add("has-event");
    }

    dia.addEventListener("click", () => {
      dataSelecionada = dataFormatada;
      abrirEventoDoDia(dataFormatada);
    });

    calendarDaysContainer.appendChild(dia);
  }

  const totalDiasCriados = primeiroDiaSemana + diasNoMes;
  const diasRestantes = 7 - (totalDiasCriados % 7);

  if (diasRestantes < 7) {
    for (let i = 1; i <= diasRestantes; i++) {
      const dia = document.createElement("div");
      dia.classList.add("calendar-day", "other-month");
      dia.textContent = i;
      calendarDaysContainer.appendChild(dia);
    }
  }
}

criarDiasDoMes();

window.onclick = (event) => {
  if (event.target === janelaModalDay) {
    janelaModalDay.style.display = "none";
  }
  if (event.target === janelaModalView) {
    janelaModalView.style.display = "none";
  }
  if (event.target === createEventModal) {
    createEventModal.style.display = "none";
  }
  if (event.target === modalPrioridades) {
    modalPrioridades.style.display = "none";
  }
};

const btnCriarNovoEvento = document.getElementById("criar-novo-evento");
const btnCancelar = document.getElementById("btnCancelarPrincipal");
const btnCancelarCalendario = document.getElementById("btnCancelarCalendario");
const btnSalvarPrincipal = document.getElementById("btnSalvarPrincipal");
const btnSalvarCalendario = document.getElementById("btnSalvarCalendario");
const btnFecharView = document.getElementById("btnFecharView");

btnCriarNovoEvento.addEventListener("click", () => {
  createEventModal.style.display = "flex";
});

function fecharModal() {
  createEventModal.style.display = "none";
  janelaModalDay.style.display = "none";
  janelaModalView.style.display = "none";
}

btnCancelar.addEventListener("click", fecharModal);
btnCancelarCalendario.addEventListener("click", fecharModal);
btnFecharView.addEventListener("click", fecharModal);

btnSalvarPrincipal.addEventListener("click", async () => {
  const inputNome = document.querySelector("#janela-modal #nome");
  const inputDescricao = document.querySelector("#janela-modal #descricao");
  const inputData = document.querySelector("#janela-modal #data");
  const inputHorario = document.querySelector("#janela-modal #hora");
  const inputLocal = document.querySelector("#janela-modal #local");
  const cor = document.querySelector("#organizarPorCor").value;

  const evento = {
    nome: inputNome.value,
    descricao: inputDescricao.value,
    data: inputData.value,
    horario: inputHorario.value,
    local: inputLocal.value,
    cor_tag: cor,
    prioridade: "media",
    status: "pendente",
  };

  const success = await salvarEvento(evento);

  if (success) {
    inputNome.value = "";
    inputDescricao.value = "";
    inputData.value = "";
    inputHorario.value = "";
    inputLocal.value = "";

    adicionaEventonaListaDeEventos(evento);
    fecharModal();
    corDaTag(cor);
    criarDiasDoMes();
  } else {
    alert("Erro ao salvar evento. Tente novamente.");
  }
});

async function salvarEvento(evento) {
  try {
    const response = await fetch(API_BASE, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "create",
        ...evento,
      }),
    });

    const result = await response.json();

    if (result.success) {
      if (!eventos[evento.data]) {
        eventos[evento.data] = [];
      }
      evento.id = result.id;
      eventos[evento.data].push(evento);
      return true;
    } else {
      console.error("Erro ao salvar evento:", result.error);
      return false;
    }
  } catch (error) {
    console.error("Erro na requisição:", error);
    return false;
  }
}

function adicionaEventonaListaDeEventos(evento) {
  const div = document.createElement("div");
  const listaDeEventos = document.querySelector(".events-list");

  div.classList.add("event-item");
  div.dataset.prioridade = evento.prioridade || "media";
  div.dataset.cor = evento.cor_tag || "azul";
  div.dataset.status = evento.status || "pendente";

  if (evento.prioridade === "alta") {
    div.classList.add("high-priority");
  } else if (evento.prioridade === "media") {
    div.classList.add("medium-priority");
  } else if (evento.prioridade === "baixa") {
    div.classList.add("low-priority");
  }

  div.innerHTML = `
        <div class="event-content">
            <h4 class="event-title" id="nomeEvento">${evento.nome}</h4>
            <div class="event-details">
              <div class="event-detail">
                <svg class="calendar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                  <line x1="16" y1="2" x2="16" y2="6" />
                  <line x1="8" y1="2" x2="8" y2="6" />
                  <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
                <p id="dataEvento">${evento.data}</p>
              </div>
              <div class="event-detail">
                <svg class="clock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12,6 12,12 16,14" />
                </svg>
                <p id="horaEvento">${evento.horario}</p>
              </div>
            </div>
            <div class="event-location">
              <svg class="map-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <p id="localEvento">${evento.local}</p>
            </div>
          </div>
          <span class="event-type meeting ${evento.cor_tag}" id="tagEvento">${evento.descricao}</span>
        </div>
    `;

  listaDeEventos.appendChild(div);
}

function corDaTag(cor) {
  const tag = document.querySelector(".event-item:last-child .event-type");
  if (!tag) return;

  tag.classList.remove("azul", "vermelho", "verde", "amarelo", "rosa");

  switch (cor.toLowerCase()) {
    case "azul":
      tag.classList.add("azul");
      break;
    case "vermelho":
      tag.classList.add("vermelho");
      break;
    case "verde":
      tag.classList.add("verde");
      break;
    case "amarelo":
      tag.classList.add("amarelo");
      break;
    case "rosa":
      tag.classList.add("rosa");
      break;
  }
}

const eventosSalvos = document.querySelector(".events-list");
const modalPrioridades = document.getElementById("janela-modal-prioridade");
const btnSalvarPrioridade = document.getElementById("btnSalvarPrioridade");
const selectPrioridade = document.getElementById("prioridadeInput");
const statusConcluidoInput = document.getElementById("statusConcluidoInput");

let eventoSelecionado = null;

eventosSalvos.addEventListener("click", (e) => {
  const item = e.target.closest(".event-item");
  if (!item) return;

  eventoSelecionado = item;
  modalPrioridades.style.display = "flex";
});

btnSalvarPrioridade.addEventListener("click", async (e) => {
  e.preventDefault();
  modalPrioridades.style.display = "none";

  if (!eventoSelecionado) return;

  const dataEvento = eventoSelecionado.querySelector("#dataEvento").textContent;
  const nomeEvento = eventoSelecionado.querySelector("#nomeEvento").textContent;
  const prioridadeInput = document.getElementById("prioridadeInput");
  const prioridade = prioridadeInput.value;

  if (eventos[dataEvento]) {
    const evento = eventos[dataEvento].find((ev) => ev.nome === nomeEvento);

    if (evento) {
      try {
        const response = await fetch(API_BASE, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            action: "update_priority",
            id: evento.id,
            prioridade: prioridade,
          }),
        });

        const result = await response.json();

        if (result.success) {
          evento.prioridade = prioridade;

          eventoSelecionado.classList.remove(
            "high-priority",
            "medium-priority",
            "low-priority"
          );
          eventoSelecionado.dataset.prioridade = prioridade;

          if (prioridade === "alta") {
            eventoSelecionado.classList.add("high-priority");
          } else if (prioridade === "media") {
            eventoSelecionado.classList.add("medium-priority");
          } else if (prioridade === "baixa") {
            eventoSelecionado.classList.add("low-priority");
          }
        }
      } catch (error) {
        console.error("Erro ao atualizar prioridade:", error);
      }
    }
  }

  modalPrioridades.style.display = "none";
});

const checkConcluido = document.getElementById("statusConcluidoInput");
configurarStatusConcluido(eventosSalvos, checkConcluido, modalPrioridades);

function configurarStatusConcluido(eventosSalvos, checkConcluido, modalPrioridades) {
  checkConcluido.addEventListener("change", async () => {
    if (!eventoSelecionado) return;

    const dataEvento = eventoSelecionado.querySelector("#dataEvento").textContent;
    const nomeEvento = eventoSelecionado.querySelector("#nomeEvento").textContent;
    const evento = eventos[dataEvento].find(ev => ev.nome === nomeEvento);

    eventoSelecionado.classList.toggle("evento-concluido", checkConcluido.checked);

    evento.status = checkConcluido.checked ? "concluido" : "pendente";

    try {
      const response = await fetch(API_BASE, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "update_status",
          id: evento.id,
          status: evento.status,
        }),
      });

      const result = await response.json();
      if (!result.success) {
        console.error("Erro ao atualizar status:", result.error);
      }
    } catch (error) {
      console.error("Erro na atualização do status:", error);
    }
  });
}
