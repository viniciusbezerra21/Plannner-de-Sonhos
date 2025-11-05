<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";

if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
  $cookieUserId = (int) $_COOKIE[$cookieName];
  if ($cookieUserId > 0) {
    $chk = $pdo->prepare("SELECT id_usuario, nome, cargo FROM usuarios WHERE id_usuario = ?");
    $chk->execute([$cookieUserId]);
    $u = $chk->fetch(PDO::FETCH_ASSOC);
    if ($u) {
      $_SESSION['usuario_id'] = (int) $u['id_usuario'];
      $_SESSION['nome'] = $u['nome'];
      $_SESSION['cargo'] = $u['cargo'] ?? 'cliente';
    } else {
      setcookie($cookieName, "", time() - 3600, "/");
    }
  }
}

$user_data = ['nome' => 'Usuário', 'email' => '', 'foto_perfil' => 'default.png'];

if (isset($_SESSION['usuario_id'])) {
  try {
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([(int) $_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      $user_data = [
        'nome' => $result['nome'] ?? 'Usuário',
        'email' => $result['email'] ?? '',
        'foto_perfil' => !empty($result['foto_perfil']) ? $result['foto_perfil'] : 'default.png'
      ];
      if (!empty($result['foto_perfil'])) {
        $_SESSION['foto_perfil'] = $result['foto_perfil'];
      } else {
        $_SESSION['foto_perfil'] = 'default.png';
      }
    }
  } catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
  }
}

$categorias = [
  'buffet' => 'Buffet',
  'bolo' => 'Bolo e Confeitaria',
  'fotografia' => 'Fotografia e Vídeo',
  'decoracao' => 'Decoração',
  'entretenimento' => 'Entretenimento (Bandas, DJs)',
  'moda' => 'Moda (Vestidos, Noivo)',
  'papelaria' => 'Papelaria',
  'pacote_completo' => 'Pacotes Completos'
];

$categoria_selecionada = isset($_GET['categoria']) ? $_GET['categoria'] : 'buffet';

$itens_disponiveis = [];
$pacotes_disponiveis = [];

// Buscar itens da categoria selecionada
$sql_itens = "
  SELECT 
    i.id_item,
    i.nome_item,
    i.valor_unitario,
    i.descricao,
    f.id_fornecedor,
    f.nome_fornecedor,
    f.categoria,
    COUNT(DISTINCT o.id_orcamento) as total_avaliacoes,
    COALESCE(AVG(CASE WHEN o.avaliacao > 0 THEN o.avaliacao END), 0) as rating_score
  FROM itens i
  INNER JOIN fornecedores f ON i.id_fornecedor = f.id_fornecedor
  LEFT JOIN orcamentos o ON i.nome_item = o.item AND o.avaliacao > 0
  WHERE f.categoria = ?
  GROUP BY i.id_item, i.nome_item, i.valor_unitario, i.descricao, f.id_fornecedor, f.nome_fornecedor, f.categoria
  ORDER BY rating_score DESC, total_avaliacoes DESC
";

$stmt = $pdo->prepare($sql_itens);
$stmt->execute([$categoria_selecionada]);
$itens_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar pacotes da categoria selecionada
$sql_pacotes = "
  SELECT 
    p.id_pacote,
    p.nome_pacote,
    p.valor_total,
    p.descricao,
    p.quantidade_itens,
    f.id_fornecedor,
    f.nome_fornecedor,
    f.categoria,
    0 as total_avaliacoes,
    0 as rating_score
  FROM pacotes p
  INNER JOIN fornecedores f ON p.id_fornecedor = f.id_fornecedor
  WHERE f.categoria = ?
  ORDER BY p.nome_pacote ASC
";

$stmt = $pdo->prepare($sql_pacotes);
$stmt->execute([$categoria_selecionada]);
$pacotes_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combinar itens e pacotes em um único array
$servicos_disponiveis = [];

// Adicionar pacotes primeiro (destaque)
foreach ($pacotes_disponiveis as $pacote) {
  $servicos_disponiveis[] = [
    'tipo' => 'pacote',
    'id' => $pacote['id_pacote'],
    'nome' => $pacote['nome_pacote'],
    'valor' => $pacote['valor_total'],
    'descricao' => $pacote['descricao'],
    'fornecedor_id' => $pacote['id_fornecedor'],
    'fornecedor_nome' => $pacote['nome_fornecedor'],
    'categoria' => $pacote['categoria'],
    'rating' => $pacote['rating_score'],
    'avaliacoes' => $pacote['total_avaliacoes'],
    'quantidade_itens' => $pacote['quantidade_itens']
  ];
}

