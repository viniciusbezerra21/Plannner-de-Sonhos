<?php
session_start();
require_once "../config/conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuario_id = (int)$_SESSION["usuario_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_notificacoes') {
    $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
    
    try {
        $sql = "UPDATE usuarios SET notificacoes_email = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$notificacoes_email, $usuario_id]);
        
        $_SESSION['success_message'] = "Preferências de notificação atualizadas!";
        header("Location: perfil.php");
        exit;
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar preferências: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_tema') {
    $tema_cor = 'azul'; // Always set to blue tones
    
    try {
        $sql = "UPDATE usuarios SET tema_cor = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tema_cor, $usuario_id]);
        
        $_SESSION['success_message'] = "Tema atualizado para tons de azul!";
        header("Location: perfil.php");
        exit;
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar tema: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_perfil') {
    $nome = trim($_POST['nome']);
    $nome_conjuge = trim($_POST['nome_conj']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $senha = trim($_POST['senha']);
    
    // Handle photo upload
    $foto_perfil = $_SESSION['foto_perfil'] ?? 'default.png';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'user_' . $usuario_id . '_' . time() . '.' . $ext;
            $upload_path = __DIR__ . '/fotos/' . $new_filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                if ($foto_perfil !== 'default.png' && file_exists(__DIR__ . '/fotos/' . $foto_perfil)) {
                    unlink(__DIR__ . '/fotos/' . $foto_perfil);
                }
                $foto_perfil = $new_filename;
            }
        }
    }
    
    // Update database
    try {
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = ?, nome_conjuge = ?, email = ?, telefone = ?, senha = ?, foto_perfil = ? WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $nome_conjuge, $email, $telefone, $senha_hash, $foto_perfil, $usuario_id]);
        } else {
            $sql = "UPDATE usuarios SET nome = ?, nome_conjuge = ?, email = ?, telefone = ?, foto_perfil = ? WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $nome_conjuge, $email, $telefone, $foto_perfil, $usuario_id]);
        }
        
        $_SESSION['foto_perfil'] = $foto_perfil;
        $_SESSION['success_message'] = "Perfil atualizado com sucesso!";
        
        header("Location: perfil.php");
        exit;
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar perfil: " . $e->getMessage();
    }
}

$usuario = null;
try {
    $sql = "SELECT nome, nome_conjuge, telefone, email, foto_perfil, notificacoes_email, tema_cor FROM usuarios WHERE id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
    
    if (!empty($usuario['foto_perfil'])) {
        $_SESSION['foto_perfil'] = $usuario['foto_perfil'];
    }
} catch (PDOException $e) {
    error_log("Error fetching user profile: " . $e->getMessage());
    $error_message = "Erro ao carregar perfil. Por favor, tente novamente.";
}

$atividades = [];
try {
    $sql = "SELECT tipo_atividade, descricao, data_atividade 
            FROM atividades_usuario 
            WHERE id_usuario = ? 
            ORDER BY data_atividade DESC 
            LIMIT 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching activities: " . $e->getMessage());
}

