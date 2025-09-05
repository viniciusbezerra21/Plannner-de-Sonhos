const modal = document.getElementById("janela-modal-orcamentos");
const btn = document.getElementById("abrirModal");
const span = modal.querySelector(".fechar");

// Abrir modal
btn.onclick = () => modal.style.display = "block";

// Fechar modal clicando no X
span.onclick = () => modal.style.display = "none";

// Fechar modal clicando fora do conteúdo
window.onclick = (event) => {
  if (event.target == modal) modal.style.display = "none";
}

//teste de commit