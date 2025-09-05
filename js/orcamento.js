const modal = document.getElementById("janela-modal-orcamentos");
const btnAbrirModal = document.getElementById("abrirModal");
const btnFecharModal = document.getElementById("sair");
const btnAdicionar = document.getElementById("adicionarItem");
const tbody = document.getElementById("tabelaPrincipal");

let orcamentos = [];


// Abrir modal
btnAbrirModal.onclick = () => modal.style.display = "block";
btnFecharModal.onclick = () => modal.style.display = "none";

// Fechar modal clicando no X

window.onclick = (event) => {
  if (event.target == modal) modal.style.display = "none";
}

// interacao com avaliacao

const stars = document.querySelectorAll('.star-icon');
const ratingContainer = document.getElementById('avaliacao');
const ratingValue = document.getElementById('rating-value');

let currentRating = 0;

function updateStars(rating) {
  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.add('filled');
    } else {
      star.classList.remove('filled');
    }
  });
  ratingValue.value = rating;
}
stars.forEach(star => {
  star.addEventListener('click', function() {
    // Obtém o valor da estrela clicada a partir do atributo data-value
    currentRating = this.dataset.value;
    ratingValueInput.value = currentRating; // Atualiza o valor no input oculto
    updateStars(currentRating); // Atualiza a aparência para refletir o clique
    console.log("Avaliação selecionada:", currentRating);
  });

  // Evento para quando o mouse passa por cima da estrela (efeito hover)
  star.addEventListener('mouseover', function() {
    // Ilumina as estrelas até a que o mouse está sobre
    updateStars(this.dataset.value);
  });
});

// Evento para quando o mouse sai do contêiner de estrelas
ratingContainer.addEventListener('mouseleave', function() {
  // Restaura a aparência para a última avaliação clicada (currentRating)
  updateStars(currentRating);
});





btnAdicionar.onclick = () => {
  const item = document.getElementById("item").value;
  const forcedor = document.getElementById("fornecedor").value;
  const avaliacao = document.getElementById("avaliacao").value;
  const quantidade = document.getElementById("quantidade").value;
  const valorUnitario = document.getElementById("valorUnit").value;
  const valorTotal = document.getElementById("valorTotal").value;

  if (item && forcedor && avaliacao && quantidade && valorUnitario) {
    tbody.innerHTML += `
      <tr>
        <td>${item}</td>
        <td>${forcedor}</td>
        <td>${avaliacao}</td>
        <td>${quantidade}</td>
        <td>${valorUnitario}</td>
        <td>${valorTotal}</td>
      </tr>
    `;
  } else {
    alert("Por favor, preencha todos os campos.");
  }
  
}

