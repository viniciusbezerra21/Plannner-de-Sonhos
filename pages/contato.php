<?php
session_start();
require_once '../config/conexao.php';

$cookieName = "lembrar_me";

// Restore session from cookie if needed
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
  $cookieUserId = (int) $_COOKIE[$cookieName];
  if ($cookieUserId > 0) {
    $chk = $pdo->prepare("SELECT id_usuario, nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $chk->execute([$cookieUserId]);
    $u = $chk->fetch(PDO::FETCH_ASSOC);
    if ($u) {
      $_SESSION['usuario_id'] = (int)$u['id_usuario'];
      $_SESSION['nome'] = $u['nome'];
      $_SESSION['email'] = $u['email'];
      $_SESSION['foto_perfil'] = $u['foto_perfil'] ?: 'default.png';
    } else {
      setcookie($cookieName, "", time() - 3600, "/");
    }
  }
}

// Fetch user data if logged in
$user_data = null;
if (isset($_SESSION['usuario_id'])) {
  $stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
  $stmt->execute([$_SESSION['usuario_id']]);
  $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = false;
$error = false;
$errorMessage = '';

$loggedInEmail = $user_data['email'] ?? '';
$loggedInName = $user_data['nome'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $nome = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['phone'] ?? '');
    $assunto = trim($_POST['subject'] ?? '');
    $mensagem = trim($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($nome) || empty($email) || empty($mensagem)) {
        $error = true;
        $errorMessage = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMessage = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contatos (nome, email, telefone, assunto, mensagem, data_envio) VALUES (:nome, :email, :telefone, :assunto, :mensagem, NOW())");
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':telefone' => $telefone,
                ':assunto' => $assunto,
                ':mensagem' => $mensagem
            ]);
            
            $success = true;
            // Clear form data on success
            $_POST = [];
        } catch (PDOException $e) {
            $error = true;
            $errorMessage = 'Erro ao enviar mensagem. Por favor, tente novamente mais tarde.';
            // Log error for debugging (in production, use proper logging)
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos - Contato</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
    <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .user-profile {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      position: relative;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid hsl(var(--primary));
      cursor: pointer;
    }

    .user-avatar-default {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary)) 80%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      border: 2px solid hsl(var(--primary));
    }

    .profile-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      min-width: 200px;
      display: none;
      z-index: 1000;
      margin-top: 0.5rem;
    }

    .profile-dropdown.active {
      display: block;
    }

    .profile-dropdown-header {
      padding: 1rem;
      border-bottom: 1px solid hsl(var(--border));
    }

    .profile-dropdown-name {
      font-weight: 600;
      color: hsl(var(--foreground));
      margin: 0;
      font-size: 0.9rem;
    }

    .profile-dropdown-email {
      color: hsl(var(--muted-foreground));
      margin: 0;
      font-size: 0.8rem;
      margin-top: 0.25rem;
    }

    .profile-dropdown-menu {
      padding: 0.5rem 0;
    }

    .profile-dropdown-item {
      display: block;
      padding: 0.75rem 1rem;
      color: hsl(var(--foreground));
      text-decoration: none;
      transition: background-color 0.2s;
      font-size: 0.9rem;
    }

    .profile-dropdown-item:hover {
      background-color: hsl(var(--accent));
    }

    .profile-dropdown-item.logout {
      color: #ef4444;
      border-top: 1px solid hsl(var(--border));
      margin-top: 0.5rem;
    }

    .profile-dropdown-item.logout:hover {
      background-color: #fef2f2;
    }

    @media (max-width: 768px) {
      .user-profile {
        order: -1;
      }

      .profile-dropdown {
        right: -1rem;
      }
    }
    
    /* Added styles for success and error alerts */
    .alert {
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      animation: slideDown 0.3s ease-out;
    }
    
    .alert-success {
      background-color: #d1fae5;
      color: #065f46;
      border: 1px solid #6ee7b7;
    }
    
    .alert-error {
      background-color: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
    }
    
    .alert svg {
      width: 1.5rem;
      height: 1.5rem;
      flex-shrink: 0;
    }
    
    .alert-message {
      flex: 1;
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
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
                <a href="gestao-contratos.php">Gestão de Contratos</a>
                <a href="tarefas.php">Lista de Tarefas</a>
              </div>
            </div>

            <a href="contato.php" class="nav-link">Contato</a> 
            <?php if (isset($_SESSION["usuario_id"])): ?>
              <!-- Added profile dropdown for logged-in users -->
              <div class="user-profile" onclick="toggleProfileDropdown()">
                <?php if ($user_data && $user_data['foto_perfil']): ?>
                  <img src="../user/fotos/<?php echo htmlspecialchars($user_data['foto_perfil']); ?>" alt="Perfil" class="user-avatar">
                <?php else: ?>
                  <div class="user-avatar-default">
                    <?php echo strtoupper(substr($user_data['nome'] ?? 'U', 0, 1)); ?>
                  </div>
                <?php endif; ?>
                <div class="profile-dropdown-wrapper">
              <img 
                src="user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>"
                alt="Foto de perfil"
                class="profile-avatar"
                onclick="toggleProfileDropdown()"
              >
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <div class="profile-dropdown-user">
                    <img 
                      src="user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>" 
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
        <nav style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem 0; border-top: 1px solid hsl(var(--border)); margin-top: 0.5rem;">
          <a href="../index.php" class="nav-link" style="padding: 0.5rem 0">Início</a>
          <a href="funcionalidades.php" class="nav-link" style="padding: 0.5rem 0">Funcionalidades</a>
 
            <a href="contato.php" class="nav-link" style="padding: 0.5rem 0">Contato</a>

            
            <a href="../user/login.php" class="btn-primary" style="align-items: center">Login</a>
        </nav>
      </div>
    </div>
  </header>
  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header">
          <h1 class="page-title">
            Entre em
            <span class="gradient-text">Contato</span>
          </h1>
          <p class="page-description">
            Estamos aqui para ajudar você a planejar o casamento dos seus
            sonhos. Entre em contato conosco e tire todas as suas dúvidas.
          </p>
        </div>
        <div class="contact-grid">
          <div class="contact-form-card">
            <div class="contact-form-header">
              <svg
                class="message-icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor">
                <path
                  d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <h2>Envie uma Mensagem</h2>
            </div>
            
            <?php if ($success): ?>
              <!-- Display success message when form is submitted successfully -->
              <div class="alert alert-success">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <div class="alert-message">
                  <strong>Mensagem enviada com sucesso!</strong><br>
                  Obrigado pelo contato. Responderemos em breve.
                </div>
              </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
              <!-- Display error message when form submission fails -->
              <div class="alert alert-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <div class="alert-message">
                  <strong>Erro ao enviar mensagem!</strong><br>
                  <?php echo htmlspecialchars($errorMessage); ?>
                </div>
              </div>
            <?php endif; ?>
            
            <!-- Updated form to submit to itself via POST -->
            <form method="POST" action="contato.php" class="contact-form">
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Nome Completo</label>
                  <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($loggedInName ?: ($_POST['name'] ?? '')); ?>" required />
                </div>
                <div class="form-group">
                  <label for="email">E-mail</label>
                  <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($loggedInEmail ?: ($_POST['email'] ?? '')); ?>" 
                    <?php echo $loggedInEmail ? 'readonly' : ''; ?>
                    required 
                  />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="phone">Telefone</label>
                  <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" />
                </div>
                <div class="form-group">
                  <label for="subject">Assunto</label>
                  <select id="subject" name="subject" required>
                    <option value="">Selecione um assunto</option>
                    <option value="duvidas" <?php echo (($_POST['subject'] ?? '') === 'duvidas') ? 'selected' : ''; ?>>Dúvidas Gerais</option>
                    <option value="suporte" <?php echo (($_POST['subject'] ?? '') === 'suporte') ? 'selected' : ''; ?>>Suporte Técnico</option>
                    <option value="vendas" <?php echo (($_POST['subject'] ?? '') === 'vendas') ? 'selected' : ''; ?>>Informações de Vendas</option>
                    <option value="feedback" <?php echo (($_POST['subject'] ?? '') === 'feedback') ? 'selected' : ''; ?>>Feedback</option>
                    <option value="outros" <?php echo (($_POST['subject'] ?? '') === 'outros') ? 'selected' : ''; ?>>Outros</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="message">Mensagem</label>
                <textarea
                  id="message"
                  name="message"
                  rows="6"
                  placeholder="Conte-nos como podemos ajudar você..."
                  required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-full">
                <svg
                  class="send-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor">
                  <line x1="22" y1="2" x2="11" y2="13" />
                  <polygon points="22,2 15,22 11,13 2,9 22,2" />
                </svg>
                Enviar Mensagem
              </button>
            </form>
          </div>
          <div class="contact-info">
            <div class="contact-info-card">
              <div class="contact-info-header">
                <svg
                  class="heart-icon"
                  viewBox="0 0 24 24"
                  fill="currentColor">
                  <path
                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                </svg>
                <h2>Informações de Contato</h2>
              </div>

              <div class="contact-details">
                <div class="contact-detail">
                  <svg
                    class="mail-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor">
                    <path
                      d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                  </svg>
                  <div>
                    <h3>E-mail</h3>
                    <p>contato@plannerdesonhos.com</p>
                    <p>suporte@plannerdesonhos.com</p>
                  </div>
                </div>

                <div class="contact-detail">
                  <svg
                    class="phone-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor">
                    <path
                      d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                  </svg>
                  <div>
                    <h3>Telefone</h3>
                    <p>(11) 9999-9999</p>
                    <p>WhatsApp: (11) 8888-8888</p>
                  </div>
                </div>

                <div class="contact-detail">
                  <svg
                    class="map-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor">
                    <path
                      d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <div>
                    <h3>Endereço</h3>
                    <p>
                      Rua das Flores, 123<br />
                      Jardim Primavera<br />
                      São Paulo - SP, 01234-567
                    </p>
                  </div>
                </div>
                <div class="contact-detail">
                  <svg
                    class="clock-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12,6 12,12 16,14" />
                  </svg>
                  <div>
                    <h3>Horário de Atendimento</h3>
                    <p>
                      Segunda a Sexta: 9h às 18h<br />
                      Sábado: 9h às 14h<br />
                      Domingo: Fechado
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="faq-card">
          <h2>Perguntas Frequentes</h2>
          <div class="faq-items">
            <div class="faq-item">
              <h3>Como funciona o Planner de Sonhos?</h3>
              <p>
                O Planner de Sonhos é uma plataforma completa para planejamento de
                casamentos com módulos especializados para cada aspecto da
                organização.
              </p>
            </div>

            <div class="faq-item">
              <h3>Existe um período de teste gratuito?</h3>
              <p>
                Sim! Oferecemos 14 dias de teste gratuito para que você possa
                explorar todas as funcionalidades.
              </p>
            </div>

            <div class="faq-item">
              <h3>Posso cancelar a qualquer momento?</h3>
              <p>
                Claro! Você pode cancelar sua assinatura a qualquer momento
                sem taxas adicionais.
              </p>
            </div>
          </div>
        </div>
        <div class="features-cta">
          <h2 class="cta-title">Pronto para começar?</h2>
          <p class="cta-description">
            Nossa equipe está pronta para ajudar você a planejar o casamento
            dos seus sonhos. Entre em contato conosco hoje mesmo!
          </p>
          <div class="cta-buttons">
            <a href="mailto:contato@plannerdesonhos.com" class="btn btn-primary">
              <svg
                class="mail-icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor">
                <path
                  d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                <polyline points="22,6 12,13 2,6" />
              </svg>
              Enviar E-mail
            </a>
            <a
              href="https://wa.me/5511888888888"
              target="_blank"
              class="btn btn-primary">
              <svg
                class="phone-icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor">
                <path
                  d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
              </svg>
              WhatsApp
            </a>
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
              <svg
                width="16"
                height="16"
                fill="currentColor"
                viewBox="0 0 24 24">
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
            <svg
              style="width: 1rem; height: 1rem"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path
                d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>contato@plannerdesonhos.com</span>
          </div>
        </div>
        <div class="footer-modules">
          <h3>Legal</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li>
              <a href="../legal-pages/privacity-politics.html">Política de Privacidade</a>
            </li>
            <li>
              <a href="../legal-pages/uses-terms.html">Termos de Uso</a>
            </li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
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

    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.classList.toggle("active");
    }
    document.addEventListener('click', function(event) {
      const profile = document.querySelector('.user-profile');
      const dropdown = document.getElementById("profileDropdown");
      if (profile && !profile.contains(event.target)) {
        dropdown?.classList.remove("active");
      }
    });
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
