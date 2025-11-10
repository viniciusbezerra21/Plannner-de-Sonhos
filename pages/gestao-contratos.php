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
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil, cargo FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([(int) $_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      $user_data = [
        'nome' => $result['nome'] ?? 'Usuário',
        'email' => $result['email'] ?? '',
        'foto_perfil' => !empty($result['foto_perfil']) ? $result['foto_perfil'] : 'default.png',
        'cargo' => $result['cargo'] ?? 'cliente'
      ];

      if (!empty($result['foto_perfil'])) {
        $_SESSION['foto_perfil'] = $result['foto_perfil'];
      } else {
        $_SESSION['foto_perfil'] = 'default.png';
      }
      
      $_SESSION['cargo'] = $result['cargo'] ?? 'cliente';
    }
  } catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
  }
}

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../user/login.php");
  exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];
$cargo = $_SESSION['cargo'] ?? 'cliente';

if (isset($_POST['logout'])) {
  try {
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id_usuario = ?");
    $stmt->execute([$idUsuario]);
  } catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
  }

  $cookieName = "lembrar_me";
  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: ../index.php");
  exit;
}

if (isset($_POST['create_contract']) && $cargo === 'cerimonialista') {
  $id_cliente = (int) $_POST['id_cliente'];
  $nome_contrato = trim($_POST['nome_contrato']);
  $descricao = trim($_POST['descricao']);
  
  $arquivo_contrato = '';
  if (isset($_FILES['arquivo_contrato']) && $_FILES['arquivo_contrato']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../contratos/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }
    
    $fileExtension = pathinfo($_FILES['arquivo_contrato']['name'], PATHINFO_EXTENSION);
    $fileName = 'contrato_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['arquivo_contrato']['tmp_name'], $uploadPath)) {
      $arquivo_contrato = $fileName;
    }
  }

  if ($nome_contrato !== "" && $arquivo_contrato !== "") {
    $stmt = $pdo->prepare("INSERT INTO contratos (id_usuario, id_cerimonialista, nome_contrato, descricao, arquivo_contrato, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
    $stmt->execute([$id_cliente, $idUsuario, $nome_contrato, $descricao, $arquivo_contrato]);
  }

  header("Location: gestao-contratos.php");
  exit;
}

if (isset($_POST['sign_contract']) && $cargo === 'cliente') {
  $id_contrato = (int) $_POST['id_contrato'];
  
  // Verificar se o contrato pertence ao usuário
  $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_contrato = ? AND id_usuario = ? AND status = 'pendente'");
  $stmt->execute([$id_contrato, $idUsuario]);
  $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($contrato) {
    $arquivo_assinado = '';
    if (isset($_FILES['arquivo_assinado']) && $_FILES['arquivo_assinado']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = '../contratos/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }
      
      $fileExtension = pathinfo($_FILES['arquivo_assinado']['name'], PATHINFO_EXTENSION);
      $fileName = 'assinado_' . time() . '_' . uniqid() . '.' . $fileExtension;
      $uploadPath = $uploadDir . $fileName;

      if (move_uploaded_file($_FILES['arquivo_assinado']['tmp_name'], $uploadPath)) {
        $arquivo_assinado = $fileName;
        
        $stmt = $pdo->prepare("UPDATE contratos SET arquivo_assinado = ?, status = 'assinado', data_assinatura = NOW() WHERE id_contrato = ?");
        $stmt->execute([$arquivo_assinado, $id_contrato]);
      }
    }
  }

  header("Location: gestao-contratos.php");
  exit;
}

if (isset($_POST['reject_contract']) && $cargo === 'cliente') {
  $id_contrato = (int) $_POST['id_contrato'];
  
  $stmt = $pdo->prepare("UPDATE contratos SET status = 'rejeitado' WHERE id_contrato = ? AND id_usuario = ?");
  $stmt->execute([$id_contrato, $idUsuario]);

  header("Location: gestao-contratos.php");
  exit;
}

