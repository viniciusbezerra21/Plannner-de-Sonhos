const tituloMes = document.getElementById('calendar-month');
const btnProximo = document.getElementById('next-month-btn');
const btnAnterior = document.getElementById('prev-month-btn');

const janelaModalDay = document.getElementById('janela-modal-day');
const createEventModal = document.getElementById('janela-modal');
const janelaModalView = document.getElementById('janela-modal-view');

let dataAtual = new Date();
let dataSelecionada = null;

// Atualiza o título do mês
function atualizarTituloMes() {
    const mes = dataAtual.toLocaleString('default', { month: 'long' });
    const ano = dataAtual.getFullYear();
    tituloMes.textContent = `${mes} ${ano}`;
}

btnProximo.addEventListener('click', () => {
    dataAtual.setMonth(dataAtual.getMonth() + 1);
    atualizarTituloMes();
    criarDiasDoMes();
});

btnAnterior.addEventListener('click', () => {
    dataAtual.setMonth(dataAtual.getMonth() - 1);
    atualizarTituloMes();
    criarDiasDoMes();
});

atualizarTituloMes();

const calendarDaysContainer = document.getElementById('calendar-days-container');

// Função para salvar evento no localStorage
function salvarEvento(evento) {
    let eventos = JSON.parse(localStorage.getItem('eventos')) || {};
    if (!eventos[evento.data]) {
        eventos[evento.data] = [];
    }
    eventos[evento.data].push(evento);
    localStorage.setItem('eventos', JSON.stringify(eventos));
}

// Função para abrir eventos do dia
function abrirEventoDoDia(data) {
    const lista = document.getElementById('eventList');
    lista.innerHTML = "";
    let eventos = JSON.parse(localStorage.getItem('eventos')) || {};

    if (eventos[data]) {
        eventos[data].forEach((evento, index) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <strong>${evento.nome}</strong> - ${evento.horario || ''} <br>
                ${evento.local || ''} <br>
                ${evento.descricao || ''}
                <br>
                <button onclick="removerEvento('${data}', ${index})" class="btn-outline">Excluir</button>
            `;
            lista.appendChild(li);
        });
    } else {
        lista.innerHTML = "<li>Nenhum evento para este dia.</li>";
    }

    janelaModalView.style.display = 'flex';
}

// Função para remover evento
function removerEvento(data, index) {
    let eventos = JSON.parse(localStorage.getItem('eventos')) || {};
    if (eventos[data]) {
        eventos[data].splice(index, 1);
        if (eventos[data].length === 0) {
            delete eventos[data];
        }
        localStorage.setItem('eventos', JSON.stringify(eventos));
    }
    abrirEventoDoDia(data);
    criarDiasDoMes();
}

// Função para criar os dias no calendário
function criarDiasDoMes() {
    calendarDaysContainer.innerHTML = "";

    const ano = dataAtual.getFullYear();
    const mes = dataAtual.getMonth();

    const primeiroDiaSemana = new Date(ano, mes, 1).getDay();
    const diasNoMes = new Date(ano, mes + 1, 0).getDate();
    const diasNoMesAnterior = new Date(ano, mes, 0).getDate();

    // Dias do mês anterior
    for (let i = primeiroDiaSemana; i > 0; i--) {
        const dia = document.createElement('div');
        dia.classList.add('calendar-day', 'other-month');
        dia.textContent = diasNoMesAnterior - i + 1;
        calendarDaysContainer.appendChild(dia);
    }

    // Dias do mês atual
    let eventos = JSON.parse(localStorage.getItem('eventos')) || {};

    for (let i = 1; i <= diasNoMes; i++) {
        const dia = document.createElement('div');
        dia.classList.add('calendar-day');
        dia.textContent = i;

        const dataFormatada = `${ano}-${String(mes + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

        if (eventos[dataFormatada]) {
            dia.classList.add('has-event');
        }

        dia.addEventListener('click', () => {
            dataSelecionada = dataFormatada;
            abrirEventoDoDia(dataFormatada);
        });

        calendarDaysContainer.appendChild(dia);
    }

    // Dias do próximo mês
    const totalDiasCriados = primeiroDiaSemana + diasNoMes;
    const diasRestantes = 7 - (totalDiasCriados % 7);

    if (diasRestantes < 7) {
        for (let i = 1; i <= diasRestantes; i++) {
            const dia = document.createElement('div');
            dia.classList.add('calendar-day', 'other-month');
            dia.textContent = i;
            calendarDaysContainer.appendChild(dia);
        }
    }
}

criarDiasDoMes();

// Fechar modais ao clicar fora
window.onclick = function (event) {
    if (event.target === janelaModalDay) {
        janelaModalDay.style.display = "none";
    }
    if (event.target === janelaModalView) {
        janelaModalView.style.display = "none";
    }
    if (event.target === createEventModal) {
        createEventModal.style.display = "none";
    }
};

// Botões modais
const btnCriarNovoEvento = document.getElementById('criar-novo-evento');
const btnCancelar = document.getElementById('btnCancelarPrincipal');
const btnCancelarCalendario = document.getElementById('btnCancelarCalendario');
const btnSalvarPrincipal = document.getElementById('btnSalvarPrincipal');
const btnSalvarCalendario = document.getElementById('btnSalvarCalendario');
const btnFecharView = document.getElementById('btnFecharView');

btnCriarNovoEvento.addEventListener('click', () => {
    createEventModal.style.display = 'flex';
});

function fecharModal() {
    createEventModal.style.display = 'none';
    janelaModalDay.style.display = 'none';
    janelaModalView.style.display = 'none';
}

btnCancelar.addEventListener('click', fecharModal);
btnCancelarCalendario.addEventListener('click', fecharModal);
btnFecharView.addEventListener('click', fecharModal);

// Salvar evento do modal principal
btnSalvarPrincipal.addEventListener('click', () => {
    const inputNome = document.querySelector('#janela-modal #nome');
    const inputDescricao = document.querySelector('#janela-modal #descricao');
    const inputData = document.querySelector('#janela-modal #data');
    const inputHorario = document.querySelector('#janela-modal #hora');
    const inputLocal = document.querySelector('#janela-modal #local');

    const evento = {
        nome: inputNome.value,
        descricao: inputDescricao.value,
        data: inputData.value,
        horario: inputHorario.value,
        local: inputLocal.value
    };

    inputNome.value = '';
    inputDescricao.value = '';
    inputData.value = '';
    inputHorario.value = '';
    inputLocal.value = '';

    salvarEvento(evento);
    fecharModal();
    criarDiasDoMes();
});

// Salvar evento do modal calendário
btnSalvarCalendario.addEventListener('click', () => {
    const inputNome = document.querySelector('#janela-modal-day #nome');
    const inputDescricao = document.querySelector('#janela-modal-day #descricao');
    const inputHorario = document.querySelector('#janela-modal-day #hora');
    const inputLocal = document.querySelector('#janela-modal-day #local');

    if (!dataSelecionada) return;

    const evento = {
        nome: inputNome.value,
        descricao: inputDescricao.value,
        data: dataSelecionada,
        horario: inputHorario.value,
        local: inputLocal.value
    };

    inputNome.value = '';
    inputDescricao.value = '';
    inputHorario.value = '';
    inputLocal.value = '';

    salvarEvento(evento);
    fecharModal();
    criarDiasDoMes();
});
