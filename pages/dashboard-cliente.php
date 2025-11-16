<?php
session_start();
require_once "../config/conexao.php";

if (!isset($_SESSION["usuario_id"])) {
  header("Location: ../user/login.php");
  exit;
}

$usuario_id = (int) $_SESSION["usuario_id"];
$usuario = null;
$eventos = [];
$orcamento = [];
$tarefas = [];
$fornecedores = [];
$resumo = [];

try {
  // Get user data
  $sql = "SELECT nome, nome_conjuge, email, telefone, foto_perfil, local_casamento, tipo_cerimonia, quantidade_convidados, orcamento_total FROM usuarios WHERE id_usuario = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get next events
  $sql = "SELECT id_evento, nome_evento, data_evento, local_evento, status_evento FROM eventos WHERE id_usuario = ? ORDER BY data_evento ASC LIMIT 5";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $sql = "SELECT SUM(quantidade * valor_unitario) as total_orcado FROM orcamentos WHERE id_usuario = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get total spent
  $sql = "SELECT SUM(quantidade * valor_unitario) as total_gasto FROM orcamentos WHERE id_usuario = ? AND status_item = 'pago'";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $gastos = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get tasks pending
  $sql = "SELECT COUNT(*) as total_tarefas FROM tarefas WHERE id_usuario = ? AND status_tarefa != 'concluida'";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $tarefas_count = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get recent tasks
  $sql = "SELECT id_tarefa, descricao_tarefa, data_vencimento, prioridade FROM tarefas WHERE id_usuario = ? AND status_tarefa != 'concluida' ORDER BY data_vencimento ASC LIMIT 3";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get suppliers
  $sql = "SELECT f.id_fornecedor, f.nome_fornecedor, f.tipo_servico, f.valor_servico, f.telefone_fornecedor FROM fornecedores f JOIN itens i ON f.id_fornecedor = i.id_fornecedor WHERE i.id_usuario = ? GROUP BY f.id_fornecedor LIMIT 5";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $total_orcado = $orcamento['total_orcado'] ?? 0;
  $total_gasto = $gastos['total_gasto'] ?? 0;
  $disponivel = $total_orcado - $total_gasto;
  $percentual_gasto = $total_orcado > 0 ? ($total_gasto / $total_orcado) * 100 : 0;

} catch (PDOException $e) {
  error_log("Error: " . $e->getMessage());
  $error_message = "Erro ao carregar dados do dashboard";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Cliente | Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }

    .dashboard-card {
      background: hsl(var(--card-background, 0, 0%, 100%));
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }

    .dashboard-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
    }

    .dashboard-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }

    .dashboard-card-title {
      font-size: 1rem;
      font-weight: 600;
      color: hsl(var(--muted-foreground));
    }

    .dashboard-card-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: hsl(var(--primary));
      margin-bottom: 0.5rem;
    }

    .progress-bar {
      height: 8px;
      background: hsl(var(--muted));
      border-radius: 9999px;
      overflow: hidden;
      margin: 1rem 0;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, hsl(var(--primary)), hsl(var(--secondary)));
      border-radius: 9999px;
      transition: width 0.3s ease;
    }

    .event-item {
      padding: 1rem;
      background: hsl(var(--muted) / 0.3);
      border-radius: 0.5rem;
      margin-bottom: 0.75rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .event-item h4 {
      margin: 0 0 0.25rem 0;
      font-size: 0.95rem;
      color: hsl(var(--foreground));
    }

    .event-item p {
      margin: 0;
      font-size: 0.8rem;
      color: hsl(var(--muted-foreground));
    }

    .status-badge {
      display: inline-block;
      padding: 0.35rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-pending {
      background: hsl(38 92% 50% / 0.1);
      color: hsl(38 92% 50%);
    }

    .status-confirmed {
      background: hsl(142 71% 45% / 0.1);
      color: hsl(142 71% 45%);
    }

    .status-completed {
      background: hsl(197 71% 53% / 0.1);
      color: hsl(197 71% 53%);
    }

    .stat-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid hsl(var(--border));
    }

    .stat-row:last-child {
      border-bottom: none;
    }

    .stat-label {
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
    }

    .stat-value {
      font-weight: 600;
      color: hsl(var(--foreground));
    }

    .btn-secondary {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      background: hsl(var(--muted));
      color: hsl(var(--foreground));
      text-decoration: none;
      border-radius: 0.5rem;
      font-weight: 600;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
    }

    .btn-secondary:hover {
      background: hsl(var(--muted) / 0.8);
      transform: translateY(-2px);
    }

    .full-width {
      grid-column: 1 / -1;
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
        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <div class="dropdown">
            <a href="funcionalidades.php" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
            <div class="dropdown-menu">
              <a href="calendario.php">Calendário</a>
              <a href="orcamento.php">Orçamento</a>
              <a href="itens.php">Serviços</a>
              <a href="gestao-contratos.php">Gestão de Contratos</a>
              <a href="tarefas.php">Lista de Tarefas</a>
              <a href="avaliacoes.php">Avaliações</a>
              <a href="historico.php">Histórico</a>
              <a href="configurar-orcamento.php">Alertas Orçamento</a>
            </div>
          </div>
          <a href="../user/perfil.php" class="nav-link">Perfil</a>
          <form method="post" style="margin:0; display: inline-block;">
            <button class="btn-outline" type="submit" name="logout">Sair</button>
          </form>
        </nav>
      </div>
    </div>
  </header>

  <main class="page-content container" style="margin-top: 5rem;">
    <div class="page-header">
      <h1 class="page-title">Dashboard - Cliente</h1>
      <p class="page-description">Visão geral do seu casamento e progresso do planejamento.</p>
    </div>

    <?php if ($usuario): ?>
      <div class="dashboard-grid">
        <!-- Budget Overview Card -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Orçamento Total</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="dashboard-card-value">R$ <?php echo number_format($usuario['orcamento_total'] ?? 0, 2, ',', '.'); ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Limite de gastos definido</p>
        </div>

        <!-- Spent Card -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Já Gasto</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--destructive));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <polyline points="23 6 13.5 15.5 8 10 1 17"></polyline>
              <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
          </div>
          <div class="dashboard-card-value">R$ <?php echo number_format($total_gasto, 2, ',', '.'); ?></div>
          <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo min($percentual_gasto, 100); ?>%"></div>
          </div>
          <div class="stat-row">
            <span class="stat-label">Percentual</span>
            <span class="stat-value"><?php echo number_format($percentual_gasto, 1, ',', '.'); ?>%</span>
          </div>
        </div>

        <!-- Available Card -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Disponível</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(142 71% 45%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <polyline points="22 12 18 16 14 12"></polyline>
              <polyline points="2 12 6 8 10 12"></polyline>
              <line x1="6" y1="9" x2="18" y2="9"></line>
            </svg>
          </div>
          <div class="dashboard-card-value">R$ <?php echo number_format($disponivel, 2, ',', '.'); ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Saldo restante para gastar</p>
        </div>

        <!-- Wedding Info Card -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Detalhes do Evento</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="stat-row">
            <span class="stat-label">Local</span>
            <span class="stat-value"><?php echo htmlspecialchars($usuario['local_casamento'] ?? 'N/A'); ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Tipo</span>
            <span class="stat-value"><?php echo htmlspecialchars($usuario['tipo_cerimonia'] ?? 'N/A'); ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Convidados</span>
            <span class="stat-value"><?php echo $usuario['quantidade_convidados'] ?? 0; ?></span>
          </div>
        </div>

        <!-- Upcoming Events -->
        <div class="dashboard-card full-width">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Próximos Eventos</span>
            <a href="calendario.php" class="btn-secondary">Ver Calendário</a>
          </div>
          <?php if (count($eventos) > 0): ?>
            <?php foreach ($eventos as $evento): ?>
              <div class="event-item">
                <div>
                  <h4><?php echo htmlspecialchars($evento['nome_evento']); ?></h4>
                  <p><?php 
                    $data = new DateTime($evento['data_evento']);
                    echo $data->format('d/m/Y H:i');
                  ?></p>
                </div>
                <span class="status-badge status-<?php echo $evento['status_evento']; ?>">
                  <?php echo ucfirst($evento['status_evento']); ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhum evento próximo</p>
          <?php endif; ?>
        </div>

        <!-- Pending Tasks -->
        <div class="dashboard-card full-width">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Tarefas Pendentes (<?php echo $tarefas_count['total_tarefas'] ?? 0; ?>)</span>
            <a href="tarefas.php" class="btn-secondary">Ver Todas</a>
          </div>
          <?php if (count($tarefas) > 0): ?>
            <?php foreach ($tarefas as $tarefa): ?>
              <div class="event-item">
                <div>
                  <h4><?php echo htmlspecialchars($tarefa['descricao_tarefa']); ?></h4>
                  <p>Prazo: <?php 
                    $data = new DateTime($tarefa['data_vencimento']);
                    echo $data->format('d/m/Y');
                  ?></p>
                </div>
                <span class="status-badge status-pending">
                  <?php echo ucfirst($tarefa['prioridade']); ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhuma tarefa pendente</p>
          <?php endif; ?>
        </div>

        <!-- Suppliers -->
        <div class="dashboard-card full-width">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Fornecedores Contratados</span>
            <a href="cerimonialista.php" class="btn-secondary">Adicionar</a>
          </div>
          <?php if (count($fornecedores) > 0): ?>
            <?php foreach ($fornecedores as $fornecedor): ?>
              <div class="event-item">
                <div>
                  <h4><?php echo htmlspecialchars($fornecedor['nome_fornecedor']); ?></h4>
                  <p><?php echo htmlspecialchars($fornecedor['tipo_servico']); ?></p>
                </div>
                <div style="text-align: right;">
                  <p style="margin: 0; font-weight: 600;">R$ <?php echo number_format($fornecedor['valor_servico'], 2, ',', '.'); ?></p>
                  <p style="margin: 0; font-size: 0.8rem; color: hsl(var(--muted-foreground));"><?php echo htmlspecialchars($fornecedor['telefone_fornecedor']); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhum fornecedor contratado</p>
          <?php endif; ?>
        </div>
      </div>

    <?php endif; ?>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
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
    </div>
  </footer>

  <script>
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");
      mobileMenu.classList.toggle("active");
      hamburgerBtn.classList.toggle("hamburger-active");
    }
  </script>
</body>
</html>
