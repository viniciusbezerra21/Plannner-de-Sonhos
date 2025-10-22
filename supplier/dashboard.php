<?php
session_start();
require_once "../config/conexao.php";

// Verificar se o fornecedor está logado
if (!isset($_SESSION['fornecedor_id'])) {
    header("Location: login.php");
    exit;
}

$fornecedor_id = (int)$_SESSION['fornecedor_id'];

// Fetch supplier info
try {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
    $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fornecedor) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // Get total items
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM itens WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
    $total_itens = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total packages
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pacotes WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
    $total_pacotes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->prepare("
        SELECT AVG(o.avaliacao) as media_avaliacao
        FROM orcamentos o
        INNER JOIN itens i ON o.item = i.nome_item
        WHERE i.id_fornecedor = ? AND o.avaliacao > 0
    ");
    $stmt->execute([$fornecedor_id]);
    $rating_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $avaliacao_media = $rating_result['media_avaliacao'] ? number_format($rating_result['media_avaliacao'], 1) : 'N/A';

    // Get recent items
    $stmt = $pdo->prepare("SELECT * FROM itens WHERE id_fornecedor = ? ORDER BY id_item DESC LIMIT 5");
    $stmt->execute([$fornecedor_id]);
    $itens_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent packages
    $stmt = $pdo->prepare("SELECT * FROM pacotes WHERE id_fornecedor = ? ORDER BY id_pacote DESC LIMIT 5");
    $stmt->execute([$fornecedor_id]);
    $pacotes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_itens = 0;
    $total_pacotes = 0;
    $avaliacao_media = 'N/A';
    $itens_recentes = [];
    $pacotes_recentes = [];
}

