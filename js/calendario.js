// --- MODAL CRIAÇÃO LIVRE (botão "criar novo evento") ---
const btnJanelaModal = document.getElementById('criar-novo-evento');
const janelaModal = document.getElementById('janela-modal');
const btnCancelar = document.getElementById('btnCancelar');
const btnSalvar = document.getElementById('btnSalvarModal');

// --- MODAL DO DIA ---
const janelaModalDay = document.getElementById("janela-modal-day");
const btnCancelarDay = janelaModalDay.querySelector("#btnCancelar");
const btnSalvarDay = janelaModalDay.querySelector("#btnSalvar");

// inputs do modal do dia
const inputNomeDay = janelaModalDay.querySelector("#nome");
const inputHoraDay = janelaModalDay.querySelector("#hora");
const inputLocalDay = janelaModalDay.querySelector("#local");

// container do calendário
const headerRow = document.querySelector(".calendar-header-row");
const diasContainer = document.querySelector(".calendar-days");
const monthLabel = document.querySelector(".calendar-month");
const btnPrev = document.querySelector(".prev");
const btnNext = document.querySelector(".next");

const diasSemana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
const meses = [
  "Janeiro","Fevereiro","Março","Abril","Maio","Junho",
  "Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"
];

let dataAtual = new Date();
let diaSelecionado = null;

// estrutura para guardar os eventos
let eventos = {
  "2025-08-15": [
    {
      nome: "Reunião com Fotógrafo",
      hora: "14:00",
      local: "Studio Fotográfico",
      tipo: "Reunião"
    }
  ],
  "2025-08-18": [
    {
      nome: "Degustação do Buffet",
      hora: "19:00",
      local: "Restaurante Elegance",
      tipo: "Degustação"
    }
  ],
  "2025-08-22": [
    {
      nome: "Prova do Vestido",
      hora: "15:30",
      local: "Atelier Noiva Bella",
      tipo: "Prova"
    }
  ],
  "2025-08-25": [
    {
      nome: "Reunião com Decorador",
      hora: "10:00",
      local: "Escritório Decor & Arte",
      tipo: "Reunião"
    }
  ]
};
// exemplo de estrutura: { "2025-08-29": [ {nome, hora, local, descricao} ] }

// cria cabeçalho só 1 vez
diasSemana.forEach(dia => {
  const div = document.createElement("div");
  div.classList.add("calendar-day-header");
  div.textContent = dia;
  headerRow.appendChild(div);
});