if (isset($_POST['delete_contract'])) {
  $id_contrato = (int) $_POST['id_contrato'];
  
  // Verificar permissão
  if ($cargo === 'cerimonialista') {
    $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_contrato = ? AND id_cerimonialista = ?");
    $stmt->execute([$id_contrato, $idUsuario]);
  } else {
    $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_contrato = ? AND id_usuario = ?");
    $stmt->execute([$id_contrato, $idUsuario]);
  }
  
  $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($contrato) {
    // Deletar arquivos
    if ($contrato['arquivo_contrato']) {
      $filePath = '../contratos/' . $contrato['arquivo_contrato'];
      if (file_exists($filePath)) {
        unlink($filePath);
      }
    }
    if ($contrato['arquivo_assinado']) {
      $filePath = '../contratos/' . $contrato['arquivo_assinado'];
      if (file_exists($filePath)) {
        unlink($filePath);
      }
    }
    
    // Deletar registro
    $stmt = $pdo->prepare("DELETE FROM contratos WHERE id_contrato = ?");
    $stmt->execute([$id_contrato]);
  }

  header("Location: gestao-contratos.php");
  exit;
}

if ($cargo === 'cerimonialista') {
  // Cerimonialista vê contratos que ele criou
  $stmt = $pdo->prepare("
    SELECT c.*, u.nome as nome_cliente, u.email as email_cliente 
    FROM contratos c 
    JOIN usuarios u ON c.id_usuario = u.id_usuario 
    WHERE c.id_cerimonialista = ? 
    ORDER BY c.data_criacao DESC
  ");
  $stmt->execute([$idUsuario]);
} else {
  // Cliente vê contratos enviados para ele
  $stmt = $pdo->prepare("
    SELECT c.*, u.nome as nome_cerimonialista 
    FROM contratos c 
    JOIN usuarios u ON c.id_cerimonialista = u.id_usuario 
    WHERE c.id_usuario = ? 
    ORDER BY c.data_criacao DESC
  ");
  $stmt->execute([$idUsuario]);
}
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$clientes = [];
if ($cargo === 'cerimonialista') {
  $stmt = $pdo->query("SELECT id_usuario, nome, email FROM usuarios WHERE cargo = 'cliente' ORDER BY nome");
  $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title >Planner de Sonhos - Gestão de Contratos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <style>
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      justify-content: center;
      align-items: center;
      z-index: 2000;
      backdrop-filter: blur(4px);
    }

    .modal-overlay.active {
      display: flex;
    }

    .contract-modal {
      background: white;
      padding: 2rem;
      border-radius: 1rem;
      width: 100%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: hsl(var(--foreground));
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      font-family: inherit;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    .form-row {
      display: flex;
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .contract-actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-top: 1rem;
    }

    .btn-small {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      border-radius: 0.375rem;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      transition: all 0.2s;
    }

    .btn-download {
      background: hsl(var(--primary));
    .btn-edit {
      background-color: hsl(var(--primary));
      color: white;
    }

    .btn-download:hover {
      opacity: 0.9;
    }

    .btn-sign {
      background: #10b981;
      color: white;
    }

    .btn-sign:hover {
      background: #059669;
    }

    .btn-reject {
      background: #ef4444;

      color: white;
    }

    .btn-reject:hover {
      background: #dc2626;
    }

    .btn-delete {
      background: #6b7280;
      color: white;
    }

    .btn-delete:hover {
      background: #4b5563;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      display: inline-block;
    }

    .status-pendente {
      background: #fef3c7;
      color: #92400e;
    }

    .status-assinado {
      background: #dcfce7;
      color: #166534;
    }

    .status-rejeitado {
      background: #fee2e2;
      color: #991b1b;
    }

    .contract-card {
      background: white;
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 1.5rem;
      transition: all 0.2s;
    }

    .contract-card:hover {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }

    .contract-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }

    .contract-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      margin-bottom: 0.25rem;
    }

    .contract-meta {
      color: hsl(var(--muted-foreground));
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
    }

    .contract-description {
      color: hsl(var(--muted-foreground));
      margin-bottom: 1rem;
      line-height: 1.5;
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

    .info-banner {
      background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--secondary) / 0.1));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .info-banner svg {
      width: 24px;
      height: 24px;
      color: hsl(var(--primary));
      flex-shrink: 0;
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

        <button id="hamburgerBtn" class="mobile-menu-btn" onclick="toggleMobileMenu()">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
      <div id="mobileMenu" class="mobile-menu">
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div class="page-header" style="justify-content:space-between;align-items:flex-start;">
          <div>
            <h1 class="page-title"">
              Gestão de <span class="gradient-text">Contratos</span>
            </h1>
            <p class="page-description">
              <?php if ($cargo === 'cerimonialista'): ?>
                Crie e envie contratos para seus clientes assinarem digitalmente.
              <?php else: ?>
                Visualize e assine os contratos enviados pelo seu cerimonialista.
              <?php endif; ?>
            </p>
          </div>
          <?php if ($cargo === 'cerimonialista'): ?>
            <button class="btn-primary" onclick="openCreateModal()">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Novo Contrato
            </button>
          <?php endif; ?>
          <button  class="btn-primary" style="margin-top: 1.5rem;" onclick="openAddModal()">+ Novo Contrato</button>
        </div>

        <?php if ($cargo === 'cliente'): ?>
          <div class="info-banner">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
              <strong>Como assinar um contrato:</strong> Baixe o contrato, assine-o (digitalmente ou impresso e escaneado), 
              e faça o upload do arquivo assinado clicando em "Assinar Contrato".
            </div>
          </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
          <?php if (empty($contratos)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
              <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; color: hsl(var(--muted-foreground));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <h3 style="margin-bottom: 0.5rem;">Nenhum contrato encontrado</h3>
              <p style="color: hsl(var(--muted-foreground));">
                <?php if ($cargo === 'cerimonialista'): ?>
                  Comece criando um novo contrato para seus clientes.
                <?php else: ?>
                  Aguarde o envio de contratos pelo seu cerimonialista.
                <?php endif; ?>
              </p>
            </div>
          <?php else: ?>
            <?php foreach ($contratos as $contrato): ?>
              <div class="contract-card">
                <div class="contract-header">
                  <div style="flex: 1;">
                    <h3 class="contract-title"><?php echo htmlspecialchars($contrato['nome_contrato']); ?></h3>
                    <div class="contract-meta">
                      <?php if ($cargo === 'cerimonialista'): ?>
                        Cliente: <?php echo htmlspecialchars($contrato['nome_cliente']); ?>
                      <?php else: ?>
                        Cerimonialista: <?php echo htmlspecialchars($contrato['nome_cerimonialista']); ?>
                      <?php endif; ?>
                    </div>
                    <div class="contract-meta">
                      Criado em: <?php echo date("d/m/Y H:i", strtotime($contrato['data_criacao'])); ?>
                    </div>
                    <?php if ($contrato['data_assinatura']): ?>
                      <div class="contract-meta">
                        Assinado em: <?php echo date("d/m/Y H:i", strtotime($contrato['data_assinatura'])); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <span class="status-badge status-<?php echo $contrato['status']; ?>">
                    <?php 
                      $status_labels = [
                        'pendente' => 'Pendente',
                        'assinado' => 'Assinado',
                        'rejeitado' => 'Rejeitado'
                      ];
                      echo $status_labels[$contrato['status']];
                    ?>
                  </span>
                </div>

                <?php if ($contrato['descricao']): ?>
                  <div class="contract-description">
                    <?php echo nl2br(htmlspecialchars($contrato['descricao'])); ?>
                  </div>
                <?php endif; ?>

                <div class="contract-actions">
                  <?php if ($contrato['arquivo_contrato']): ?>
                    <a href="../contratos/<?php echo htmlspecialchars($contrato['arquivo_contrato']); ?>" 
                       download class="btn-small btn-download">
                      📄 Baixar Contrato
                  <?php if ($contrato['arquivo_pdf']): ?>
                    <a href="../Docs/<?php echo htmlspecialchars($contrato['arquivo_pdf']); ?>" download class="btn-small"
                      style="scale: 0.8;">
                       Baixar
                    </a>
                  <?php endif; ?>

                  <?php if ($contrato['arquivo_assinado']): ?>
                    <a href="../contratos/<?php echo htmlspecialchars($contrato['arquivo_assinado']); ?>" 
                       download class="btn-small btn-download">
                      ✅ Baixar Assinado
                    </a>
                  <?php endif; ?>

                  <?php if ($cargo === 'cliente' && $contrato['status'] === 'pendente'): ?>
                    <button class="btn-small btn-sign" onclick="openSignModal(<?php echo $contrato['id_contrato']; ?>)">
                      ✍️ Assinar Contrato
                    </button>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja rejeitar este contrato?')">
                      <input type="hidden" name="id_contrato" value="<?php echo $contrato['id_contrato']; ?>">
                      <button type="submit" name="reject_contract" class="btn-small btn-reject">
                        ❌ Rejeitar
                      </button>
                    </form>
                  <?php endif; ?>

                  <form method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este contrato?')">
                  <button style="scale: 0.8;" class="btn-primary"
                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($contrato)); ?>)">
                     Editar
                  </button>
                  <form method="post" style="display: inline;"
                    onsubmit="return confirm('Tem certeza que deseja excluir este contrato?')">
                    <input type="hidden" name="id_contrato" value="<?php echo $contrato['id_contrato']; ?>">
                    <button type="submit" name="delete_contract" style="scale: 0.8; left: 120px;" class="btn-outline">
                       Excluir
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <?php if ($cargo === 'cerimonialista'): ?>
  <!-- Modal Criar Contrato -->
  <div class="modal-overlay" id="createModal">
    <div class="contract-modal">
      <form method="post" enctype="multipart/form-data">
        <h2 style="margin-bottom: 1.5rem; color: hsl(var(--primary));">Criar Novo Contrato</h2>

        <div class="form-group">
          <label for="id_cliente">Cliente *</label>
          <select id="id_cliente" name="id_cliente" required>
            <option value="">Selecione um cliente</option>
            <?php foreach ($clientes as $cliente): ?>
              <option value="<?php echo $cliente['id_usuario']; ?>">
                <?php echo htmlspecialchars($cliente['nome']) . ' (' . htmlspecialchars($cliente['email']) . ')'; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="nome_contrato">Nome do Contrato *</label>
          <input type="text" id="nome_contrato" name="nome_contrato" 
                 placeholder="Ex: Contrato de Serviços de Cerimonial" required>
        </div>

        <div class="form-group">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" 
                    placeholder="Descreva os detalhes do contrato..."></textarea>
        </div>

        <div class="form-group">
          <label for="arquivo_contrato">Arquivo do Contrato (PDF) *</label>
          <input type="file" id="arquivo_contrato" name="arquivo_contrato" 
                 accept=".pdf,.doc,.docx" required>
          <small style="color: hsl(var(--muted-foreground)); display: block; margin-top: 0.25rem;">
            Formatos aceitos: PDF, DOC, DOCX
          </small>
        </div>

        <div class="form-row">
          <button type="submit" name="create_contract" class="btn-primary">Criar e Enviar</button>
          <button type="button" class="btn-outline" onclick="closeModal('createModal')">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($cargo === 'cliente'): ?>
  <!-- Modal Assinar Contrato -->
  <div class="modal-overlay" id="signModal">
    <div class="contract-modal">
      <form method="post" enctype="multipart/form-data">
        <h2 style="margin-bottom: 1.5rem; color: hsl(var(--primary));">Assinar Contrato</h2>

        <input type="hidden" id="sign_id_contrato" name="id_contrato">

        <div class="info-banner" style="margin-bottom: 1.5rem;">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div style="font-size: 0.875rem;">
            Baixe o contrato original, assine-o (digitalmente ou impresso e escaneado), 
            e faça o upload do arquivo assinado abaixo.
          </div>
        </div>

        <div class="form-group">
          <label for="arquivo_assinado">Contrato Assinado (PDF) *</label>
          <input type="file" id="arquivo_assinado" name="arquivo_assinado" 
                 accept=".pdf" required>
          <small style="color: hsl(var(--muted-foreground)); display: block; margin-top: 0.25rem;">
            Faça upload do contrato com sua assinatura (PDF)
          </small>
        </div>

        <div class="form-row">
          <button type="submit" name="sign_contract" class="btn-primary">Confirmar Assinatura</button>
          <button type="button" class="btn-outline" onclick="closeModal('signModal')">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

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
    function openCreateModal() {
      document.getElementById("createModal").classList.add("active");
    }

    function openSignModal(idContrato) {
      document.getElementById("sign_id_contrato").value = idContrato;
      document.getElementById("signModal").classList.add("active");
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove("active");
    }

    document.addEventListener("click", function (event) {
      const modals = document.querySelectorAll(".modal-overlay");
      modals.forEach(modal => {
        if (event.target === modal) {
          modal.classList.remove("active");
        }
      });
    });

    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");
      mobileMenu.classList.toggle("active");
      hamburgerBtn.classList.toggle("hamburger-active");
    }


    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
          const mobileMenu = document.getElementById("mobileMenu");
          const hamburgerBtn = document.getElementById("hamburgerBtn");
          mobileMenu.classList.remove("active");
          hamburgerBtn.classList.remove("hamburger-active");
        }
      });
    });
  </script>
  <script>
    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.classList.toggle("active");
    }

    document.addEventListener('click', function (event) {
      const profileWrapper = document.querySelector('.profile-dropdown-wrapper');
      const dropdown = document.getElementById("profileDropdown");
      if (profileWrapper && dropdown && !profileWrapper.contains(event.target)) {
        dropdown.classList.remove("active");
      }
    });
  </script>
</body>

</html>
