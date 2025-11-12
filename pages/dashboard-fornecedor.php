<?php
session_start();
require_once "../config/conexao.php";

if (!isset($_SESSION["usuario_id"])) {
  header("Location: ../user/login.php");
  exit;
}

$usuario_id = (int) $_SESSION["usuario_id"];
$fornecedor = null;
$servicos = [];
$contratos = [];
$clientes = [];
$resumo = [];

try {
  // Get supplier data
  $sql = "SELECT nome_fornecedor, email_fornecedor, telefone_fornecedor, tipo_servico, valor_servico, foto_perfil, endereco_fornecedor FROM fornecedores WHERE id_usuario = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get services/items offered
  $sql = "SELECT id_item, descricao_item, valor_item, status_item FROM itens WHERE id_fornecedor IN (SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?) LIMIT 10";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get contracts
  $sql = "SELECT COUNT(*) as total_contratos FROM contratos WHERE id_fornecedor IN (SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $contratos_count = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get recent contracts
  $sql = "SELECT id_contrato, data_contrato, valor_contrato, status_contrato FROM contratos WHERE id_fornecedor IN (SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?) ORDER BY data_contrato DESC LIMIT 5";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get clients count
  $sql = "SELECT COUNT(DISTINCT id_usuario) as total_clientes FROM itens WHERE id_fornecedor IN (SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $clientes_count = $stmt->fetch(PDO::FETCH_ASSOC);

  // Get revenue
  $sql = "SELECT SUM(valor_contrato) as receita_total FROM contratos WHERE id_fornecedor IN (SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?) AND status_contrato = 'pago'";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id]);
  $receita = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  error_log("Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Fornecedor | Planner de Sonhos</title>
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

    .item-row {
      padding: 1rem;
      background: hsl(var(--muted) / 0.3);
      border-radius: 0.5rem;
      margin-bottom: 0.75rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .item-row h4 {
      margin: 0 0 0.25rem 0;
      font-size: 0.95rem;
      color: hsl(var(--foreground));
    }

    .item-row p {
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

    .status-pendente {
      background: hsl(38 92% 50% / 0.1);
      color: hsl(38 92% 50%);
    }

    .status-confirmado {
      background: hsl(142 71% 45% / 0.1);
      color: hsl(142 71% 45%);
    }

    .status-pago {
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

    .dropdown {
      position: relative;
      display: inline-block;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
      z-index: 1;
    }

    .dropdown-menu a {
      color: black;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
    }

    .dropdown-menu a:hover {
      background-color: #f1f1f1;
    }

    .dropdown:hover .dropdown-menu {
      display: block;
    }

    .dropdown-toggle {
      cursor: pointer;
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
              <a href="mensagens.php">Mensagens</a>
              <a href="avaliacoes.php">Avaliações</a>
              <a href="notificacoes.php">Notificações</a>
              <a href="historico.php">Histórico</a>
              <a href="disponibilidade.php">Minha Disponibilidade</a>
              <a href="candidaturas.php">Candidaturas</a>
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
      <h1 class="page-title">Dashboard - Fornecedor</h1>
      <p class="page-description">Gerenciamento de seus serviços e contratos.</p>
    </div>

    <?php if ($fornecedor): ?>
      <div class="dashboard-grid">
        <!-- Total Clients -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Total de Clientes</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M17 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="dashboard-card-value"><?php echo $clientes_count['total_clientes'] ?? 0; ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Clientes únicos</p>
        </div>

        <!-- Total Contracts -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Contratos</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="12" y1="19" x2="12" y2="13"></line>
              <line x1="9" y1="16" x2="15" y2="16"></line>
            </svg>
          </div>
          <div class="dashboard-card-value"><?php echo $contratos_count['total_contratos'] ?? 0; ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Contratos ativos</p>
        </div>

        <!-- Revenue -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Receita</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(142 71% 45%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="dashboard-card-value">R$ <?php echo number_format($receita['receita_total'] ?? 0, 2, ',', '.'); ?></div>
          <p style="color: hsl(var(--muted-foreground)); margin: 0;">Total recebido</p>
        </div>

        <!-- Supplier Info -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Informações</span>
            <svg style="width: 1.5rem; height: 1.5rem; color: hsl(var(--primary));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div class="stat-row">
            <span class="stat-label">Tipo</span>
            <span class="stat-value"><?php echo htmlspecialchars($fornecedor['tipo_servico'] ?? 'N/A'); ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Valor Padrão</span>
            <span class="stat-value">R$ <?php echo number_format($fornecedor['valor_servico'] ?? 0, 2, ',', '.'); ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Telefone</span>
            <span class="stat-value"><?php echo htmlspecialchars($fornecedor['telefone_fornecedor'] ?? 'N/A'); ?></span>
          </div>
        </div>

        <!-- Recent Contracts -->
        <div class="dashboard-card full-width">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Contratos Recentes</span>
          </div>
          <?php if (count($contratos) > 0): ?>
            <?php foreach ($contratos as $contrato): ?>
              <div class="item-row">
                <div>
                  <h4>Contrato #<?php echo $contrato['id_contrato']; ?></h4>
                  <p><?php 
                    $data = new DateTime($contrato['data_contrato']);
                    echo $data->format('d/m/Y');
                  ?></p>
                </div>
                <div style="text-align: right;">
                  <p style="margin: 0; font-weight: 600;">R$ <?php echo number_format($contrato['valor_contrato'], 2, ',', '.'); ?></p>
                  <span class="status-badge status-<?php echo str_replace(' ', '', strtolower($contrato['status_contrato'])); ?>">
                    <?php echo ucfirst($contrato['status_contrato']); ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhum contrato recente</p>
          <?php endif; ?>
        </div>

        <!-- Services Offered -->
        <div class="dashboard-card full-width">
          <div class="dashboard-card-header">
            <span class="dashboard-card-title">Serviços Oferecidos</span>
            <a href="itens.php" class="btn-secondary">Adicionar</a>
          </div>
          <?php if (count($servicos) > 0): ?>
            <?php foreach ($servicos as $servico): ?>
              <div class="item-row">
                <div>
                  <h4><?php echo htmlspecialchars($servico['descricao_item']); ?></h4>
                </div>
                <div style="text-align: right;">
                  <p style="margin: 0; font-weight: 600;">R$ <?php echo number_format($servico['valor_item'], 2, ',', '.'); ?></p>
                  <span class="status-badge status-<?php echo str_replace(' ', '', strtolower($servico['status_item'])); ?>">
                    <?php echo ucfirst($servico['status_item']); ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem;">Nenhum serviço cadastrado</p>
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