// Adicionar itens
foreach ($itens_disponiveis as $item) {
  $servicos_disponiveis[] = [
    'tipo' => 'item',
    'id' => $item['id_item'],
    'nome' => $item['nome_item'],
    'valor' => $item['valor_unitario'],
    'descricao' => $item['descricao'],
    'fornecedor_id' => $item['id_fornecedor'],
    'fornecedor_nome' => $item['nome_fornecedor'],
    'categoria' => $item['categoria'],
    'rating' => $item['rating_score'],
    'avaliacoes' => $item['total_avaliacoes']
  ];
}

if (isset($_POST['logout'])) {
  try {
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
  } catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
  }

  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: ../index.php");
  exit;
}

if (isset($_POST['add_to_budget'])) {
  $id_fornecedor = (int) $_POST['id_fornecedor'];
  $id_usuario = (int) $_SESSION['usuario_id'];
  $tipo = $_POST['tipo'] ?? 'item'; 
  $id_servico = (int) $_POST['id_servico'];

  $stmt = $pdo->prepare("SELECT nome_fornecedor FROM fornecedores WHERE id_fornecedor = ?");
  $stmt->execute([$id_fornecedor]);
  $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($fornecedor) {
    try {
      if ($tipo === 'item') {
        $stmt = $pdo->prepare("SELECT nome_item, valor_unitario FROM itens WHERE id_item = ?");
        $stmt->execute([$id_servico]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
          $stmt = $pdo->prepare("
            INSERT INTO orcamentos (id_usuario, item, fornecedor, quantidade, valor_unitario, avaliacao)
            VALUES (?, ?, ?, 1, ?, 0)
          ");
          $stmt->execute([$id_usuario, $item['nome_item'], $fornecedor['nome_fornecedor'], $item['valor_unitario']]);
        }
      } elseif ($tipo === 'pacote') {
        $stmt = $pdo->prepare("SELECT nome_pacote, valor_total FROM pacotes WHERE id_pacote = ?");
        $stmt->execute([$id_servico]);
        $pacote = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pacote) {
          $stmt = $pdo->prepare("
            INSERT INTO orcamentos (id_usuario, item, fornecedor, quantidade, valor_unitario, avaliacao)
            VALUES (?, ?, ?, 1, ?, 0)
          ");
          $stmt->execute([$id_usuario, $pacote['nome_pacote'], $fornecedor['nome_fornecedor'], $pacote['valor_total']]);
        }
      }

      $stmt = $pdo->prepare("
        INSERT INTO contratos (nome_fornecedor, categoria, arquivo_pdf, data_assinatura, data_validade, valor, status, id_usuario)
        VALUES (?, ?, '', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0, 'ativo', ?)
      ");
      $stmt->execute([$fornecedor['nome_fornecedor'], $categoria_selecionada, $id_usuario]);

      header("Location: itens.php?categoria=" . urlencode($categoria_selecionada) . "&success=1");
      exit;
    } catch (PDOException $e) {
      error_log("Error adding to budget: " . $e->getMessage());
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fornecedores - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <style>
    .suppliers-container {
      display: grid;
      grid-template-columns: 250px 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }

    .categories-sidebar {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1.5rem;
      height: fit-content;
      position: sticky;
      top: 100px;
    }

    .categories-sidebar h3 {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: hsl(var(--foreground));
    }

    .category-list {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .category-item {
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      text-decoration: none;
      color: hsl(var(--foreground));
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
      cursor: pointer;
    }

    .category-item:hover {
      background: hsl(var(--muted));
      border-left-color: hsl(var(--primary));
    }

    .category-item.active {
      background: hsl(var(--primary) / 0.1);
      border-left-color: hsl(var(--primary));
      color: hsl(var(--primary));
      font-weight: 600;
    }

    .suppliers-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 1.5rem;
    }

    .supplier-card {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1.5rem;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .supplier-card:hover {
      border-color: hsl(var(--primary));
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      transform: translateY(-4px);
    }

    .supplier-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 1rem;
    }

    .supplier-name {
      font-size: 1.1rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      margin: 0;
    }

    .supplier-badge {
      background: hsl(var(--primary) / 0.1);
      color: hsl(var(--primary));
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .type-badge {
      background: hsl(var(--secondary) / 0.15);
      color: hsl(var(--secondary-foreground));
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .type-badge.pacote {
      background: linear-gradient(135deg, hsl(var(--primary) / 0.2), hsl(var(--primary) / 0.1));
      color: hsl(var(--primary));
      border: 1px solid hsl(var(--primary) / 0.3);
    }

    .supplier-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.75rem;
      font-size: 0.85rem;
      color: hsl(var(--muted-foreground));
    }

    .supplier-info svg {
      width: 14px;
      height: 14px;
    }

    .supplier-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .stars {
      display: flex;
      gap: 2px;
    }

    .star {
      width: 16px;
      height: 16px;
      fill: #ffc107;
    }

    .star-empty {
      fill: #ddd;
    }

    .rating-text {
      font-size: 0.9rem;
      color: hsl(var(--muted-foreground));
    }

    .supplier-description {
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
      margin-bottom: 1rem;
      flex-grow: 1;
      line-height: 1.5;
    }

    .package-info {
      background: hsl(var(--muted) / 0.3);
      border-radius: 0.5rem;
      padding: 0.75rem;
      margin-bottom: 1rem;
      font-size: 0.85rem;
      color: hsl(var(--muted-foreground));
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .package-info svg {
      width: 16px;
      height: 16px;
      flex-shrink: 0;
    }

    .supplier-actions {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      margin-top: auto;
    }

    .supplier-actions form {
      display: flex;
      gap: 0.5rem;
    }

    .supplier-actions button {
      flex: 1;
      padding: 0.75rem;
      border: none;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.85rem;
    }

    .btn-add-item {
      background: hsl(var(--primary) / 0.1);
      color: hsl(var(--primary));
      border: 1px solid hsl(var(--primary) / 0.3);
    }

    .btn-add-item:hover {
      background: hsl(var(--primary) / 0.2);
    }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem 1rem;
      color: hsl(var(--muted-foreground));
    }

    .empty-state svg {
      width: 64px;
      height: 64px;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .success-message {
      background: #d4edda;
      color: #155724;
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1rem;
      border: 1px solid #c3e6cb;
    }

    .profile-dropdown-wrapper {
      position: relative;
    }

    .profile-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.3s ease;
      object-fit: cover;
    }

    .profile-avatar:hover {
      border-color: hsl(var(--primary));
      transform: scale(1.05);
    }

    .profile-dropdown {
      position: absolute;
      top: calc(100% + 0.5rem);
      right: 0;
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      min-width: 280px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow: hidden;
    }

    .profile-dropdown.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .profile-dropdown-header {
      padding: 1.25rem;
      border-bottom: 1px solid hsl(var(--border));
      background: linear-gradient(135deg, hsl(var(--primary) / 0.05), hsl(var(--secondary) / 0.05));
    }

    .profile-dropdown-user {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .profile-dropdown-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid hsl(var(--primary));
    }

    .profile-dropdown-info {
      flex: 1;
      min-width: 0;
    }

    .profile-dropdown-name {
      font-weight: 600;
      font-size: 0.95rem;
      color: hsl(var(--foreground));
      margin-bottom: 0.125rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .profile-dropdown-email {
      font-size: 0.8rem;
      color: hsl(var(--muted-foreground));
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .profile-dropdown-menu {
      padding: 0.5rem;
    }

    .profile-dropdown-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      color: hsl(var(--foreground));
      text-decoration: none;
      border-radius: 0.5rem;
      transition: all 0.2s ease;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .profile-dropdown-item:hover {
      background: hsl(var(--muted));
      transform: translateX(4px);
    }

    .profile-dropdown-item svg {
      width: 18px;
      height: 18px;
      stroke-width: 2;
    }

    .profile-dropdown-item.logout {
      color: hsl(var(--destructive));
      border-top: 1px solid hsl(var(--border));
      margin-top: 0.5rem;
      padding-top: 1rem;
    }

    .profile-dropdown-item.logout:hover {
      background: hsl(var(--destructive) / 0.1);
    }

    .profile-dropdown-item.logout svg {
      stroke: hsl(var(--destructive));
    }

    @media (max-width: 768px) {
      .suppliers-container {
        grid-template-columns: 1fr;
      }

      .categories-sidebar {
        position: static;
      }

      .category-list {
        flex-direction: row;
        flex-wrap: wrap;
      }

      .category-item {
        flex: 1;
        min-width: 120px;
        text-align: center;
      }

      .suppliers-grid {
        grid-template-columns: 1fr;
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
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
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
            </div>
          </div>
          <a href="contato.php" class="nav-link">Contato</a>

          <?php if (isset($_SESSION["usuario_id"])): ?>
            <div class="profile-dropdown-wrapper">
              <img src="../user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>"
                alt="Foto de perfil" class="profile-avatar" onclick="toggleProfileDropdown()">
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <div class="profile-dropdown-user">
                    <img src="../user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>"
                      alt="Avatar" class="profile-dropdown-avatar">
                    <div class="profile-dropdown-info">

                      <div class="profile-dropdown-name">
                        <?php echo htmlspecialchars($user_data['nome']); ?>
                      </div>
                      <div class="profile-dropdown-email">
                        <?php echo htmlspecialchars($user_data['email']); ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="profile-dropdown-menu">
                  <a href="../user/perfil.php" class="profile-dropdown-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Meu Perfil
                  </a>
                  <a href="funcionalidades.php" class="profile-dropdown-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <rect x="3" y="4" width="7" height="7"></rect>
                      <rect x="14" y="3" width="7" height="7"></rect>
                      <rect x="14" y="14" width="7" height="7"></rect>
                      <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Funcionalidades
                  </a>
                  <form method="post" style="margin:0;">
                    <button type="submit" name="logout" class="profile-dropdown-item logout"
                      style="width: 100%; text-align: left; background: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem;">
                      <svg fill="hsl(var(--foreground))" width="800px" height="800px" viewBox="0 0 36 36" version="1.1"
                        preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink">
                        <title>logout-line</title>
                        <path d="M7,6H23v9.8h2V6a2,2,0,0,0-2-2H7A2,2,0,0,0,5,6V30a2,2,0,0,0,2,2H23a2,2,0,0,0,2-2H7Z"
                          class="clr-i-outline clr-i-outline-path-1"></path>
                        <path
                          d="M28.16,17.28a1,1,0,0,0-1.41,1.41L30.13,22H15.63a1,1,0,0,0-1,1,1,1,0,0,0,1,1h14.5l-3.38,3.46a1,1,0,1,0,1.41,1.41L34,23.07Z"
                          class="clr-i-outline clr-i-outline-path-2"></path>
                        <rect x="0" y="0" width="36" height="36" fill-opacity="0" />
                      </svg>
                      Sair
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php else: ?>

            <a href="../user/login.php" class="btn-primary" style="align-items: center">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header">
          <h1 class="page-title">
            Encontre <span class="gradient-text">Itens e Pacotes</span>
          </h1>
          <p class="page-description">
            Explore itens e pacotes de qualidade para seu evento, organizados por categoria.
          </p>
        </div>

        <?php if (isset($_GET['success'])): ?>
          <div class="success-message">
            ✓ Serviço adicionado ao orçamento e contrato criado com sucesso!
          </div>
        <?php endif; ?>

        <div class="suppliers-container">

          <aside class="categories-sidebar">
            <h3>Categorias</h3>
            <div class="category-list">
              <?php foreach ($categorias as $key => $nome): ?>
                <a href="itens.php?categoria=<?php echo urlencode($key); ?>"
                  class="category-item <?php echo ($categoria_selecionada === $key) ? 'active' : ''; ?>">
                  <?php echo htmlspecialchars($nome); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </aside>

          <div>
            <h2 style="margin-bottom: 1.5rem; color: hsl(var(--foreground));">
              <?php echo htmlspecialchars($categorias[$categoria_selecionada] ?? 'Itens e Pacotes'); ?>
            </h2>

            <?php if (empty($servicos_disponiveis)): ?>
              <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="11" cy="11" r="8"></circle>
                  <path d="m21 21-4.35-4.35"></path>
                </svg>
                <h3>Nenhum item ou pacote encontrado</h3>
                <p>Não há itens ou pacotes disponíveis nesta categoria no momento.</p>
              </div>
            <?php else: ?>
              <div class="suppliers-grid">
                <?php foreach ($servicos_disponiveis as $servico): ?>
                  <div class="supplier-card">
                    <div class="supplier-header">
                      <div style="flex: 1;">
                        <h3 class="supplier-name"><?php echo htmlspecialchars($servico['nome']); ?></h3>
                        <div class="supplier-info">
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                          </svg>
                          <span><?php echo htmlspecialchars($servico['fornecedor_nome']); ?></span>
                        </div>
                      </div>
                      <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                        <span class="type-badge <?php echo $servico['tipo']; ?>">
                          <?php echo $servico['tipo'] === 'pacote' ? 'Pacote' : 'Item'; ?>
                        </span>
                        <div style="font-size: 1.25rem; font-weight: 700; color: hsl(var(--primary));">
                          R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?>
                        </div>
                      </div>
                    </div>

                    <?php if ($servico['tipo'] === 'item' && $servico['avaliacoes'] > 0): ?>
                      <div class="supplier-rating">
                        <div class="stars">
                          <?php
                          $rating = (float) $servico['rating'];
                          for ($i = 1; $i <= 5; $i++):
                            ?>
                            <svg class="star <?php echo ($i <= $rating) ? '' : 'star-empty'; ?>" viewBox="0 0 24 24">
                              <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                          <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                          <?php echo number_format($rating, 1, ',', '.') . ' (' . (int) $servico['avaliacoes'] . ' avaliações)'; ?>
                        </span>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($servico['descricao'])): ?>
                      <p class="supplier-description">
                        <?php echo htmlspecialchars($servico['descricao']); ?>
                      </p>
                    <?php else: ?>
                      <p class="supplier-description">
                        <?php echo $servico['tipo'] === 'pacote' ? 'Pacote completo com múltiplos serviços incluídos.' : 'Serviço de qualidade oferecido por ' . htmlspecialchars($servico['fornecedor_nome']) . '.'; ?>
                      </p>
                    <?php endif; ?>

                    <?php if ($servico['tipo'] === 'pacote'): ?>
                      <div class="package-info">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                        <span><strong><?php echo $servico['quantidade_itens']; ?></strong> item(ns) incluído(s) neste pacote</span>
                      </div>
                    <?php endif; ?>

                    <div class="supplier-actions">
                      <?php if (isset($_SESSION['usuario_id'])): ?>
                        <form method="post" style="flex: 1;">
                          <input type="hidden" name="id_fornecedor" value="<?php echo $servico['fornecedor_id']; ?>">
                          <input type="hidden" name="tipo" value="<?php echo $servico['tipo']; ?>">
                          <input type="hidden" name="id_servico" value="<?php echo $servico['id']; ?>">
                          <button type="submit" name="add_to_budget" class="btn-primary" style="width: 100%;">
                            Adicionar ao Orçamento
                          </button>
                        </form>
                      <?php else: ?>
                        <a href="../user/login.php" class="btn-primary"
                          style="width: 100%; text-align: center; padding: 0.75rem;">
                          Fazer Login
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
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
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">Planner de Sonhos</span>
          </a>
          <p class="footer-description">
            A plataforma mais completa para cerimonialistas organizarem
            casamentos perfeitos. Simplifique sua gestão e encante seus
            clientes.
          </p>
          <div class="footer-contact">
            <svg style="width: 1rem; height: 1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>contato@plannerdesonhos.com</span>
          </div>
        </div>
        <div class="footer-links">
          <h3>Navegação</h3>
          <ul>
            <li><a href="../index.php">Início</a></li>
            <li>
              <a href="funcionalidades.php">Funcionalidades</a>
            </li>
            <li>
              <a href="contato.php">Contato</a>
            </li>
          </ul>
        </div>
        <div class="footer-modules">
          <h3>Legal</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li>
              <a href="../legal-pages/privacity-politics.html">Política de Privacidade</a>
            </li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
        <div
          style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
          <span>Feito com</span>
          <svg style="width: 1rem; height: 1rem; color: hsl(var(--primary)); margin: 0 0.25rem;" fill="currentColor"
            viewBox="0 0 24 24">
            <path
              d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
          <span>para cerimonialistas</span>
        </div>
      </div>
    </div>
  </footer>

  <script>
    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.classList.toggle("active");
    }

    document.addEventListener('click', function (event) {
      const wrapper = document.querySelector('.profile-dropdown-wrapper');
      const dropdown = document.getElementById("profileDropdown");

      if (wrapper && !wrapper.contains(event.target)) {
        dropdown?.classList.remove("active");
      }
    });
  </script>
</body>

</html>