$foto_perfil = $usuario["foto_perfil"] ?? "default.png";
$tema_cor = $usuario["tema_cor"] ?? "azul";
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .profile-card {
      display: flex;
      align-items: center;
      gap: 2rem;
      padding: 3rem 2rem;
      border-radius: 1rem;
      grid-column: span 2;
      background: hsl(var(--card-background, 0, 0%, 100%));
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      margin: 0 auto;
    }

    .profile-photo {
      flex-shrink: 0;
      display: inline-block;
    }

    .profile-photo img,
    .profile-placeholder {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      display: block;
    }

    .profile-placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      font-size: 3rem;
      font-weight: bold;
      user-select: none;
    }

    .profile-info {
      flex: 1;
    }

    .profile-card h2 {
      font-size: 1.75rem;
      margin-bottom: 0.5rem;
      color: hsl(var(--foreground));
    }

    .profile-card p {
      margin-bottom: 0.25rem;
      color: hsl(var(--muted-foreground));
    }

    .profile-card .btn-primary {
      margin-top: 1.5rem;
      display: inline-block;
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      font-weight: 600;
      transition: all 0.3s;
      cursor: pointer;
      border: none;
    }

    .profile-card .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }

    /* Adicionando estilos para o modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(2px);
    }

    .modal-content {
      background-color: white;
      margin: 2% auto;
      padding: 2rem;
      border-radius: 1rem;
      width: 90%;
      max-width: 500px;
      height: auto;
      max-height: 90vh;
      overflow: visible;
      position: relative;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 2rem;
      font-weight: bold;
      cursor: pointer;
      color: #999;
    }

    .close:hover {
      color: #333;
    }

    .modal-profile-photo img,
    .modal-profile-placeholder {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
      margin: 0 auto;
      display: block;
    }

    .modal-profile-placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      font-size: 3rem;
      font-weight: bold;
      user-select: none;
    }

    .custom-file {
      margin-top: 1rem;
      text-align: center;
    }

    .custom-file input[type="file"] {
      display: none;
    }

    .custom-file label {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      padding: 0.6rem 1.2rem;
      border-radius: 9999px;
      cursor: pointer;
      font-weight: 600;
      display: inline-block;
      transition: all 0.3s;
    }

    .custom-file label::before {
      content: "";
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      transition: left 0.6s;
    }

    .custom-file label:hover {
      animation: glow 0.5s ease-in-out;
    }

    .form-group {
      margin-bottom: 1rem;
      text-align: left;
    }

    .form-group label {
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: block;
      color: hsl(var(--foreground));
    }

    .form-group input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.5rem;
      font-size: 1rem;
      transition: all 0.2s;
      box-sizing: border-box;
    }

    .form-group input:focus {
      outline: none;
      border-color: hsl(var(--primary));
      box-shadow: 0 0 0 3px hsl(var(--primary)/0.2);
    }

    .btn-submit {
      display: block;
      width: 100%;
      margin-top: 1.5rem;
      padding: 0.75rem;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      font-weight: 600;
      border: none;
      border-radius: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .alert {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 0.5rem;
      font-weight: 500;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    @media (max-width: 768px) {
      .profile-card {
        grid-column: span 1;
      }

      .modal-content {
        margin: 1% auto;
        width: 95%;
        max-height: 95vh;
        padding: 1.5rem;
      }
    }

    .feature-benefits li {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }

    .feature-benefits svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
    }

    /* Enhanced card styles for interactive preferences */
    .preference-card {
      background: hsl(var(--card-background, 0, 0%, 100%));
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }

    .preference-card:hover {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      transform: translateY(-2px);
    }

    .preference-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 0;
      border-bottom: 1px solid hsl(var(--border));
    }

    .preference-item:last-child {
      border-bottom: none;
    }

    .preference-label {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 500;
      color: hsl(var(--foreground));
    }

    .preference-label svg {
      width: 20px;
      height: 20px;
      stroke: hsl(var(--primary));
    }

    /* Toggle switch styles */
    .toggle-switch {
      position: relative;
      width: 50px;
      height: 26px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: 0.4s;
      border-radius: 34px;
    }

    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: 0.4s;
      border-radius: 50%;
    }

    .toggle-switch input:checked + .toggle-slider {
      background-color: hsl(var(--primary));
    }

    .toggle-switch input:checked + .toggle-slider:before {
      transform: translateX(24px);
    }

    /* Theme button */
    .theme-button {
      padding: 0.75rem 1.5rem;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      color: white;
      border: none;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .theme-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4);
    }

    /* Activity card styles */
    .activity-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 1rem 0;
      border-bottom: 1px solid hsl(var(--border));
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: hsl(var(--primary) / 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .activity-icon svg {
      width: 20px;
      height: 20px;
      stroke: hsl(var(--primary));
    }

    .activity-content {
      flex: 1;
    }

    .activity-description {
      font-weight: 500;
      color: hsl(var(--foreground));
      margin-bottom: 0.25rem;
    }

    .activity-time {
      font-size: 0.875rem;
      color: hsl(var(--muted-foreground));
    }
  </style>
</head>

<body>
  <!-- Header -->
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
          <a href="perfil.php" class="nav-link">Perfil</a>
          <a href="../pages/fornecedores.php">Fornecedores</a>
          <a href="../pages/calendario.php" class="nav-link">Calendario</a>
          <a href="../pages/orcamento.php" class="nav-link">Orçamento</a>
        </nav>
        <button id="hamburgerBtn" class="mobile-menu-btn" onclick="toggleMobileMenu()">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
      <div id="mobileMenu" class="mobile-menu">
        <a href="../index.php" class="nav-link">Início</a>
        <a href="perfil.php" class="nav-link">Perfil</a>
        <a href="../pages/calendario.php" class="nav-link">Calendario</a>
        <a href="../pages/orcamento.php" class="nav-link">Orçamento</a>
      </div>
    </div>
  </header>

  <!-- Conteúdo -->
  <main class="page-content container" style="margin-top:5rem;">
    <div class="page-header">
      <h1 class="page-title">Meu Perfil</h1>
      <p class="page-description">Gerencie suas informações pessoais e preferências.</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <?php 
          echo htmlspecialchars($_SESSION['success_message']); 
          unset($_SESSION['success_message']);
        ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
      <div class="alert alert-error">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>
    
    <?php if ($usuario): ?>
    <div class="features-detailed-grid">
      <!-- Card Perfil -->
      <div class="profile-card">
        <div class="profile-photo">
          <img src="fotos/<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil">
        </div>
        <div class="profile-info">
          <!-- Display user data with proper fallbacks -->
          <h2><?php echo htmlspecialchars($usuario['nome'] ?? 'Nome não disponível'); ?></h2>
          <p>E-mail: <?php echo htmlspecialchars($usuario['email'] ?? 'Não informado'); ?></p>
          <p>Telefone: <?php echo htmlspecialchars($usuario['telefone'] ?? 'Não informado'); ?></p>
          <p>Cônjuge: <?php echo htmlspecialchars($usuario['nome_conjuge'] ?? 'Não informado'); ?></p>
          <button class="btn-primary" onclick="openEditModal()">Editar Perfil</button>
        </div>
      </div>

      <!-- Card Preferências -->
      <div class="preference-card">
        <div class="feature-detailed-header">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="3" />
              <path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m0-6l4.2-4.2" />
            </svg>
          </div>
          <h2 class="feature-detailed-title">Preferências</h2>
        </div>
        
        <form method="POST" id="notificationForm">
          <input type="hidden" name="action" value="atualizar_notificacoes">
          <div class="preference-item">
            <div class="preference-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
              </svg>
              Notificações por email
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="notificacoes_email" 
                     <?php echo ($usuario['notificacoes_email'] ?? 1) ? 'checked' : ''; ?>
                     onchange="document.getElementById('notificationForm').submit()">
              <span class="toggle-slider"></span>
            </label>
          </div>
    </form>
      </div>

      <!-- Card Atividades -->
      <div class="preference-card">
        <div class="feature-detailed-header">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
          </div>
          <h2 class="feature-detailed-title">Atividades Recentes</h2>
        </div>
        
        <?php if (count($atividades) > 0): ?>
          <?php foreach ($atividades as $atividade): ?>
            <div class="activity-item">
              <div class="activity-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <polyline points="9 11 12 14 22 4" />
                  <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                </svg>
              </div>
              <div class="activity-content">
                <div class="activity-description">
                  <?php echo htmlspecialchars($atividade['descricao']); ?>
                </div>
                <div class="activity-time">
                  <?php 
                    $data = new DateTime($atividade['data_atividade']);
                    echo $data->format('d/m/Y H:i');
                  ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="activity-item">
            <div class="activity-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
            </div>
            <div class="activity-content">
              <div class="activity-description">Nenhuma atividade recente</div>
              <div class="activity-time">Comece a usar o sistema para ver suas atividades aqui</div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <!-- Adicionando modal de edição de perfil -->
  <?php if ($usuario): ?>
  <div id="editModal" style="animation: staticFadeIn 0.3s ease-out;" class="modal">
    <div style="animation: slideInFromBottom 0.3s ease-out forwards;" class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2 style="text-align: center; margin-bottom: 0.5rem;">Editar Perfil</h2>

      <div class="modal-profile-photo" style="text-align: center; margin-bottom: 0.5rem;">
        <img src="fotos/<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" class="modal-profile-photo" id="modalFotoPreview">
        <div class="custom-file">
          <input type="file" id="modalFoto" name="foto" accept="image/*" onchange="previewModalFoto(event)">
          <label for="modalFoto">Trocar Foto de Perfil</label>
        </div>
      </div>

      <!-- Fixed form to properly submit with file upload -->
      <form method="POST" enctype="multipart/form-data" id="editForm" action="perfil.php">
        <input type="hidden" name="action" value="editar_perfil">
        <!-- Hidden input to handle file upload -->
        <input type="file" id="hiddenFoto" name="foto" style="display: none;">

        <div class="form-group">
          <label for="modalNome">Nome Completo</label>
          <input type="text" id="modalNome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
        </div>

        <div class="form-group">
          <label for="modalNomeConj">Nome do Cônjuge</label>
          <input type="text" id="modalNomeConj" name="nome_conj" value="<?php echo htmlspecialchars($usuario['nome_conjuge']); ?>" required>
        </div>

        <div class="form-group">
          <label for="modalEmail">E-mail</label>
          <input type="email" id="modalEmail" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
        </div>

        <div class="form-group">
          <label for="modalTelefone">Telefone</label>
          <input type="text" id="modalTelefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" required>
        </div>

        <div class="form-group">
          <label for="modalSenha">Nova Senha (opcional)</label>
          <input type="password" id="modalSenha" name="senha" placeholder="Deixe em branco para manter a senha atual">
        </div>

        <button type="submit" class="btn-submit">Salvar Alterações</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="footer">
    <div class="container footer-content">
      <div class="footer-brand">
        <a href="../index.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>
        <p class="footer-description">Organize cada detalhe do seu casamento com praticidade e elegância.</p>
      </div>
      <div class="footer-links">
        <h3>Links</h3>
        <ul>
          <li><a href="../index.php">Início</a></li>
          <li><a href="perfil.php">Perfil</a></li>
          <li><a href="../pages/calendario.php">Calendario</a></li>
          <li><a href="../pages/orcamento.php">Orçamento</a></li>
        </ul>
      </div>
      <div class="footer-modules">
        <h3>Módulos</h3>
        <ul>
          <li><a href="#">Fornecedores</a></li>
          <li><a href="#">Checklist</a></li>
          <li><a href="#" class="disabled">Relatórios</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
    </div>
  </footer>

  <script>
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");
      mobileMenu.classList.toggle("active");
      hamburgerBtn.classList.toggle("hamburger-active");
    }

    function openEditModal() {
      document.getElementById('editModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function previewModalFoto(event) {
      const file = event.target.files[0];
      if (file) {
        const foto = document.getElementById('modalFotoPreview');
        const hiddenInput = document.getElementById('hiddenFoto');
        
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        hiddenInput.files = dataTransfer.files;
        
        if (foto) {
          foto.src = URL.createObjectURL(file);
          foto.style.display = 'block';
        }
      }
    }

    // Fechar modal ao clicar fora dele
    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target === modal) {
        closeEditModal();
      }
    }
  </script>
</body>

</html>
