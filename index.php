<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WeddingEasy - Planejamento de Casamentos</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="Style/styles.css" />
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

    .mensagem-sucesso {
      position: fixed;
      top: 1rem;
      right: 1rem;
      background-color: #E8F5E8;
      border: 1px solid #4CAF50;
      color: #4CAF50;
      padding: 1rem 1.5rem;
      border-radius: 0.75rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      animation: slideIn 0.3s ease-out;
    }

    /* Modal de Login */
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

    @media (max-width: 768px) {
      .user-profile {
        order: -1;
      }

      .profile-dropdown {
        right: -1rem;
      }

      .mensagem-sucesso {
        top: 0.5rem;
        right: 0.5rem;
        left: 0.5rem;
      }

      .login-modal-content {
        margin: 50% auto;
        width: 95%;
        padding: 1.5rem;
      }
    }
  </style>
</head>

<body>
  <!-- Mensagem de sucesso -->
  <?php
  if (!empty($_SESSION['mensagem_sucesso'])) {
    echo '<div class="mensagem-sucesso" id="mensagemSucesso">' . $_SESSION['mensagem_sucesso'] . '</div>';
    unset($_SESSION['mensagem_sucesso']);
  }
  ?>

  <!-- Modal de Login (movido para o topo) -->
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
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">WeddingEasy</span>
        </a>

        <!-- Navigation Desktop -->
        <nav class="nav">
          <a href="index.php" class="nav-link">Início</a>

          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <!-- Usuário logado - Dropdown Funcionalidades -->
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
            <a href="pages/funcionalidades.php" class="nav-link">Funcionalidades</a>
          <?php endif; ?>

          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <!-- Usuário logado -->
            <div class="user-profile">
              <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                <img src="uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>"
                  alt="Foto do perfil" class="user-avatar" onclick="toggleProfileDropdown()" />
              <?php else: ?>
                <div class="user-avatar-default" onclick="toggleProfileDropdown()">
                  <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                </div>
              <?php endif; ?>

              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <p class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?>
                  </p>
                  <p class="profile-dropdown-email"><?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?>
                  </p>
                </div>
                <div class="profile-dropdown-menu">
                  <a href="user/perfil.php" class="profile-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      style="margin-right: 0.5rem; vertical-align: middle;">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                      <circle cx="12" cy="7" r="4" />
                    </svg>
                    Meu Perfil
                  </a>
                  <a href="pages/dashboard.php" class="profile-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      style="margin-right: 0.5rem; vertical-align: middle;">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                      <line x1="9" y1="9" x2="15" y2="9" />
                      <line x1="9" y1="15" x2="15" y2="15" />
                    </svg>
                    Dashboard
                  </a>
                  <a href="pages/casamento.php" class="profile-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      style="margin-right: 0.5rem; vertical-align: middle;">
                      <path
                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                    Meu Casamento
                  </a>
                  <a href="user/logout.php" class="profile-dropdown-item logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      style="margin-right: 0.5rem; vertical-align: middle;">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16,17 21,12 16,7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Sair
                  </a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <!-- Usuário não logado -->
            <a href="user/login.php" class="btn-primary" style="align-items: center">Login</a>
          <?php endif; ?>
        </nav>

        <!-- Mobile menu button -->
        <button id="hamburgerBtn" class="mobile-menu-btn" onclick="toggleMobileMenu()">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>

      <!-- Mobile Menu -->
      <div id="mobileMenu" class="mobile-menu">
        <nav style="
              display: flex;
              flex-direction: column;
              gap: 1rem;
              padding: 1rem 0;
              border-top: 1px solid hsl(var(--border));
              margin-top: 0.5rem;
            ">
          <a href="index.php" class="nav-link" style="padding: 0.5rem 0">Início</a>
          <a href="pages/funcionalidades.php" class="nav-link" style="padding: 0.5rem 0">Funcionalidades</a>

          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <a href="pages/contato.php" class="nav-link" style="padding: 0.5rem 0">Contato</a>

            <div style="border-top: 1px solid hsl(var(--border)); margin-top: 1rem; padding-top: 1rem;">
              <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                  <img src="uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>"
                    alt="Foto do perfil" class="user-avatar" style="width: 32px; height: 32px;" />
                <?php else: ?>
                  <div class="user-avatar-default" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div style="font-weight: 600; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?>
                  </div>
                  <div style="font-size: 0.8rem; color: hsl(var(--muted-foreground));">
                    <?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?>
                  </div>
                </div>
              </div>
              <a href="user/perfil.php" class="nav-link" style="padding: 0.5rem 0">Meu Perfil</a>
              <a href="pages/dashboard.php" class="nav-link" style="padding: 0.5rem 0">Dashboard</a>
              <a href="pages/casamento.php" class="nav-link" style="padding: 0.5rem 0">Meu Casamento</a>
              <a href="user/logout.php" class="nav-link" style="padding: 0.5rem 0; color: #ef4444;">Sair</a>
            </div>
          <?php else: ?>
            <a href="user/login.php" class="btn-primary" style="align-items: center">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <!-- Hero Section redesenhada com layout em grid e visual aprimorado -->
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
                  <svg style="
                        width: 1.5rem;
                        height: 1.5rem;
                        color: hsl(var(--secondary));
                        position: absolute;
                        top: -0.5rem;
                        right: -2rem;
                        animation: pulse 3s infinite;
                      " fill="currentColor" viewBox="0 0 24 24">
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
              <a href="pages/funcionalidades.php" class="btn-primary">
                Explorar Funcionalidades
              </a>
              <a href="pages/#.php" class="btn-outline">Assistir Demonstração</a>
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
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span style="cursor: default">Sem complicações</span>
              </div>
            </div>
          </div>

          <!-- Visual -->
          <div class="hero-visual">
            <div class="hero-visual-card">
              <!-- Main content area -->
              <div class="hero-visual-content">
                <div class="hero-visual-header">
                  <h3 class="hero-visual-title">Próximos Eventos</h3>
                  <svg style="
                        width: 1.25rem;
                        height: 1.25rem;
                        color: hsl(var(--primary));
                      " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </div>

                <div class="hero-visual-events">
                  <div class="hero-visual-event">
                    <div class="hero-visual-dot"></div>
                    <div class="hero-visual-event-info">
                      <h4>Casamento Maria & João</h4>
                      <p>15 de Dezembro</p>
                    </div>
                  </div>

                  <div class="hero-visual-event">
                    <div class="hero-visual-dot"></div>
                    <div class="hero-visual-event-info">
                      <h4>Casamento Ana & Pedro</h4>
                      <p>22 de Dezembro</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Floating hearts -->
              <svg style="
                    position: absolute;
                    top: 1.5rem;
                    left: 1.5rem;
                    width: 1rem;
                    height: 1rem;
                    color: hsla(var(--primary), 0.6);
                    animation: pulse 3s infinite;
                  " fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
              <svg style="
                    position: absolute;
                    bottom: 4rem;
                    right: 2rem;
                    width: 0.75rem;
                    height: 0.75rem;
                    color: hsla(var(--secondary), 0.6);
                    animation: pulse 4s infinite;
                  " fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
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

    <!-- Features Section com cards aprimorados e hover effects -->
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
          <!-- Feature 1 -->
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

          <!-- Feature 2 -->
          <div class="feature-card">
            <div class="feature-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <h3 class="feature-title">Flexibilidade na gestão de parcelas</h3>
            <p class="feature-description">
              Controle personalizado de pagamentos com opções flexíveis para
              cada cliente.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="feature-card">
            <div class="feature-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
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

          <!-- Feature 4 -->
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

        <!-- Decorative elements -->
        <div style="position: relative; margin-top: 4rem; text-align: center">
          <div style="
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                background: rgba(225, 190, 231, 0.1);
                border-radius: 9999px;
              ">
            <span style="
                  width: 0.5rem;
                  height: 0.5rem;
                  background: hsl(var(--primary));
                  border-radius: 50%;
                  animation: pulse 2s infinite;
                "></span>
            <span style="
                  font-family: 'Roboto', sans-serif;
                  font-size: 0.875rem;
                  color: hsl(var(--muted-foreground));
                  cursor: default;
                ">
              Mais de 100 funcionalidades para facilitar seu trabalho
            </span>
            <span style="
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

    <!-- CTA Section -->
    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2 class="cta-title">Pronto para começar?</h2>
          <p class="cta-description">
            Junte-se a milhares de casais que já organizaram o casamento dos
            sonhos com o WeddingEasy.
          </p>
          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <a href="pages/dashboard.php" class="btn-primary">
              Acessar Dashboard
              <svg style="width: 1rem; height: 1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M5 12h14M12 5l7 7-7 7" />
              </svg>
            </a>
          <?php else: ?>
            <a href="pages/funcionalidades.php" class="btn-primary">
              Explorar Funcionalidades
              <svg style="width: 1rem; height: 1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M5 12h14M12 5l7 7-7 7" />
              </svg>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <a href="index.php" class="logo">
            <div class="heart-icon">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">WeddingEasy</span>
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
            <span>contato@weddingeasy.com</span>
          </div>
        </div>
        <div class="footer-links">
          <h3>Navegação</h3>
          <ul>
            <li><a href="index.php">Início</a></li>
            <li>
              <?php if (isset($_SESSION['usuario_logado'])): ?>
                <a href="pages/funcionalidades.php">Funcionalidades</a>
              <?php else: ?>
                <a href="#" onclick="openLoginModal()">Funcionalidades</a>
              <?php endif; ?>
            </li>
            <li>
              <?php if (isset($_SESSION['usuario_logado'])): ?>
                <a href="pages/contato.php">Contato</a>
              <?php else: ?>
                <a href="#" onclick="openLoginModal()">Contato</a>
              <?php endif; ?>
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
        <p>&copy; 2024 WeddingEasy. Todos os direitos reservados.</p>
        <div style="
              display: flex;
              align-items: center;
              gap: 0.25rem;
              font-size: 0.875rem;
              color: hsl(var(--muted-foreground));
            ">
          <span>Feito com</span>
          <svg style="
                width: 1rem;
                height: 1rem;
                color: hsl(var(--primary));
                margin: 0 0.25rem;
              " fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
          <span>para cerimonialistas</span>
        </div>
      </div>
    </div>
  </footer>
  
  <!-- JavaScript para funcionalidade do menu mobile -->
  <script>
    // Mobile menu toggle
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");

      mobileMenu.classList.toggle("active");
      hamburgerBtn.classList.toggle("hamburger-active");
    }

    // Profile dropdown toggle
    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.classList.toggle("active");
    }

    // Modal functions
    function openLoginModal() {
      document.getElementById("loginModal").style.display = "block";
    }

    function closeLoginModal() {
      document.getElementById("loginModal").style.display = "none";
    }

    // Fechar dropdown quando clicar fora
    document.addEventListener('click', function (event) {
      const profile = document.querySelector('.user-profile');
      const dropdown = document.getElementById("profileDropdown");

      if (profile && !profile.contains(event.target)) {
        dropdown?.classList.remove("active");
      }
    });

    // Fecha modal ao clicar fora
    window.onclick = function (event) {
      const modal = document.getElementById("loginModal");
      if (event.target === modal) {
        closeLoginModal();
      }
    };

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });

          // Close mobile menu if open
          const mobileMenu = document.getElementById("mobileMenu");
          const hamburgerBtn = document.getElementById("hamburgerBtn");
          mobileMenu.classList.remove("active");
          hamburgerBtn.classList.remove("hamburger-active");
        }
      });
    });

    // Auto-hide success message after 5 seconds
    const successMessage = document.getElementById('mensagemSucesso');
    if (successMessage) {
      setTimeout(() => {
        successMessage.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => {
          successMessage.remove();
        }, 300);
      }, 5000);
    }
  </script>
</body>

</html>