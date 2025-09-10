const modal = document.getElementById("janela-modal-orcamentos")
const btnAbrirModal = document.getElementById("abrirModal")
const btnFecharModal = document.getElementById("sair")
const tbody = document.getElementById("tabelaPrincipal")

const orcamentos = []

window.addEventListener("DOMContentLoaded", carregarItensDoServidor)

// Abrir modal
btnAbrirModal.onclick = () => (modal.style.display = "block")
btnFecharModal.onclick = () => (modal.style.display = "none")

// Fechar modal clicando no X
window.onclick = (event) => {
  if (event.target == modal) modal.style.display = "none"
}

//campo avaliação

const tabelaPrincipal = document.getElementById("tabelaPrincipal")
const totalGeral = document.getElementById("total")
const btnAdicionar = document.getElementById("adicionarItem")

btnAdicionar.addEventListener("click", async (e) => {
  e.preventDefault()

  // Pega os inputs do modal
  const inputs = document.querySelectorAll("#janela-modal-orcamentos input")
  const item = inputs[0].value.trim()
  const fornecedor = inputs[1].value.trim()
  const quantidade = Number.parseFloat(inputs[2].value) || 0
  const valorUnitario = Number.parseFloat(inputs[3].value) || 0

  if (!item || !fornecedor || quantidade <= 0 || valorUnitario <= 0) {
    alert("Preencha todos os campos corretamente!")
    return
  }

  try {
    const formData = new FormData()
    formData.append("action", "salvar_item")
    formData.append("item", item)
    formData.append("fornecedor", fornecedor)
    formData.append("quantidade", quantidade)
    formData.append("valor_uni", valorUnitario)

    const response = await fetch(window.location.href, {
      method: "POST",
      body: formData,
    })

    const result = await response.json()

    if (!result.success) {
      alert("Erro ao salvar: " + result.message)
      return
    }

    // Se salvou com sucesso, adiciona na tabela
    adicionarItemNaTabela(item, fornecedor, quantidade, valorUnitario)

    // Limpa inputs
    inputs.forEach((input) => (input.value = ""))

    // Fecha modal
    modal.style.display = "none"
  } catch (error) {
    console.error("Erro:", error)
    alert("Erro ao salvar item no banco de dados")
  }
})

function adicionarItemNaTabela(item, fornecedor, quantidade, valorUnitario) {
  const valorTotal = quantidade * valorUnitario

  // Cria nova linha
  const tr = document.createElement("tr")

  tr.innerHTML = `
    <td style="padding: 0.75rem">${item}</td>
    <td style="padding: 0.75rem">${fornecedor}</td>
    <td style="padding: 0.75rem"><div class="estrelas"></div></td>
    <td style="padding: 0.75rem">${quantidade}</td>
    <td style="padding: 0.75rem">R$ ${valorUnitario.toFixed(2)}</td>
    <td style="padding: 0.75rem">R$ ${valorTotal.toFixed(2)}</td>
  `

  tabelaPrincipal.appendChild(tr)

  // Adiciona as 5 estrelas dinâmicas
  const estrelasContainer = tr.querySelector(".estrelas")
  const svgPath = "M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"

  for (let i = 0; i < 5; i++) {
    const estrela = document.createElementNS("http://www.w3.org/2000/svg", "svg")
    estrela.setAttribute("class", "star-icon")
    estrela.setAttribute("viewBox", "0 0 24 24")
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path")
    path.setAttribute("d", svgPath)
    estrela.appendChild(path)
    estrelasContainer.appendChild(estrela)
  }

  // Ativa a lógica de clique nas estrelas só dessa linha
  const estrelas = estrelasContainer.querySelectorAll(".star-icon")
  let nota = 0
  estrelas.forEach((estrela, index) => {
    estrela.addEventListener("click", () => {
      if (nota === index + 1) {
        nota = 0
        estrelas.forEach((s) => s.classList.remove("selected"))
      } else {
        nota = index + 1
        estrelas.forEach((s, i) => {
          if (i < nota) s.classList.add("selected")
          else s.classList.remove("selected")
        })
      }
    })
  })

  atualizarTotalGeral()
}

async function carregarItensDoServidor() {
  try {
    const response = await fetch(window.location.href + "?action=carregar_itens")
    const result = await response.json()

    if (result.success && result.itens) {
      tabelaPrincipal.innerHTML = ""

      // Adiciona cada item do banco na tabela
      result.itens.forEach((item) => {
        adicionarItemNaTabela(item.item, item.fornecedor, item.quantidade, Number.parseFloat(item.valor_uni))
      })
    }
  } catch (error) {
    console.error("Erro ao carregar itens:", error)
  }
}

function atualizarTotalGeral() {
  let soma = 0
  tabelaPrincipal.querySelectorAll("tr").forEach((row) => {
    const td = row.querySelector("td:last-child")
    if (td) {
      const valor = Number.parseFloat(td.textContent.replace("R$", "").replace(",", "."))
      soma += isNaN(valor) ? 0 : valor
    }
  })
  totalGeral.textContent = "R$ " + soma.toFixed(2)
}
