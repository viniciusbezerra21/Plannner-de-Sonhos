const modal = document.getElementById("janela-modal-orcamentos");
const btnAbrirModal = document.getElementById("abrirModal");
const btnFecharModal = document.getElementById("sair");
const tbody = document.getElementById("tabelaPrincipal");

let orcamentos = [];


// Abrir modal
btnAbrirModal.onclick = () => modal.style.display = "block";
btnFecharModal.onclick = () => modal.style.display = "none";

// Fechar modal clicando no X

window.onclick = (event) => {
  if (event.target == modal) modal.style.display = "none";
}

//campo avaliação



const tabelaPrincipal = document.getElementById("tabelaPrincipal");
const totalGeral = document.getElementById("total");
const btnAdicionar = document.getElementById("adicionarItem");

btnAdicionar.addEventListener("click", (e) => {
  e.preventDefault();

  // Pega os inputs do modal
  const inputs = document.querySelectorAll("#janela-modal-orcamentos input");
  const item = inputs[0].value.trim();
  const fornecedor = inputs[1].value.trim();
  const quantidade = parseFloat(inputs[2].value) || 0;
  const valorUnitario = parseFloat(inputs[3].value) || 0;

  if (!item || !fornecedor || quantidade <= 0 || valorUnitario <= 0) {
    alert("Preencha todos os campos corretamente!");
    return;
  }

  const valorTotal = quantidade * valorUnitario;

  // Cria nova linha
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td style="padding: 0.75rem">${item}</td>
    <td style="padding: 0.75rem">${fornecedor}</td>
    <td style="padding: 0.75rem"><div class="estrelas"></div></td>
    <td style="padding: 0.75rem">${quantidade}</td>
    <td style="padding: 0.75rem">R$ ${valorUnitario.toFixed(2)}</td>
    <td style="padding: 0.75rem">R$ ${valorTotal.toFixed(2)}</td>
  `;

  tabelaPrincipal.appendChild(tr);

  // Adiciona as 5 estrelas dinâmicas
  const estrelasContainer = tr.querySelector(".estrelas");
  const svgPath = "M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z";

  for (let i = 0; i < 5; i++) {
    const estrela = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    estrela.setAttribute("class", "star-icon");
    estrela.setAttribute("viewBox", "0 0 24 24");
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", svgPath);
    estrela.appendChild(path);
    estrelasContainer.appendChild(estrela);
  }

  // Ativa a lógica de clique nas estrelas só dessa linha
  const estrelas = estrelasContainer.querySelectorAll(".star-icon");
  let nota = 0;
  estrelas.forEach((estrela, index) => {
    estrela.addEventListener("click", () => {
      if (nota === index + 1) {
        nota = 0;
        estrelas.forEach(s => s.classList.remove("selected"));
      } else {
        nota = index + 1;
        estrelas.forEach((s, i) => {
          if (i < nota) s.classList.add("selected");
          else s.classList.remove("selected");
        });
      }
    });
  });

  // Atualiza o total geral
  let soma = 0;
  tabelaPrincipal.querySelectorAll("tr").forEach(row => {
    const td = row.querySelector("td:last-child");
    if (td) {
      const valor = parseFloat(td.textContent.replace("R$", "").replace(",", "."));
      soma += isNaN(valor) ? 0 : valor;
    }
  });
  totalGeral.textContent = "R$ " + soma.toFixed(2);

  // Limpa inputs
  inputs.forEach(input => input.value = "");
});