function renderCalendar(data) {
  diasContainer.innerHTML = ""; 
  const ano = data.getFullYear();
  const mes = data.getMonth();

  // atualiza título
  monthLabel.textContent = `${meses[mes]} ${ano}`;

  const totalDias = new Date(ano, mes + 1, 0).getDate();
  const primeiroDiaSemana = new Date(ano, mes, 1).getDay();
  const totalDiasMesAnterior = new Date(ano, mes, 0).getDate();

  // dias do mês anterior
  for (let i = primeiroDiaSemana - 1; i >= 0; i--) {
    const div = document.createElement("div");
    div.classList.add("calendar-day", "other-month");
    div.textContent = totalDiasMesAnterior - i;
    diasContainer.appendChild(div);
  }

  // dias do mês atual
  for (let dia = 1; dia <= totalDias; dia++) {
    const div = document.createElement("div");
    div.classList.add("calendar-day");
    div.textContent = dia;

    // gera string da data
    const dataStr = `${ano}-${String(mes+1).padStart(2,"0")}-${String(dia).padStart(2,"0")}`;
    div.dataset.date = dataStr;

    // se já tiver evento, marca visual
    if (eventos[dataStr] && eventos[dataStr].length > 0) {
      div.classList.add("has-event");
      const dot = document.createElement("span");
      dot.className = "event-dot";
      div.appendChild(dot);
    }

    diasContainer.appendChild(div);
  }

  // dias do próximo mês
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

// navegação
btnPrev.addEventListener("click", () => {
  dataAtual.setMonth(dataAtual.getMonth() - 1);
  renderCalendar(dataAtual);
});

btnNext.addEventListener("click", () => {
  dataAtual.setMonth(dataAtual.getMonth() + 1);
  renderCalendar(dataAtual);
});

// render inicial
renderCalendar(dataAtual);

// abrir modal ao clicar no dia do calendário
diasContainer.addEventListener("click", (e) => {
  const alvo = e.target.closest(".calendar-day");
  if (!alvo || alvo.classList.contains("other-month")) return;

  diaSelecionado = alvo;
  const dataStr = alvo.dataset.date;

  if (eventos[dataStr] && eventos[dataStr].length > 0) {
    // abre modal de visualizar
    listaEventos.innerHTML = "";
    eventos[dataStr].forEach(ev => {
      const div = document.createElement("div");
      div.className = "evento-item";
      div.innerHTML = `
        <p><strong>${ev.nome}</strong></p>
        <p>Hora: ${ev.hora || "—"}</p>
        <p>Local: ${ev.local || "—"}</p>
        <hr>
      `;
      listaEventos.appendChild(div);
    });
    janelaModalView.style.display = "flex";
  } else {
    // abre modal de adicionar
    janelaModalDay.style.display = "flex";
  }
});

// salvar evento no dia
btnSalvarDay.addEventListener("click", (e) => {
  e.preventDefault();
  if (!diaSelecionado) return;

  const dataStr = diaSelecionado.dataset.date;
  const nome = inputNomeDay.value.trim();
  const hora = inputHoraDay.value;
  const local = inputLocalDay.value.trim();

  if (!nome) return; // nome é obrigatório

  // cria obj evento
  const evento = { nome, hora, local };

  if (!eventos[dataStr]) eventos[dataStr] = [];
  eventos[dataStr].push(evento);

  // limpa inputs
  inputNomeDay.value = "";
  inputHoraDay.value = "";
  inputLocalDay.value = "";

  // fecha modal
  janelaModalDay.style.display = "none";

  // re-renderiza calendário para mostrar ponto
  renderCalendar(dataAtual);

  console.log("Eventos:", eventos);
});

// cancelar modal do dia
btnCancelarDay.addEventListener("click", (e) => {
  e.preventDefault();
  janelaModalDay.style.display = "none";
});

// cancelar modal de criação livre
btnCancelar.addEventListener("click", (e) => {
  e.preventDefault();
  janelaModal.style.display = "none";
});

// abrir modal de criação livre
btnJanelaModal.addEventListener("click", () => {
  janelaModal.style.display = "flex";
});

// fechar modal clicando fora
window.addEventListener("click", (e) => {
  if (e.target === janelaModal) janelaModal.style.display = "none";
  if (e.target === janelaModalDay) janelaModalDay.style.display = "none";
});

// --- MODAL DE VISUALIZAÇÃO ---
const janelaModalView = document.getElementById("janela-modal-view");
const listaEventos = document.getElementById("listaEventos");
const btnFecharView = document.getElementById("btnFecharView");

// abrir modal ao clicar no dia do calendário
diasContainer.addEventListener("click", (e) => {
  const alvo = e.target.closest(".calendar-day");
  if (!alvo || alvo.classList.contains("other-month")) return;

  diaSelecionado = alvo;
  const dataStr = alvo.dataset.date;

  if (eventos[dataStr] && eventos[dataStr].length > 0) {
    // já tem evento → abre modal de visualização
    listaEventos.innerHTML = "";
    eventos[dataStr].forEach(ev => {
      const div = document.createElement("div");
      div.className = "evento-item";
      div.innerHTML = `
        <p><strong>${ev.nome}</strong></p>
        <p>Hora: ${ev.hora || "—"}</p>
        <p>Local: ${ev.local || "—"}</p>
        <hr>
      `;
      listaEventos.appendChild(div);
    });
    janelaModalView.style.display = "flex";
  } else {
    // sem evento → abre modal de criação
    janelaModalDay.style.display = "flex";
  }
});

// fechar modal de visualização
btnFecharView.addEventListener("click", () => {
  janelaModalView.style.display = "none";
});

window.addEventListener("click", (e) => {
  if (e.target === janelaModal) janelaModal.style.display = "none";
  if (e.target === janelaModalDay) janelaModalDay.style.display = "none";
  if (e.target === janelaModalView) janelaModalView.style.display = "none";
});

btnSalvar.addEventListener("click", (e) => {
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

  const evento = { nome, hora, local };
  if (!eventos[dataStr]) eventos[dataStr] = [];
  eventos[dataStr].push(evento);

  // limpar inputs
  document.getElementById("nome").value = "";
  document.getElementById("hora").value = "";
  document.getElementById("local").value = "";
  document.getElementById("data").value = "";

  janelaModal.style.display = "none";
  renderCalendar(dataAtual);
});

