const modal = document.getElementById("janela-modal-orcamentos")
const btnAbrirModal = document.getElementById("abrirModal")
const btnFecharModal = document.getElementById("sair")

// Abrir modal
btnAbrirModal.onclick = () => (modal.style.display = "block")

// Fechar modal
btnFecharModal.onclick = () => {
  modal.style.display = "none"
  // Limpar campos do formulário
  document.getElementById("item").value = ""
  document.getElementById("fornecedor").value = ""
  document.getElementById("quantidade").value = "1"
  document.getElementById("valor_unitario").value = ""
}

// Fechar modal clicando fora
window.onclick = (event) => {
  if (event.target == modal) {
    modal.style.display = "none"
  }
}

document.querySelector('form[method="post"]').addEventListener("submit", (e) => {
  const item = document.getElementById("item").value.trim()
  const valorUnitario = document.getElementById("valor_unitario").value
  const quantidade = document.getElementById("quantidade").value

  if (!item) {
    alert("Por favor, informe o nome do item.")
    e.preventDefault()
    return false
  }

  if (!valorUnitario || Number.parseFloat(valorUnitario) <= 0) {
    alert("Por favor, informe um valor unitário válido.")
    e.preventDefault()
    return false
  }

  if (!quantidade || Number.parseInt(quantidade) <= 0) {
    alert("Por favor, informe uma quantidade válida.")
    e.preventDefault()
    return false
  }

  // Se chegou até aqui, o formulário é válido
  return true
})

document.addEventListener("DOMContentLoaded", () => {
  // Adicionar funcionalidade de estrelas para avaliação (apenas visual)
  const estrelasContainers = document.querySelectorAll(".estrelas")

  estrelasContainers.forEach((container) => {
    const estrelas = container.querySelectorAll(".star-icon")
    let nota = 0

    estrelas.forEach((estrela, index) => {
      estrela.style.cursor = "pointer"

      estrela.addEventListener("click", () => {
        if (nota === index + 1) {
          // Se clicar na mesma estrela, remove a avaliação
          nota = 0
          estrelas.forEach((s) => (s.style.fill = "#ccc"))
        } else {
          // Define nova avaliação
          nota = index + 1
          estrelas.forEach((s, i) => {
            s.style.fill = i < nota ? "#ffc107" : "#ccc"
          })
        }
      })

      // Efeito hover
      estrela.addEventListener("mouseenter", () => {
        estrelas.forEach((s, i) => {
          s.style.fill = i <= index ? "#ffc107" : "#ccc"
        })
      })
    })

    // Restaurar estado original ao sair do hover
    container.addEventListener("mouseleave", () => {
      estrelas.forEach((s, i) => {
        s.style.fill = i < nota ? "#ffc107" : "#ccc"
      })
    })
  })
})
