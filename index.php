<?php
session_start();

// === CONFIGURAÇÕES DO COOKIE ===
$cookieName = "lembrar_me";
$cookieTime = time() + (86400 * 30); // 30 dias

// === Simulação de login automático ===
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
  $_SESSION['usuario_id'] = $_COOKIE[$cookieName];
  $_SESSION['foto_perfil'] = "default.png";
}
if (isset($_SESSION["usuario_id"]) && empty($_SESSION['foto_perfil'])) {
  $_SESSION['foto_perfil'] = "default.png";
}

// === Logout ===
if (isset($_POST['logout'])) {
  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: index.php");
  exit;
}

require_once 'config/conexao.php';

$user_data = ['nome' => 'Usuário', 'email' => ''];
if (isset($_SESSION["usuario_id"])) {
  try {
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([(int)$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
      $user_data = $result;
      // Update session with latest photo
      if (!empty($result['foto_perfil'])) {
        $_SESSION['foto_perfil'] = $result['foto_perfil'];
      }
    }
  } catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
  }
}

$proximos_eventos = [];
if (isset($_SESSION["usuario_id"])) {
  try {
    $sql = "SELECT nome_evento, data_evento 
            FROM eventos 
            WHERE id_usuario = ? AND data_evento >= CURDATE()
            ORDER BY data_evento ASC 
            LIMIT 2";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$_SESSION['usuario_id']]);
    $proximos_eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    error_log("Error fetching events: " . $e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="Style/styles.css" />
  <link
    rel="shortcut icon"
    href="Style/assets/icon.png"
    type="image/x-icon" />
  <style>
    .login-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
    }

    .login-modal-content {
      background-color: hsl(var(--card));
      margin: 15% auto;
      padding: 2rem;
      border: none;
      border-radius: 1rem;
      width: 90%;
      max-width: 400px;
      text-align: center;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .login-modal-close {
      color: hsl(var(--muted-foreground));
      float: right;
      font-size: 28px;
      font-weight: bold;
      position: absolute;
      right: 1rem;
      top: 1rem;
      cursor: pointer;
      transition: color 0.2s;
    }

    .login-modal-close:hover {
      color: hsl(var(--foreground));
    }

    .login-modal h2 {
      color: hsl(var(--foreground));
      margin-bottom: 1rem;
      font-size: 1.5rem;
      font-weight: 600;
    }

    .login-modal p {
      color: hsl(var(--muted-foreground));
      margin-bottom: 2rem;
      font-size: 1rem;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }

      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }

      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      justify-content: center;
      align-items: center;
    }

    .modal video {
      border-radius: 20px;
      max-width: 90%;
      max-height: 80%;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
      animation: staticFadeIn 0.8s ease-out;
    }

    @media (max-width: 768px) {

      .login-modal-content {
        margin: 50% auto;
        width: 95%;
        padding: 1.5rem;
      }
    }

    /* Enhanced profile dropdown styles */
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
  <div id="loginModal" class="login-modal">
    <div class="login-modal-content">
      <span class="login-modal-close" onclick="closeLoginModal()">&times;</span>
      <h2>Você precisa estar logado</h2>
      <p>Faça login para acessar esta funcionalidade.</p>
      <a href="user/login.php" class="btn-primary">Login</a>
    </div>
  </div>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <!-- Logo -->
        <a href="index.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>

        <nav class="nav">
          <?php if (isset($_SESSION["usuario_id"])): ?>
            <a href="index.php" class="nav-link">Início</a>
            <div class="dropdown">
              <a href="pages/funcionalidades.php" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
              <div class="dropdown-menu">
                <a href="pages/calendario.php">Calendário</a>
                <a href="pages/orcamento.php">Orçamento</a>
                <a href="pages/gestao-contratos.php">Gestão de Contratos</a>
                <a href="pages/tarefas.php">Lista de Tarefas</a>
              </div>
            </div>
            <a href="pages/contato.php" class="nav-link">Contato</a>
          <?php else: ?>
            <a href="index.php" class="nav-link">Início</a>
          <?php endif; ?>

          <?php if (isset($_SESSION["usuario_id"])): ?>
            <!-- Enhanced profile dropdown with user info -->
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
            <!-- Botão login padrão -->
            <a href="user/login.php" class="btn-primary" style="align-items: center">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
  <div class="modal" id="modal">
    <video id="video" controls>
      <source src="Style/assets/Prototipo.mp4" type="video/mp4">
      Seu navegador não suporta vídeo.
    </video>
  </div>
  <main>
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <!-- Content -->
          <div class="hero-text">
            <div>
              <h1 class="hero-title">
                Organize o casamento
                <span class="text-primary">
                  perfeito
                  <svg
                    style="
                          width: 1.5rem;
                          height: 1.5rem;
                          color: hsl(var(--secondary));
                          position: absolute;
                          top: -0.5rem;
                          right: -2rem;
                          animation: pulse 3s infinite;
                        "
                    fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09z" />
                  </svg>
                </span>
                . Sem estresse, sem falhas.
              </h1>

              <p class="hero-description">
                Uma plataforma completa para cerimonialistas gerenciarem
                eventos com excelência.
              </p>
            </div>

            <div class="hero-buttons">
            <?php if (isset($_SESSION["usuario_id"])): ?>
              <a href="pages/funcionalidades.php" class="btn-primary">
                Explorar Funcionalidades
              </a>
              <?php else: ?>
              <a href="user/login.php" class="btn-primary">
                Explorar Funcionalidades
              </a>
              <?php endif; ?>
              <button class="btn-outline" id="abrirModal">Assistir Demonstração</button>
            </div>

            <!-- Trust indicators -->
            <div class="trust-indicators">
              <div class="trust-indicator">
                <svg fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                </svg>
                <span style="cursor: default">500+ casamentos organizados</span>
              </div>
              <div class="trust-indicator">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect
                    x="3"
                    y="4"
                    width="18"
                    height="18"
                    rx="2"
                    ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span style="cursor: default">Sem complicações</span>
              </div>
            </div>
          </div>

          <div class="hero-visual">
            <div class="hero-visual-card">
              <!-- Main content area -->
              <div class="hero-visual-content">
                <div class="hero-visual-header">
                  <h3 class="hero-visual-title">Próximos Eventos</h3>
                  <svg
                    style="width: 1.25rem; height: 1.25rem; color: hsl(var(--primary));"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </div>

                <div class="hero-visual-events">
                  <?php if (count($proximos_eventos) > 0): ?>
                    <?php foreach ($proximos_eventos as $evento): ?>
                      <div class="hero-visual-event">
                        <div class="hero-visual-dot"></div>
                        <div class="hero-visual-event-info">
                          <h4><?php echo htmlspecialchars($evento['nome_evento']); ?></h4>
                          <p><?php 
                            $data = new DateTime($evento['data_evento']);
                            echo $data->format('d \d\e F');
                          ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="hero-visual-event">
                      <div class="hero-visual-dot"></div>
                      <div class="hero-visual-event-info">
                        <h4>Nenhum evento próximo</h4>
                        <p>Crie seu primeiro evento</p>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <svg
                style="
                      position: absolute;
                      top: 1.5rem;
                      left: 1.5rem;
                      width: 1rem;
                      height: 1rem;
                      color: hsla(var(--primary), 0.6);
                      animation: pulse 3s infinite;
                    "
                fill="currentColor"
                viewBox="0 0 24 24">
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
              <svg
                style="
                      position: absolute;
                      bottom: 4rem;
                      right: 2rem;
                      width: 0.75rem;
                      height: 0.75rem;
                      color: hsla(var(--secondary), 0.6);
                      animation: pulse 4s infinite;
                    "
                fill="currentColor"
                viewBox="0 0 24 24">
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="stats">
      <div class="container">
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-number">10,000+</div>
            <div class="stat-label">Casamentos Organizados</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">98%</div>
            <div class="stat-label">Satisfação dos Clientes</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">50+</div>
            <div class="stat-label">Funcionalidades</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Suporte Disponível</div>
          </div>
        </div>
      </div>
    </section>

    <section class="features">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Por que usar nosso sistema?</h2>
          <p class="section-description">
            Descubra como nossa plataforma pode transformar a organização dos
            seus eventos
          </p>
        </div>

        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
            <h3 class="feature-title">Visão descomplicada dos dados</h3>
            <p class="feature-description">
              Interface clara e amigável para acompanhar todos os detalhes do
              evento de forma intuitiva.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path
                  d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <h3 class="feature-title">Flexibilidade na gestão de parcelas</h3>
            <p class="feature-description">
              Controle personalizado de pagamentos com opções flexíveis para
              cada cliente.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                <polyline points="9,11 12,14 15,11"></polyline>
                <line x1="12" y1="2" x2="12" y2="14"></line>
              </svg>
            </div>
            <h3 class="feature-title">Listagem de itens</h3>
            <p class="feature-description">
              Checklist completo com fornecedores, status e quantidade de cada
              item necessário.
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <h3 class="feature-title">Visão de datas e prazos</h3>
            <p class="feature-description">
              Calendário integrado com eventos, lembretes e prazos importantes
              organizados.
            </p>
          </div>
        </div>

        <div style="position: relative; margin-top: 4rem; text-align: center">
          <div
            style="
                  display: inline-flex;
                  align-items: center;
                  gap: 0.5rem;
                  padding: 0.75rem 1.5rem;
                  background: rgba(225, 190, 231, 0.1);
                  border-radius: 9999px;
                ">
            <span
              style="
                    width: 0.5rem;
                    height: 0.5rem;
                    background: hsl(var(--primary));
                    border-radius: 50%;
                    animation: pulse 2s infinite;
                  "></span>
            <span
              style="
                    font-family: 'Roboto', sans-serif;
                    font-size: 0.875rem;
                    color: hsl(var(--muted-foreground));
                    cursor: default;
                  ">
              Mais de 50 funcionalidades para facilitar seu trabalho
            </span>
            <span
              style="
                    width: 0.5rem;
                    height: 0.5rem;
                    background: hsl(var(--secondary));
                    border-radius: 50%;
                    animation: pulse 3s infinite;
                  "></span>
          </div>
        </div>
      </div>
    </section>

    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2 class="cta-title">Pronto para começar?</h2>
          <p class="cta-description">
            Junte-se a milhares de casais que já organizaram o casamento dos
            sonhos com o Planner de Sonhos.
          </p>
          <?php if (isset($_SESSION["usuario_id"])): ?>
          <a href="pages/funcionalidades.php" class="btn-primary">
            Explorar Funcionalidades
            <svg
              style="width: 1rem; height: 1rem"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </a>
          <?php else: ?>
            <a href="user/login.php" class="btn-primary">
            Explorar Funcionalidades
            <svg
              style="width: 1rem; height: 1rem"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <a href="index.php" class="logo">
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
        <div class="footer-links">
          <h3>Navegação</h3>
          <ul>
            <li><a href="index.php">Início</a></li>
            <li>
              <a href="pages/funcionalidades.php">Funcionalidades</a>
            </li>
            <li>
              <a href="pages/contato.php">Contato</a>
            </li>
          </ul>
        </div>
        <div class="footer-modules">
          <h3>Legal</h3>
          <ul>
            <li><a href="legal-pages/about.html">Sobre</a></li>
            <li>
              <a href="legal-pages/privacity-politics.html">Política de Privacidade</a>
            </li>
            <li><a href="legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
        <div
          style="
                display: flex;
                align-items: center;
                gap: 0.25rem;
                font-size: 0.875rem;
                color: hsl(var(--muted-foreground));
              ">
          <span>Feito com</span>
          <svg
            style="
                  width: 1rem;
                  height: 1rem;
                  color: hsl(var(--primary));
                  margin: 0 0.25rem;
                "
            fill="currentColor"
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

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById("profileDropdown");
      const wrapper = document.querySelector('.profile-dropdown-wrapper');
      
      if (dropdown && wrapper && !wrapper.contains(event.target)) {
        dropdown.classList.remove("active");
      }
    });

    function openLoginModal() {
      document.getElementById("loginModal").style.display = "block";
    }

    function closeLoginModal() {
      document.getElementById("loginModal").style.display = "none";
    }

    window.onclick = function(event) {
      const modal = document.getElementById("loginModal");
      if (event.target === modal) {
        closeLoginModal();
      }
    };

    const successMessage = document.getElementById("mensagemSucesso");
    if (successMessage) {
      setTimeout(() => {
        successMessage.style.animation = "slideOut 0.3s ease-in forwards";
        setTimeout(() => {
          successMessage.remove();
        }, 300);
      }, 5000);
    }
    const btn = document.getElementById('abrirModal')
    const modal = document.getElementById('modal')
    const video = document.getElementById('video')

    btn.addEventListener('click', () => {
      modal.style.display = 'flex'
      video.currentTime = 0
      video.play()
    })

    video.addEventListener('ended', () => {
      modal.style.display = 'none'
      video.pause()
    })

    modal.addEventListener('click', e => {
      if (e.target === modal) {
        modal.style.display = 'none'
        video.pause()
      }
    })
  </script>
</body>

</html>
