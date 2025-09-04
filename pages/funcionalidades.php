<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<<<<<<< HEAD

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Funcionalidades - WeddingEasy</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
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
=======
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WeddingEasy</title>
    <link rel="stylesheet" href="../Style/styles.css" />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
      rel="stylesheet"
    />
    <style>
>>>>>>> bb362d3 (Titulos, pagina login ADM)
      .user-profile {
        order: -1;
      }

      .profile-dropdown {
        right: -1rem;
      }
    }
  </style>
</head>

<body>
  <!-- Header -->
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
          <span class="logo-text">WeddingEasy</span>
        </a>

        <!-- Navigation Desktop -->
        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>

          <!-- Dropdown Funcionalidades -->
          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <div class="dropdown">
              <a href="funcionalidades.php" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
              <div class="dropdown-menu">
                <a href="calendario.php">Calendário</a>
                <a href="orcamento.php">Orçamento</a>
                <a href="gestao-contratos.php">Gestão de Contratos</a>
                <a href="tarefas.php">Lista de Tarefas</a>
              </div>
            </div>
          <?php else: ?>
            <a href="funcionalidades.php" class="nav-link">Funcionalidades</a>
          <?php endif; ?>


          <?php if (isset($_SESSION['usuario_logado'])): ?>
         
            <a href="contato.php" class="nav-link">Contato</a>
            <?php else: ?>
              <a href="#" onclick="openLoginModal()">
              <?php endif; ?>


              <?php if (isset($_SESSION['usuario_logado'])): ?>
                <!-- Usuário logado -->
                <div class="user-profile">
                  <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('../uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                    <img src="../uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>"
                      alt="Foto do perfil" class="user-avatar" onclick="toggleProfileDropdown()" />
                  <?php else: ?>
                    <div class="user-avatar-default" onclick="toggleProfileDropdown()">
                      <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>

                  <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                      <p class="profile-dropdown-name">
                        <?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?>
                      </p>
                      <p class="profile-dropdown-email">
                        <?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?>
                      </p>
                    </div>
                    <div class="profile-dropdown-menu">
                      <a href="../user/perfil.php" class="profile-dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                          style="margin-right: 0.5rem; vertical-align: middle;">
                          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                          <circle cx="12" cy="7" r="4" />
                        </svg>
                        Meu Perfil
                      </a>
                      <a href="dashboard.php" class="profile-dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                          style="margin-right: 0.5rem; vertical-align: middle;">
                          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                          <line x1="9" y1="9" x2="15" y2="9" />
                          <line x1="9" y1="15" x2="15" y2="15" />
                        </svg>
                        Dashboard
                      </a>
                      <a href="casamento.php" class="profile-dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                          style="margin-right: 0.5rem; vertical-align: middle;">
                          <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                        Meu Casamento
                      </a>
                      <a href="../user/logout.php" class="profile-dropdown-item logout">
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
                <a href="../user/login.php" class="btn-primary" style="align-items: center">Login</a>
              <?php endif; ?>
        </nav>

        <!-- CTA Button Desktop -->
        <button class="btn-primary" style="display: none">
          Solicitar Demonstração
        </button>

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
          <a href="../index.php" class="nav-link" style="padding: 0.5rem 0">Início</a>
          <a href="funcionalidades.php" class="nav-link" style="padding: 0.5rem 0">Funcionalidades</a>
          <a href="contato.php" class="nav-link" style="padding: 0.5rem 0">Contato</a>

          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <div style="border-top: 1px solid hsl(var(--border)); margin-top: 1rem; padding-top: 1rem;">
              <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('../uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                  <img src="../uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>"
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
              <a href="../user/perfil.php" class="nav-link" style="padding: 0.5rem 0">Meu Perfil</a>
              <a href="dashboard.php" class="nav-link" style="padding: 0.5rem 0">Dashboard</a>
              <a href="casamento.php" class="nav-link" style="padding: 0.5rem 0">Meu Casamento</a>
              <a href="../user/logout.php" class="nav-link" style="padding: 0.5rem 0; color: #ef4444;">Sair</a>
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
            Funcionalidades que fazem a
            <span class="gradient-text">diferença</span>
          </h1>
          <p class="page-description">
            Descubra todas as ferramentas que o WeddingEasy oferece para
            tornar o planejamento do seu casamento uma experiência única e sem
            estresse.
          </p>
        </div>
        <?php if (isset($_SESSION['usuario_logado'])): ?>
          <a href="calendario.php">
          <?php else: ?>
            <a href="#" onclick="openLoginModal()">
            <?php endif; ?>

            <div class="features-detailed-grid">
              <div class="feature-detailed-card">
                <div class="feature-detailed-header">
                  <div class="feature-icon calendar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                      <line x1="16" y1="2" x2="16" y2="6" />
                      <line x1="8" y1="2" x2="8" y2="6" />
                      <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="feature-detailed-title">Calendário Inteligente</h3>
                    <p class="feature-detailed-description">
                      Organize todas as datas importantes, compromissos com
                      fornecedores e prazos em um calendário visual e intuitivo.
                    </p>
                  </div>
                </div>
                <ul class="feature-benefits">
                  <li>
                    <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Lembretes automáticos
                  </li>
                  <li>
                    <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Sincronização com Google Calendar
                  </li>
                  <li>
                    <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Visualização mensal/semanal/diária
                  </li>
                </ul>
              </div>
          </a>

          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <a href="orcamento.php">
            <?php else: ?>
              <a href="#" onclick="openLoginModal()">
              <?php endif; ?>
              <div class="feature-detailed-card">
                <div class="feature-detailed-header">
                  <div class="feature-icon dollar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <line x1="12" y1="1" x2="12" y2="23" />
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="feature-detailed-title">
                      Controle Financeiro Completo
                    </h3>
                    <p class="feature-detailed-description">
                      Gerencie seu orçamento de forma inteligente com relatórios
                      detalhados e controle de gastos por categoria.
                    </p>
                  </div>
                </div>
                <ul class="feature-benefits">
                  <li>
                    <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Orçamento por categoria
                  </li>
                  <li>
                    <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Relatórios financeiros
                  </li>
                  <li>
                    <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    Controle de pagamentos
                  </li>
                </ul>
              </div>
            </a>

            <?php if (isset($_SESSION['usuario_logado'])): ?>
              <a href="gestao-contratos.php">
              <?php else: ?>
                <a href="#" onclick="openLoginModal()">
                <?php endif; ?>
                <div class="feature-detailed-card">
                  <div class="feature-detailed-header">
                    <div class="feature-icon file-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14,2 14,8 20,8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <polyline points="10,9 9,9 8,9" />
                      </svg>
                    </div>
                    <div>
                      <h3 class="feature-detailed-title">Gestão de Contratos</h3>
                      <p class="feature-detailed-description">
                        Centralize todos os contratos e documentos importantes em um
                        local seguro e organizado.
                      </p>
                    </div>
                  </div>
                  <ul class="feature-benefits">
                    <li>
                      <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                        <path
                          d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                      Armazenamento seguro
                    </li>
                    <li>
                      <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                        <path
                          d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                      Lembretes de vencimento
                    </li>
                    <li>
                      <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                        <path
                          d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                      Assinatura digital
                    </li>
                  </ul>
                </div>
              </a>

              <?php if (isset($_SESSION['usuario_logado'])): ?>
                <a href="tarefas.php">
                <?php else: ?>
                  <a href="#" onclick="openLoginModal()">
                  <?php endif; ?>
                  <div class="feature-detailed-card">
                    <div class="feature-detailed-header">
                      <div class="feature-icon check-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path d="M9 11l3 3L22 4" />
                          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                      </div>
                      <div>
                        <h3 class="feature-detailed-title">
                          Lista de Tarefas Inteligente
                        </h3>
                        <p class="feature-detailed-description">
                          Sistema completo de checklist com tarefas pré-definidas e
                          personalizáveis para cada etapa do planejamento.
                        </p>
                      </div>
                    </div>
                    <ul class="feature-benefits">
                      <li>
                        <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                          <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        Templates prontos
                      </li>
                      <li>
                        <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                          <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        Priorização de tarefas
                      </li>
                      <li>
                        <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--primary))">
                          <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        Progresso visual
                      </li>
                    </ul>
                  </div>
      </div>
      </a>

      <div class="features-cta">
        <h2 class="cta-title">Por que escolher o WeddingEasy?</h2>
        <p class="cta-description">
          Mais de 10.000 casais já confiaram em nós para organizar o dia
          mais importante de suas vidas.
        </p>
        <div class="benefits-grid">
          <div class="benefit-item">
            <svg class="clock-icon" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--foreground))">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12,6 12,12 16,14" />
            </svg>
            <h3>Economia de Tempo</h3>
            <p>Reduza em 70% o tempo gasto no planejamento</p>
          </div>
          <div class="benefit-item">
            <svg class="star-icon" viewBox="0 0 24 24" fill="hsl(var(--foreground))">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
            <h3>Qualidade Garantida</h3>
            <p>98% de satisfação dos nossos usuários</p>
          </div>
          <div class="benefit-item">
            <svg class="users-icon" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--foreground))">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            <h3>Suporte Especializado</h3>
            <p>Equipe de especialistas em casamentos</p>
          </div>
        </div>
      </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
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
          <h3>Links Rápidos</h3>
          <ul>
            <li><a href="../index.php">Início</a></li>
            <li><a href="funcionalidades.php">Funcionalidades</a></li>
            <li><a href="contato.php">Contato</a></li>
          </ul>
        </div>

        <div class="footer-modules">
          <h3>Legal</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li><a href="../legal-pages/privacity-politics.html">Política de Privacidade</a></li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 WeddingEasy. Todos os direitos reservados.</p>
      </div>
    </div>
    <!-- Modal de login -->
    <div id="loginModal" class="login-modal">
      <div class="login-modal-content">
        <span class="login-modal-close" onclick="closeLoginModal()">&times;</span>
        <h2>Você precisa estar logado</h2>
        <p>Faça login para acessar esta funcionalidade.</p>
        <a href="../user/login.php" class="btn-primary">Login</a>
      </div>
    </div>

    <style>
      .login-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
      }

      .login-modal-content {
        background-color: hsl(var(--card));
        margin: 15% auto;
        padding: 2rem;
        border-radius: 0.75rem;
        width: 90%;
        max-width: 400px;
        text-align: center;
        color: hsl(var(--foreground));
      }

      .login-modal-close {
        position: absolute;
        top: 0.5rem;
        right: 1rem;
        font-size: 1.5rem;
        cursor: pointer;
      }

      .login-modal a.btn-primary {
        margin-top: 1rem;
        display: inline-block;
      }
    </style>

  </footer>
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

    // Fechar dropdown quando clicar fora
    document.addEventListener('click', function (event) {
      const profile = document.querySelector('.user-profile');
      const dropdown = document.getElementById("profileDropdown");

      if (profile && !profile.contains(event.target)) {
        dropdown?.classList.remove("active");
      }
    });

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
  </script>
  <script>
    function openLoginModal() {
      document.getElementById("loginModal").style.display = "block";
    }

    function closeLoginModal() {
      document.getElementById("loginModal").style.display = "none";
    }

    // Fechar modal ao clicar fora do conteúdo
    window.onclick = function (event) {
      const modal = document.getElementById("loginModal");
      if (event.target === modal) {
        closeLoginModal();
      }
    };
  </script>

</body>

</html>