const modal = document.getElementById("janela-modal-orcamentos");
const btn = document.getElementById("abrirModal");
const span = modal.querySelector(".fechar");

btn.onclick = () => modal.style.display = "block";
span.onclick = () => modal.style.display = "none";

window.onclick = (event) => {
  if (event.target == modal) modal.style.display = "none";
}