// Handle logout
if (isset($_POST['logout'])) {
    setcookie("lembrar_me_fornecedor", "", time() - 3600, "/");
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .dashboard-container {
      display: grid;
      grid-template-columns: 250px 1fr;
      gap: 2rem;
      margin-top: 2rem;
      min-height: calc(100vh - 200px);
    }

    .sidebar {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1.5rem;
      height: fit-content;
      position: sticky;
      top: 100px;
    }

    .sidebar h3 {
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: hsl(var(--muted-foreground));
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .sidebar-menu {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .sidebar-item {
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      text-decoration: none;
      color: hsl(var(--foreground));
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.9rem;
    }

    .sidebar-item:hover {
      background: hsl(var(--muted));
      border-left-color: hsl(var(--primary));
    }

    .sidebar-item.active {
      background: hsl(var(--primary) / 0.1);
      border-left-color: hsl(var(--primary));
      color: hsl(var(--primary));
      font-weight: 600;
    }

    .sidebar-item svg {
      width: 18px;
      height: 18px;
      stroke-width: 2;
    }

    .sidebar-divider {
      height: 1px;
      background: hsl(var(--border));
      margin: 1rem 0;
    }

    .main-content {
      display: flex;
      flex-direction: column;
      gap: 2rem;
    }

    .welcome-card {
      background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--secondary)));
      border-radius: 1rem;
      padding: 2rem;
      color: white;
    }

    .welcome-card h1 {
      font-size: 1.75rem;
      margin-bottom: 0.5rem;
    }

    .welcome-card p {
      opacity: 0.9;
      margin-bottom: 1.5rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
    }

    .stat-card {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 1.5rem;
      text-align: center;
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: hsl(var(--primary) / 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }

    .stat-icon svg {
      width: 24px;
      height: 24px;
      stroke: hsl(var(--primary));
    }

    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: hsl(var(--foreground));
      margin-bottom: 0.5rem;
    }

    .stat-label {
      font-size: 0.9rem;
      color: hsl(var(--muted-foreground));
    }

    .section {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .section h2 {
      margin-top: 0;
      margin-bottom: 1.5rem;
      color: hsl(var(--foreground));
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .section h2 svg {
      width: 24px;
      height: 24px;
      stroke: hsl(var(--primary));
    }

    .items-table {
      width: 100%;
      border-collapse: collapse;
    }

    .items-table thead {
      background: hsl(var(--muted));
      border-bottom: 2px solid hsl(var(--border));
    }

    .items-table th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: hsl(var(--foreground));
      font-size: 0.9rem;
    }

    .items-table td {
      padding: 1rem;
      border-bottom: 1px solid hsl(var(--border));
      color: hsl(var(--foreground));
    }

    .items-table tr:hover {
      background: hsl(var(--muted) / 0.5);
    }

    .item-price {
      color: hsl(var(--primary));
      font-weight: 600;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: hsl(var(--muted-foreground));
    }

    .empty-state svg {
      width: 48px;
      height: 48px;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .action-buttons {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }

    .action-buttons a,
    .action-buttons button {
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
    }

    @media (max-width: 768px) {
      .dashboard-container {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: static;
      }

      .sidebar-menu {
        flex-direction: row;
        flex-wrap: wrap;
      }

      .sidebar-item {
        flex: 1;
        min-width: 120px;
        text-align: center;
        justify-content: center;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .welcome-card h1 {
        font-size: 1.25rem;
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

        <nav class="nav">
          <a href="dashboard.php" class="nav-link">Dashboard</a>
          <a href="profile.php" class="nav-link">Meu Perfil</a>
          <form method="post" style="margin: 0;">
            <button type="submit" name="logout" class="btn-primary" style="border: none; cursor: pointer;">Sair</button>
          </form>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="dashboard-container">
          <!-- Sidebar -->
          <aside class="sidebar">
            <h3>Menu</h3>
            <div class="sidebar-menu">
              <a href="dashboard.php" class="sidebar-item active">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect x="3" y="3" width="7" height="7"></rect>
                  <rect x="14" y="3" width="7" height="7"></rect>
                  <rect x="14" y="14" width="7" height="7"></rect>
                  <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
              </a>
              <a href="profile.php" class="sidebar-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Meu Perfil
              </a>
              <a href="items.php" class="sidebar-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                  <polyline points="9,11 12,14 15,11"></polyline>
                  <line x1="12" y1="2" x2="12" y2="14"></line>
                </svg>
                Itens/Serviços
              </a>
              <a href="packages.php" class="sidebar-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                  <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                  <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Pacotes
              </a>
            </div>
          </aside>

          <!-- Main Content -->
          <div class="main-content">
            <div class="welcome-card">
              <h1>Bem-vindo, <?php echo htmlspecialchars($fornecedor['nome_fornecedor']); ?>!</h1>
              <p>Gerencie seus serviços, itens e pacotes de forma eficiente.</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
              <div class="stat-card">
                <div class="stat-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2v20m0 0l-7-7m7 7l7-7" />
                  </svg>
                </div>
                <div class="stat-number"><?php echo $total_itens; ?></div>
                <div class="stat-label">Itens/Serviços</div>
              </div>

              <div class="stat-card">
                <div class="stat-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                  </svg>
                </div>
                <div class="stat-number"><?php echo $total_pacotes; ?></div>
                <div class="stat-label">Pacotes</div>
              </div>

              <!-- Display calculated average rating from orcamentos -->
              <div class="stat-card">
                <div class="stat-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
                <div class="stat-number"><?php echo $avaliacao_media; ?></div>
                <div class="stat-label">Avaliação Média</div>
              </div>
            </div>

            <!-- Recent Items -->
            <?php if (!empty($itens_recentes)): ?>
            <div class="section">
              <h2>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                </svg>
                Itens/Serviços Recentes
              </h2>
              <table class="items-table">
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th>Valor Unitário</th>
                    <th>Data de Criação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($itens_recentes as $item): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($item['nome_item']); ?></td>
                    <td class="item-price">R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($item['data_criacao'] ?? 'now')); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <div class="action-buttons">
                <a href="items.php" class="btn-primary">Ver Todos os Itens</a>
                <a href="items.php?action=add" class="btn-outline">Adicionar Novo Item</a>
              </div>
            </div>
            <?php else: ?>
            <div class="section">
              <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                </svg>
                <h3>Nenhum item cadastrado</h3>
                <p>Comece adicionando seus primeiros itens/serviços</p>
                <a href="items.php?action=add" class="btn-primary" style="display: inline-block; margin-top: 1rem;">Adicionar Item</a>
              </div>
            </div>
            <?php endif; ?>

            <!-- Recent Packages -->
            <?php if (!empty($pacotes_recentes)): ?>
            <div class="section">
              <h2>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Pacotes Recentes
              </h2>
              <table class="items-table">
                <thead>
                  <tr>
                    <th>Nome do Pacote</th>
                    <th>Valor Total</th>
                    <th>Itens</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pacotes_recentes as $pacote): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($pacote['nome_pacote']); ?></td>
                    <td class="item-price">R$ <?php echo number_format($pacote['valor_total'], 2, ',', '.'); ?></td>
                    <td><?php echo $pacote['quantidade_itens'] ?? 0; ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <div class="action-buttons">
                <a href="packages.php" class="btn-primary">Ver Todos os Pacotes</a>
                <a href="packages.php?action=add" class="btn-outline">Criar Novo Pacote</a>
              </div>
            </div>
            <?php else: ?>
            <div class="section">
              <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                <h3>Nenhum pacote cadastrado</h3>
                <p>Crie pacotes combinando seus itens/serviços</p>
                <a href="packages.php?action=add" class="btn-primary" style="display: inline-block; margin-top: 1rem;">Criar Pacote</a>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
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
      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>
</body>

</html>
