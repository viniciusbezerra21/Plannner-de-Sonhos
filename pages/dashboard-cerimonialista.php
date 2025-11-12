<?php
session_start();
require_once "../config/conexao.php";

if (!isset($_SESSION["usuario_id"])) {
  header("Location: ../user/login.php");
  exit;
}

$usuario_id = (int) $_SESSION["usuario_id"];
$cerimonialista = null;
$eventos = [];
$clientes = [];
$fornecedores = [];
$tarefas_pendentes = [];

try {
  // Get cerimonialista data
  $sql = "SELECT nome, email, telefone, foto_perfil FROM usuarios WHERE id_usuario = ? AND tipo_usuario = 'cerimonialista'";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $cerimonialista = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get upcoming events
  $sql = "SELECT id_evento, nome_evento, data_evento, local_evento, status_evento, quantidade_convidados FROM eventos WHERE id_cerimonialista = ? AND data_evento >= CURDATE() ORDER BY data_evento ASC LIMIT 5";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get total clients
  $sql = "SELECT COUNT(DISTINCT id_usuario) as total_clientes FROM eventos WHERE id_cerimonialista = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $clientes_count = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get suppliers managed
  $sql = "SELECT DISTINCT f.id_fornecedor, f.nome_fornecedor, f.tipo_servico FROM fornecedores f JOIN itens i ON f.id_fornecedor = i.id_fornecedor WHERE i.id_usuario IN (SELECT id_usuario FROM eventos WHERE id_cerimonialista = ?) LIMIT 5";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get pending tasks
  $sql = "SELECT id_tarefa, descricao_tarefa, data_vencimento, prioridade FROM tarefas WHERE id_usuario = ? AND status_tarefa != 'concluida' ORDER BY data_vencimento ASC LIMIT 3";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $tarefas_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get events count
  $sql = "SELECT COUNT(*) as total_eventos FROM eventos WHERE id_cerimonialista = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $eventos_count = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  error_log("Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Cerimonialista | Planner de Sonhos</title>
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

    .event-item {
      padding: 1rem;
      background: hsl(var(--muted) / 0.3);
      border-radius: 0.5rem;
      margin-bottom: 0.75rem;
      border-left: 4px solid hsl(var(--primary));
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
      margin-top: 0.5rem;
    }

    .status-planejamento {
      background: hsl(38 92% 50% / 0.1);
      color: hsl(38 92% 50%);
    }

    .status-confirmado {
      background: hsl(142 71% 45% / 0.1);
      color: hsl(142 71% 45%);
    }

    .status-realizado {
      background: hsl(197 71% 53% / 0.1);
      color: hsl(197 71% 53%);
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
      font-size: 0.9rem;
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
          <a href="calendario.php" class="nav-link">Calendário</a>
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
      <h1 class="page-title">Dashboard - Cerimonialista</h1>
      <p class="page-description">Gerencie seus eventos, clientes e fornecedores.</p>
    </div>

    <?php if ($cerimonialista): ?>
      <div class="dashboard-grid">
        <!-- Total Events -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Total de Eventos</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="dashboard-card-value"><?php echo $eventos_count['total_eventos'] ?? 0; ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Casamentos gerenciados</p>
        </div>

        <!-- Total Clients -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Total de Clientes</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="dashboard-card-value"><?php echo $clientes_count['total_clientes'] ?? 0; ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Clientes ativos</p>
        </div>

        <!-- Suppliers Network -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Fornecedores</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"></path>
            </svg>
          </div>
          <div class="dashboard-card-value"><?php echo count($fornecedores); ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Na sua rede</p>
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
                <h4><?php echo htmlspecialchars($evento['nome_evento']); ?></h4>
                <p>Data: <?php 
                  $data = new DateTime($evento['data_evento']);
                  echo $data->format('d/m/Y');
                ?></p>
                <p>Local: <?php echo htmlspecialchars($evento['local_evento']); ?></p>
                <p>Convidados: <?php echo $evento['quantidade_convidados']; ?></p>
                <span class="status-badge status-<?php echo str_replace(' ', '', strtolower($evento['status_evento'])); ?>">
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
            <span class="dashboard-card-title">Tarefas Pendentes</span>
            <a href="tarefas.php" class="btn-secondary">Ver Todas</a>
          </div>
          <?php if (count($tarefas_pendentes) > 0): ?>
            <?php foreach ($tarefas_pendentes as $tarefa): ?>
              <div class="event-item">
                <h4><?php echo htmlspecialchars($tarefa['descricao_tarefa']); ?></h4>
                <p>Prazo: <?php 
                  $data = new DateTime($tarefa['data_vencimento']);
                  echo $data->format('d/m/Y');
                ?></p>
                <span class="status-badge status-<?php echo str_replace(' ', '', strtolower($tarefa['prioridade'])); ?>">
                  <?php echo ucfirst($tarefa['prioridade']); ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhuma tarefa pendente</p>
          <?php endif; ?>
        </div>

        <!-- Suppliers Network -->
        <div class="dashboard-card full-width">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Rede de Fornecedores</span>
            <a href="cerimonialista.php" class="btn-secondary">Adicionar</a>
          </div>
          <?php if (count($fornecedores) > 0): ?>
            <?php foreach ($fornecedores as $fornecedor): ?>
              <div class="event-item">
                <h4><?php echo htmlspecialchars($fornecedor['nome_fornecedor']); ?></h4>
                <p><?php echo htmlspecialchars($fornecedor['tipo_servico']); ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhum fornecedor na rede</p>
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
</body>
</html>
