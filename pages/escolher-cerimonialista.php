<?php
session_start();
require_once "../config/conexao.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'cliente') {
    header("Location: ../user/login-unified.php?type=cliente");
    exit;
}

$id_cliente = $_SESSION['usuario_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_cerimonialista'])) {
    $id_cerimonialista = (int)$_POST['id_cerimonialista'];
    $data_casamento = $_POST['data_casamento'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO cliente_cerimonialista (id_cliente, id_cerimonialista, data_casamento, status)
            VALUES (?, ?, ?, 'ativo')
            ON DUPLICATE KEY UPDATE status = 'ativo'
        ");
        $stmt->execute([$id_cliente, $id_cerimonialista, $data_casamento]);

        $_SESSION['id_cerimonialista'] = $id_cerimonialista;
        $_SESSION['data_casamento'] = $data_casamento;

        header("Location: ../pages/dashboard-cliente.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error selecting cerimonialista: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Escolher Cerimonialista - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .calendario-container {
      max-width: 1000px;
      margin: auto;
      padding: 1rem 0;
    }

    .calendario-header {
      text-align: center;
      margin-bottom: 1rem;
    }

    .calendario-header h1 {
      font-size: 1.5rem;
      color: hsl(var(--foreground));
      margin-bottom: 0.25rem;
    }

    .calendario-header p {
      color: hsl(var(--muted-foreground));
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }

    .mes-navegacao {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .mes-navegacao button {
      padding: 0.5rem 1rem;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      border: none;
      border-radius: 0.5rem;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
      font-size: 0.9rem;
    }

    .mes-navegacao button:hover {
      transform: scale(1.05);
    }

    .mes-titulo {
      font-size: 1.1rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      min-width: 150px;
    }

    .calendario-wrapper {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 1rem;
    }

    .dias-semana {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 0.3rem;
      margin-bottom: 0.5rem;
    }

    .dia-semana {
      text-align: center;
      font-weight: 600;
      padding: 0.4rem;
      color: hsl(var(--muted-foreground));
      font-size: 0.75rem;
    }

    .dias-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 0.3rem;
    }

    .dia {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid hsl(var(--border));
      border-radius: 0.5rem;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
      background: hsl(var(--background));
      color: hsl(var(--foreground));
      font-size: 0.8rem;
      position: relative;
    }

    .dia:hover:not(.vazio):not(.passado) {
      border-color: hsl(var(--primary));
      background: hsl(var(--primary) / 0.1);
      transform: scale(1.05);
    }

    .dia.passado {
      opacity: 0.4;
      cursor: not-allowed;
      background: hsl(var(--muted));
    }

    .dia.vazio {
      border: none;
      cursor: default;
      background: transparent;
    }

    .dia.com-disponiveis {
      border-color: #10b981;
      background: #ecfdf5;
      font-weight: 600;
      color: #059669;
    }

    .dia.com-disponiveis:after {
      content: '✓';
      position: absolute;
      top: -8px;
      right: -8px;
      background: #10b981;
      color: white;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: bold;
    }

    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: hsl(var(--card));
      border-radius: 1rem;
      max-width: 600px;
      width: 100%;
      max-height: 80vh;
      overflow-y: auto;
      padding: 2rem;
      position: relative;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .modal-header h3 {
      margin: 0;
      color: hsl(var(--foreground));
      font-size: 1.25rem;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 2rem;
      cursor: pointer;
      color: hsl(var(--muted-foreground));
      transition: color 0.2s;
    }

    .modal-close:hover {
      color: hsl(var(--foreground));
    }

    .cerimonialistas-lista {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .cerimonialista-item {
      display: flex;
      gap: 0.75rem;
      padding: 0.75rem;
      background: hsl(var(--muted));
      border-radius: 0.5rem;
      align-items: flex-start;
      transition: all 0.2s;
      border: 2px solid transparent;
    }

    .cerimonialista-item:hover {
      border-color: hsl(var(--primary));
      background: hsl(var(--primary) / 0.05);
    }

    .cerimonialista-foto {
      width: 50px;
      height: 50px;
      border-radius: 0.5rem;
      object-fit: cover;
      flex-shrink: 0;
    }

    .cerimonialista-info {
      flex: 1;
    }

    .cerimonialista-info h4 {
      margin: 0 0 0.15rem 0;
      color: hsl(var(--foreground));
      font-size: 0.9rem;
    }

    .cerimonialista-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.3rem;
      font-size: 0.75rem;
      color: hsl(var(--muted-foreground));
    }

    .stars {
      color: #fbbf24;
    }

    .cerimonialista-bio {
      font-size: 0.75rem;
      color: hsl(var(--muted-foreground));
      line-height: 1.3;
      margin-bottom: 0.5rem;
    }

    .cerimonialista-acoes {
      display: flex;
      gap: 0.3rem;
    }

    .btn-fornecedores {
      flex: 1;
      padding: 0.35rem 0.5rem;
      background: hsl(var(--muted));
      color: hsl(var(--foreground));
      border: 1px solid hsl(var(--border));
      border-radius: 0.4rem;
      cursor: pointer;
      font-size: 0.7rem;
      font-weight: 500;
      transition: all 0.2s;
    }

    .btn-fornecedores:hover {
      background: hsl(var(--primary) / 0.1);
      border-color: hsl(var(--primary));
    }

    .btn-escolher {
      flex: 1;
      padding: 0.35rem 0.5rem;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      border: none;
      border-radius: 0.4rem;
      cursor: pointer;
      font-size: 0.7rem;
      font-weight: 600;
      transition: all 0.2s;
    }

    .btn-escolher:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
    }

    .vazio-estado {
      text-align: center;
      padding: 2rem;
      color: hsl(var(--muted-foreground));
    }

    .legenda {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 1rem;
      padding: 0.75rem;
      background: hsl(var(--muted));
      border-radius: 0.5rem;
      font-size: 0.75rem;
    }

    .legenda-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .legenda-cor {
      width: 16px;
      height: 16px;
      border-radius: 0.25rem;
      border: 2px solid;
    }

    @media (max-width: 768px) {
      .dias-semana,
      .dias-grid {
        gap: 0.2rem;
      }

      .dia {
        font-size: 0.7rem;
      }

      .mes-navegacao {
        gap: 0.5rem;
        flex-wrap: wrap;
      }

      .mes-titulo {
        order: 3;
        width: 100%;
        margin-top: 0.5rem;
      }

      .cerimonialista-item {
        flex-direction: column;
      }

      .cerimonialista-foto {
        width: 100%;
        height: 150px;
      }
    }
    
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="../index.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content" style="padding-top: 4rem">
      <div class="container">
        <div class="calendario-container">
          <div class="calendario-header">
            <h1>Escolha a Data do Seu Casamento</h1>
            <p>Clique em um dia para ver os cerimonialistas disponíveis</p>
          </div>

          <!-- Added calendar-based selection interface -->
          <div class="mes-navegacao">
            <button onclick="mesAnterior()">← Anterior</button>
            <div class="mes-titulo" id="mesTitulo"></div>
            <button onclick="mesProximo()">Próximo →</button>
          </div>

          <div class="calendario-wrapper">
            <div class="dias-semana">
              <div class="dia-semana">Dom</div>
              <div class="dia-semana">Seg</div>
              <div class="dia-semana">Ter</div>
              <div class="dia-semana">Qua</div>
              <div class="dia-semana">Qui</div>
              <div class="dia-semana">Sex</div>
              <div class="dia-semana">Sab</div>
            </div>

            <div class="dias-grid" id="diasGrid"></div>
          </div>

          <div class="legenda">
            <div class="legenda-item">
              <div class="legenda-cor" style="background: #ecfdf5; border-color: #10b981;"></div>
              <span>Cerimonialistas disponíveis</span>
            </div>
            <div class="legenda-item">
              <div class="legenda-cor" style="background: hsl(var(--background)); border-color: hsl(var(--border));"></div>
              <span>Sem informações</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Modal de Cerimonialistas -->
  <div class="modal-overlay" id="cerimonialistas-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalTitulo">Cerimonialistas Disponíveis</h3>
        <button class="modal-close" onclick="fecharModal()">&times;</button>
      </div>
      <div class="cerimonialistas-lista" id="cerimonalistasList"></div>
    </div>
  </div>

  <!-- Modal de Fornecedores -->
  <div class="modal-overlay" id="fornecedores-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Fornecedores</h3>
        <button class="modal-close" onclick="fecharFornecedoresModal()">&times;</button>
      </div>
      <div class="cerimonialistas-lista" id="fornecedoresList"></div>
    </div>
  </div>

  <script>
    let mesAtual = new Date();
    let disponivelPorData = {};

    function inicializarCalendario() {
      renderizarCalendario();
      carregarDisponibilidades();
    }

    function renderizarCalendario() {
      const ano = mesAtual.getFullYear();
      const mes = mesAtual.getMonth();
      
      // Atualizar título
      const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                     'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
      document.getElementById('mesTitulo').textContent = `${meses[mes]} ${ano}`;

      // Calcular dias
      const primeiroDia = new Date(ano, mes, 1).getDay();
      const ultimoDia = new Date(ano, mes + 1, 0).getDate();
      
      const diasGrid = document.getElementById('diasGrid');
      diasGrid.innerHTML = '';

      // Dias vazios no início
      for (let i = 0; i < primeiroDia; i++) {
        const div = document.createElement('div');
        div.className = 'dia vazio';
        diasGrid.appendChild(div);
      }

      // Dias do mês
      const hoje = new Date();
      hoje.setHours(0, 0, 0, 0);

      for (let dia = 1; dia <= ultimoDia; dia++) {
        const dataObj = new Date(ano, mes, dia);
        const dataStr = `${ano}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
        
        const div = document.createElement('div');
        div.className = 'dia';
        
        // Verificar se é passado
        if (dataObj < hoje) {
          div.classList.add('passado');
        } else {
          // Verificar se há disponíveis
          if (disponivelPorData[dataStr]) {
            div.classList.add('com-disponiveis');
          }
          div.onclick = () => abrirModal(dataStr);
        }
        
        div.textContent = dia;
        diasGrid.appendChild(div);
      }
    }

    function carregarDisponibilidades() {
      // Carregar dados de disponibilidade para próximos 120 dias
      const hoje = new Date();
      const ate = new Date();
      ate.setDate(ate.getDate() + 120);

      fetch('../api/get-cerimonialistas-disponiveis.php?range=true&ate=' + ate.toISOString().split('T')[0])
        .then(response => response.json())
        .then(data => {
          if (Array.isArray(data)) {
            // Agrupar por data
            data.forEach(item => {
              const data_key = item.data_casamento || Object.keys(item)[0];
              if (!disponivelPorData[data_key]) {
                disponivelPorData[data_key] = [];
              }
              disponivelPorData[data_key].push(item);
            });
            renderizarCalendario();
          }
        })
        .catch(error => console.error('Erro ao carregar disponibilidades:', error));
    }

    function mesAnterior() {
      mesAtual.setMonth(mesAtual.getMonth() - 1);
      renderizarCalendario();
    }

    function mesProximo() {
      mesAtual.setMonth(mesAtual.getMonth() + 1);
      renderizarCalendario();
    }

    function abrirModal(dataStr) {
      const [ano, mes, dia] = dataStr.split('-');
      document.getElementById('modalTitulo').textContent = `Cerimonialistas disponíveis em ${dia}/${mes}/${ano}`;

      fetch(`../api/get-cerimonialistas-disponiveis.php?data=${dataStr}`)
        .then(response => response.json())
        .then(data => {
          const lista = document.getElementById('cerimonalistasList');
          lista.innerHTML = '';
          
          if (Array.isArray(data) && data.length > 0) {
            data.forEach(cerimo => {
              const item = document.createElement('div');
              item.className = 'cerimonialista-item';
              item.innerHTML = `
                <img src="../user/fotos/${cerimo.foto_perfil || 'default.png'}" 
                     alt="${cerimo.nome}" 
                     class="cerimonialista-foto"
                     onerror="this.src='../user/fotos/default.png'">
                <div class="cerimonialista-info">
                  <h4>${cerimo.nome}</h4>
                  <div class="cerimonialista-rating">
                    <span class="stars">★★★★★</span>
                    <span>${parseFloat(cerimo.avaliacao || 0).toFixed(1)}/5</span>
                  </div>
                  <div class="cerimonialista-bio">
                    ${cerimo.bio ? cerimo.bio.substring(0, 100) : 'Cerimonialista profissional'}
                  </div>
                  <div class="cerimonialista-acoes">
                    <button class="btn-fornecedores" onclick="abrirFornecedores(${cerimo.id_usuario})">
                      Ver Fornecedores
                    </button>
                    <form method="POST" style="flex: 1; display: flex;">
                      <input type="hidden" name="id_cerimonialista" value="${cerimo.id_usuario}">
                      <input type="hidden" name="data_casamento" value="${dataStr}">
                      <button type="submit" class="btn-escolher">Escolher</button>
                    </form>
                  </div>
                </div>
              `;
              lista.appendChild(item);
            });
          } else {
            lista.innerHTML = '<div class="vazio-estado">Nenhum cerimonialista disponível nesta data</div>';
          }

          document.getElementById('cerimonialistas-modal').classList.add('active');
        })
        .catch(error => {
          console.error('Erro:', error);
          document.getElementById('cerimonalistasList').innerHTML = 
            '<div class="vazio-estado">Erro ao carregar cerimonialistas</div>';
          document.getElementById('cerimonialistas-modal').classList.add('active');
        });
    }

    function fecharModal() {
      document.getElementById('cerimonialistas-modal').classList.remove('active');
    }

    function abrirFornecedores(idCerimonialista) {
      fetch(`../api/get-fornecedores-cerimonialista.php?id=${idCerimonialista}`)
        .then(response => response.json())
        .then(data => {
          const lista = document.getElementById('fornecedoresList');
          lista.innerHTML = '';
          
          if (Array.isArray(data) && data.length > 0) {
            data.forEach(fornecedor => {
              const item = document.createElement('div');
              item.className = 'cerimonialista-item';
              item.innerHTML = `
                <div class="cerimonialista-info">
                  <h4>${fornecedor.nome_fornecedor}</h4>
                  <div class="cerimonialista-rating">
                    <span>${fornecedor.categoria}</span>
                  </div>
                  <div class="cerimonialista-bio">
                    Avaliação: ${parseFloat(fornecedor.avaliacao || 0).toFixed(1)}/5
                  </div>
                </div>
              `;
              lista.appendChild(item);
            });
          } else {
            lista.innerHTML = '<div class="vazio-estado">Nenhum fornecedor disponível</div>';
          }

          document.getElementById('fornecedores-modal').classList.add('active');
        })
        .catch(error => console.error('Erro:', error));
    }

    function fecharFornecedoresModal() {
      document.getElementById('fornecedores-modal').classList.remove('active');
    }

    // Fechar modal ao clicar fora
    document.getElementById('cerimonialistas-modal').addEventListener('click', function(e) {
      if (e.target === this) fecharModal();
    });

    document.getElementById('fornecedores-modal').addEventListener('click', function(e) {
      if (e.target === this) fecharFornecedoresModal();
    });

    // Inicializar
    inicializarCalendario();
  </script>
</body>

</html>
