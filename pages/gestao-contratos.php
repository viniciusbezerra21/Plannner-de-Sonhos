<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";

/* --- Restaurar sessão a partir do cookie (seguro: valida no DB) --- */
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
  $cookieUserId = (int) $_COOKIE[$cookieName];
  if ($cookieUserId > 0) {
    $chk = $pdo->prepare("SELECT id_usuario, nome, cargo FROM usuarios WHERE id_usuario = ?");
    $chk->execute([$cookieUserId]);
    $u = $chk->fetch(PDO::FETCH_ASSOC);
    if ($u) {
      $_SESSION['usuario_id'] = (int)$u['id_usuario'];
      $_SESSION['nome'] = $u['nome'];
      $_SESSION['cargo'] = $u['cargo'] ?? 'cliente';
    } else {
      // cookie inválido -> remover
      setcookie($cookieName, "", time() - 3600, "/");
    }
  }
}

$user_data = ['nome' => 'Usuário', 'email' => '', 'foto_perfil' => 'default.png'];

/* --- Verifica login e busca dados do usuário --- */
if (isset($_SESSION['usuario_id'])) {
  try {
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([(int)$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
      $user_data = [
        'nome' => $result['nome'] ?? 'Usuário',
        'email' => $result['email'] ?? '',
        'foto_perfil' => !empty($result['foto_perfil']) ? $result['foto_perfil'] : 'default.png'
      ];
      // Update session with latest photo
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

/* ------------------------ */
/* 🔐 LOGIN POR COOKIE */
/* ------------------------ */
// if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
//   $usuarioId = (int) $_COOKIE[$cookieName];
//
//   $stmt = $pdo->prepare("SELECT id_usuario, foto_perfil FROM usuarios WHERE id_usuario = ?");
//   $stmt->execute([$usuarioId]);
//   $user = $stmt->fetch(PDO::FETCH_ASSOC);
//
//   if ($user) {
//     $_SESSION['usuario_id'] = $user['id_usuario'];
//     $_SESSION['foto_perfil'] = $user['foto_perfil'] ?: "default.png";
//   } else {
//     setcookie($cookieName, "", time() - 3600, "/");
//   }
// }

/* ------------------------ */
/* 🔑 VERIFICA LOGIN */
/* ------------------------ */
if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../user/login.php");
  exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];

/* ------------------------ */
/* 🚪 LOGOUT */
/* ------------------------ */
if (isset($_POST['logout'])) {
  session_destroy();
  setcookie($cookieName, "", time() - 3600, "/");
  header("Location: ../user/login.php");
  exit;
}

/* ------------------------ */
/* ➕ ADICIONAR CONTRATO */
/* ------------------------ */
if (isset($_POST['add_contract'])) {
  $nome_fornecedor = trim($_POST['nome_fornecedor']);
  $categoria = trim($_POST['categoria']);
  $data_assinatura = $_POST['data_assinatura'];
  $data_validade = $_POST['data_validade'];
  $valor = $_POST['valor'] ? floatval($_POST['valor']) : null;
  $observacoes = trim($_POST['observacoes']);
  
  // Upload do arquivo PDF
  $arquivo_pdf = '';
  if (isset($_FILES['arquivo_pdf']) && $_FILES['arquivo_pdf']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../Docs/';
    $fileName = time() . '_' . basename($_FILES['arquivo_pdf']['name']);
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['arquivo_pdf']['tmp_name'], $uploadPath)) {
      $arquivo_pdf = $fileName;
    }
  }

  if ($nome_fornecedor !== "" && $categoria !== "" && $data_assinatura !== "" && $data_validade !== "") {
    $stmt = $pdo->prepare("INSERT INTO contratos (nome_fornecedor, categoria, arquivo_pdf, data_assinatura, data_validade, valor, observacoes, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nome_fornecedor, $categoria, $arquivo_pdf, $data_assinatura, $data_validade, $valor, $observacoes, $idUsuario]);
  }

  header("Location: gestao-contratos.php");
  exit;
}

/* ------------------------ */
/* 🗑️ EXCLUIR CONTRATO */
/* ------------------------ */
if (isset($_POST['delete_contract'])) {
  $id_contrato = (int) $_POST['id_contrato'];
  
  // Buscar o arquivo para deletar
  $stmt = $pdo->prepare("SELECT arquivo_pdf FROM contratos WHERE id_contrato = ? AND id_usuario = ?");
  $stmt->execute([$id_contrato, $idUsuario]);
  $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($contrato && $contrato['arquivo_pdf']) {
    $filePath = '../Docs/' . $contrato['arquivo_pdf'];
    if (file_exists($filePath)) {
      unlink($filePath);
    }
  }
  
  $stmt = $pdo->prepare("DELETE FROM contratos WHERE id_contrato = ? AND id_usuario = ?");
  $stmt->execute([$id_contrato, $idUsuario]);
  
  header("Location: gestao-contratos.php");
  exit;
}

/* ------------------------ */
/* ✏️ EDITAR CONTRATO */
/* ------------------------ */
if (isset($_POST['edit_contract'])) {
  $id_contrato = (int) $_POST['id_contrato'];
  $nome_fornecedor = trim($_POST['nome_fornecedor']);
  $categoria = trim($_POST['categoria']);
  $data_assinatura = $_POST['data_assinatura'];
  $data_validade = $_POST['data_validade'];
  $valor = $_POST['valor'] ? floatval($_POST['valor']) : null;
  $status = $_POST['status'];
  $observacoes = trim($_POST['observacoes']);
  
  $stmt = $pdo->prepare("UPDATE contratos SET nome_fornecedor = ?, categoria = ?, data_assinatura = ?, data_validade = ?, valor = ?, status = ?, observacoes = ? WHERE id_contrato = ? AND id_usuario = ?");
  $stmt->execute([$nome_fornecedor, $categoria, $data_assinatura, $data_validade, $valor, $status, $observacoes, $id_contrato, $idUsuario]);
  
  header("Location: gestao-contratos.php");
  exit;
}

/* ------------------------ */
/* 📋 LISTAR CONTRATOS */
/* ------------------------ */
$stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_usuario = ? ORDER BY data_assinatura DESC");
$stmt->execute([$idUsuario]);
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos - Gestão de Contratos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <style>
    
    /* Adding modal and form styles for contract management */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      justify-content: center;
      align-items: center;
      z-index: 2000;
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
    }

    .btn-edit {
      background: #3b82f6;
      color: white;
    }

    .btn-delete {
      background: #ef4444;
      color: white;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-ativo {
      background: #dcfce7;
      color: #166534;
    }

    .status-vencido {
      background: #fef2f2;
      color: #dc2626;
    }

    .status-cancelado {
      background: #f3f4f6;
      color: #6b7280;
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
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <!-- Logo -->
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
              <a href="fornecedores.php">Fornecedores</a>
              <a href="gestao-contratos.php">Gestão de Contratos</a>
              <a href="tarefas.php">Lista de Tarefas</a>
            </div>
          </div>
          <a href="contato.php" class="nav-link">Contato</a>

          <?php if (isset($_SESSION["usuario_id"])): ?>
            <div class="profile-dropdown-wrapper">
              <img 
                src="../user/fotos/<?php echo htmlspecialchars($user_data['foto_perfil'] ?? 'default.png'); ?>"
                alt="Foto de perfil"
                class="profile-avatar"
                onclick="toggleProfileDropdown()"
              >
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <div class="profile-dropdown-user">
                    <img 
                      src="../user/fotos/<?php echo htmlspecialchars($user_data['foto_perfil'] ?? 'default.png'); ?>"
                      alt="Avatar" 
                      class="profile-dropdown-avatar"
                    >
                    <div class="profile-dropdown-info">
                      <!-- Fixed to properly display user name and email -->
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
                  <a href="user/perfil.php" class="profile-dropdown-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Meu Perfil
                  </a>
                  <a href="pages/funcionalidades.php" class="profile-dropdown-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <rect x="3" y="4" width="7" height="7"></rect>
                      <rect x="14" y="3" width="7" height="7"></rect>
                      <rect x="14" y="14" width="7" height="7"></rect>
                      <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Funcionalidades
                  </a>
                  <form method="post" style="margin:0;">
                    <button type="submit" name="logout" class="profile-dropdown-item logout" style="width: 100%; text-align: left; background: none; border: none; font-family: inherit; font-size: inherit;">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
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
        <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <h1 class="page-title">
              Gestão de <span class="gradient-text">Contratos</span>
            </h1>
            <p class="page-description">
              Visualize, baixe e mantenha todos os contratos organizados em um só lugar.
            </p>
          </div>
          <button class="btn-primary" onclick="openAddModal()">+ Novo Contrato</button>
        </div>

        <div class="contracts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
          <?php if (empty($contratos)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
              <p>Você ainda não tem contratos cadastrados.</p>
            </div>
          <?php else: ?>
            <?php foreach ($contratos as $contrato): ?>
              <div class="card" style="display: flex; flex-direction: column; text-align: center; padding: 1rem;">
                <?php if ($contrato['arquivo_pdf']): ?>
                  <iframe
                    src="../Docs/<?php echo htmlspecialchars($contrato['arquivo_pdf']); ?>"
                    style="width: 100%; height: 200px; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid hsl(var(--border));">
                  </iframe>
                <?php else: ?>
                  <div style="width: 100%; height: 200px; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid hsl(var(--border)); display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                    <span>📄 Sem arquivo</span>
                  </div>
                <?php endif; ?>

                <h3 style="margin-bottom: 0.5rem"><?php echo htmlspecialchars($contrato['nome_fornecedor']); ?></h3>
                
                <div style="display: flex; justify-content: center; margin-bottom: 0.5rem;">
                  <span class="status-badge status-<?php echo $contrato['status']; ?>">
                    <?php echo ucfirst($contrato['status']); ?>
                  </span>
                </div>

                <p style="color: hsl(var(--muted-foreground)); font-size: 0.9rem; margin-bottom: 1rem;">
                  <?php echo htmlspecialchars($contrato['categoria']); ?> | 
                  Assinado: <?php echo date("d/m/Y", strtotime($contrato['data_assinatura'])); ?> | 
                  Válido até: <?php echo date("d/m/Y", strtotime($contrato['data_validade'])); ?>
                  <?php if ($contrato['valor']): ?>
                    <br>Valor: R$ <?php echo number_format($contrato['valor'], 2, ',', '.'); ?>
                  <?php endif; ?>
                </p>

                <div class="contract-actions">
                  <?php if ($contrato['arquivo_pdf']): ?>
                    <a href="../Docs/<?php echo htmlspecialchars($contrato['arquivo_pdf']); ?>" 
                       download class="btn-small" style="background: hsl(var(--primary)); color: white;">
                      📄 Baixar
                    </a>
                  <?php endif; ?>
                  <button class="btn-small btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($contrato)); ?>)">
                    ✏️ Editar
                  </button>
                  <form method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este contrato?')">
                    <input type="hidden" name="id_contrato" value="<?php echo $contrato['id_contrato']; ?>">
                    <button type="submit" name="delete_contract" class="btn-small btn-delete">
                      🗑️ Excluir
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

  <!-- Adding modal for adding new contracts -->
  <div class="modal-overlay" id="addModal">
    <div class="contract-modal">
      <form method="post" enctype="multipart/form-data">
        <h2 style="margin-bottom: 1.5rem; color: hsl(var(--primary));">Adicionar Novo Contrato</h2>
        
        <div class="form-group">
          <label for="nome_fornecedor">Nome do Fornecedor *</label>
          <input type="text" id="nome_fornecedor" name="nome_fornecedor" required>
        </div>

        <div class="form-group">
          <label for="categoria">Categoria *</label>
          <select id="categoria" name="categoria" required>
            <option value="">Selecione uma categoria</option>
            <option value="Decoração">Decoração</option>
            <option value="Buffet">Buffet</option>
            <option value="Fotografia">Fotografia</option>
            <option value="Música">Música</option>
            <option value="Transporte">Transporte</option>
            <option value="Vestido/Terno">Vestido/Terno</option>
            <option value="Flores">Flores</option>
            <option value="Outros">Outros</option>
          </select>
        </div>

        <div class="form-group">
          <label for="arquivo_pdf">Arquivo PDF</label>
          <input type="file" id="arquivo_pdf" name="arquivo_pdf" accept=".pdf">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="data_assinatura">Data de Assinatura *</label>
            <input type="date" id="data_assinatura" name="data_assinatura" required>
          </div>

          <div class="form-group">
            <label for="data_validade">Data de Validade *</label>
            <input type="date" id="data_validade" name="data_validade" required>
          </div>
        </div>

        <div class="form-group">
          <label for="valor">Valor (R$)</label>
          <input type="number" id="valor" name="valor" step="0.01" min="0">
        </div>

        <div class="form-group">
          <label for="observacoes">Observações</label>
          <textarea id="observacoes" name="observacoes" placeholder="Observações adicionais sobre o contrato..."></textarea>
        </div>

        <div class="form-row">
          <button type="submit" name="add_contract" class="btn-primary">Salvar Contrato</button>
          <button type="button" class="btn-outline" onclick="closeModal('addModal')">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Adding modal for editing contracts -->
  <div class="modal-overlay" id="editModal">
    <div class="contract-modal">
      <form method="post">
        <h2 style="margin-bottom: 1.5rem; color: hsl(var(--primary));">Editar Contrato</h2>
        
        <input type="hidden" id="edit_id_contrato" name="id_contrato">
        
        <div class="form-group">
          <label for="edit_nome_fornecedor">Nome do Fornecedor *</label>
          <input type="text" id="edit_nome_fornecedor" name="nome_fornecedor" required>
        </div>

        <div class="form-group">
          <label for="edit_categoria">Categoria *</label>
          <select id="edit_categoria" name="categoria" required>
            <option value="">Selecione uma categoria</option>
            <option value="Decoração">Decoração</option>
            <option value="Buffet">Buffet</option>
            <option value="Fotografia">Fotografia</option>
            <option value="Música">Música</option>
            <option value="Transporte">Transporte</option>
            <option value="Vestido/Terno">Vestido/Terno</option>
            <option value="Flores">Flores</option>
            <option value="Outros">Outros</option>
          </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="edit_data_assinatura">Data de Assinatura *</label>
            <input type="date" id="edit_data_assinatura" name="data_assinatura" required>
          </div>

          <div class="form-group">
            <label for="edit_data_validade">Data de Validade *</label>
            <input type="date" id="edit_data_validade" name="data_validade" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="edit_valor">Valor (R$)</label>
            <input type="number" id="edit_valor" name="valor" step="0.01" min="0">
          </div>

          <div class="form-group">
            <label for="edit_status">Status</label>
            <select id="edit_status" name="status">
              <option value="ativo">Ativo</option>
              <option value="vencido">Vencido</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="edit_observacoes">Observações</label>
          <textarea id="edit_observacoes" name="observacoes" placeholder="Observações adicionais sobre o contrato..."></textarea>
        </div>

        <div class="form-row">
          <button type="submit" name="edit_contract" class="btn-primary">Salvar Alterações</button>
          <button type="button" class="btn-outline" onclick="closeModal('editModal')">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="footer">
  </footer>

  <script>
    function openAddModal() {
      document.getElementById("addModal").classList.add("active");
    }

    function openEditModal(contrato) {
      document.getElementById("edit_id_contrato").value = contrato.id_contrato;
      document.getElementById("edit_nome_fornecedor").value = contrato.nome_fornecedor;
      document.getElementById("edit_categoria").value = contrato.categoria;
      document.getElementById("edit_data_assinatura").value = contrato.data_assinatura;
      document.getElementById("edit_data_validade").value = contrato.data_validade;
      document.getElementById("edit_valor").value = contrato.valor || '';
      document.getElementById("edit_status").value = contrato.status;
      document.getElementById("edit_observacoes").value = contrato.observacoes || '';
      
      document.getElementById("editModal").classList.add("active");
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove("active");
    }

    // Close modal when clicking outside
    document.addEventListener("click", function(event) {
      const modals = document.querySelectorAll(".modal-overlay");
      modals.forEach(modal => {
        if (modal.classList.contains("active") && !event.target.closest(".contract-modal") && !event.target.closest("button[onclick*='Modal']")) {
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

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function(e) {
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

  </script>
</body>

</html>
