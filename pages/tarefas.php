<?php session_start(); ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lista de Tarefas - WeddingEasy</title>
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
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
          
          <?php if (isset($_SESSION['usuario_logado'])): ?>
            <!-- Usuário logado -->
            <div class="user-profile">
              <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('../uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                <img 
                  src="../uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>" 
                  alt="Foto do perfil" 
                  class="user-avatar"
                  onclick="toggleProfileDropdown()"
                />
              <?php else: ?>
                <div class="user-avatar-default" onclick="toggleProfileDropdown()">
                  <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                </div>
              <?php endif; ?>
              
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <p class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?></p>
                  <p class="profile-dropdown-email"><?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?></p>
                </div>
                <div class="profile-dropdown-menu">
                  <a href="../user/perfil.php" class="profile-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                      <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Meu Perfil
                  </a>
                  <a href="dashboard.php" class="profile-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                      <line x1="9" y1="9" x2="15" y2="9"/>
                      <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    Dashboard
                  </a>
                  <a href="casamento.php" class="profile-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    Meu Casamento
                  </a>
                  <a href="../user/logout.php" class="profile-dropdown-item logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                      <polyline points="16,17 21,12 16,7"/>
                      <line x1="21" y1="12" x2="9" y2="12"/>
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

        <button id="hamburgerBtn" class="mobile-menu-btn" onclick="toggleMobileMenu()">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>

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
                  <img 
                    src="../uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>" 
                    alt="Foto do perfil" 
                    class="user-avatar"
                    style="width: 32px; height: 32px;"
                  />
                <?php else: ?>
                  <div class="user-avatar-default" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?></div>
                  <div style="font-size: 0.8rem; color: hsl(var(--muted-foreground));"><?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?></div>
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
            Lista de <span class="gradient-text">Tarefas Inteligente</span>
          </h1>
          <p class="page-description">
            Veja rapidamente o status, prioridade e prazos das tarefas
            essenciais para o evento.
          </p>
        </div>

        <!-- Grid de Tarefas -->
        <div class="tasks-grid" style="
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
              gap: 1.5rem;
            ">
          <!-- Card Tarefa -->
          <div class="task-card" style="
                display: flex;
                border: 1px solid hsl(var(--border));
                border-radius: 0.5rem;
                overflow: hidden;
              ">
            <div style="width: 6px; background-color: red"></div>
            <div class="task-card" style="padding: 1rem; flex: 1">
              <h3 style="margin-bottom: 0.5rem">
                Definir local da cerimônia
              </h3>
              <p style="
                    color: hsl(var(--muted-foreground));
                    font-size: 0.9rem;
                    margin-bottom: 0.5rem;
                  ">
                Responsável: Maria
              </p>
              <p style="font-size: 0.9rem; margin-bottom: 0.5rem">
                📅 Prazo: 01/09/2025
              </p>
              <p style="font-size: 0.9rem; color: orange">⏳ Em Progresso</p>
            </div>
          </div>

          <div class="task-card" style="
                display: flex;
                border: 1px solid hsl(var(--border));
                border-radius: 0.5rem;
                overflow: hidden;
              ">
            <div style="width: 6px; background-color: orange"></div>
            <div class="task-card" style="padding: 1rem; flex: 1">
              <h3 style="margin-bottom: 0.5rem">Contratar fotógrafo</h3>
              <p style="
                    color: hsl(var(--muted-foreground));
                    font-size: 0.9rem;
                    margin-bottom: 0.5rem;
                  ">
                Responsável: João
              </p>
              <p style="font-size: 0.9rem; margin-bottom: 0.5rem">
                📅 Prazo: 15/09/2025
              </p>
              <p style="font-size: 0.9rem; color: red">❗ Pendente</p>
            </div>
          </div>

          <div class="task-card" style="
                display: flex;
                border: 1px solid hsl(var(--border));
                border-radius: 0.5rem;
                overflow: hidden;
              ">
            <div style="width: 6px; background-color: orange"></div>
            <div class="task-card" style="padding: 1rem; flex: 1">
              <h3 style="margin-bottom: 0.5rem">
                Escolher cardápio do buffet
              </h3>
              <p style="
                    color: hsl(var(--muted-foreground));
                    font-size: 0.9rem;
                    margin-bottom: 0.5rem;
                  ">
                Responsável: Ana
              </p>
              <p style="font-size: 0.9rem; margin-bottom: 0.5rem">
                📅 Prazo: 20/09/2025
              </p>
              <p style="font-size: 0.9rem; color: green">✔ Concluído</p>
            </div>
          </div>

          <div class="task-card" style="
                display: flex;
                border: 1px solid hsl(var(--border));
                border-radius: 0.5rem;
                overflow: hidden;
              ">
            <div style="width: 6px; background-color: green"></div>
            <div class="task-card" style="padding: 1rem; flex: 1">
              <h3 style="margin-bottom: 0.5rem">Definir playlist da festa</h3>
              <p style="
                    color: hsl(var(--muted-foreground));
                    font-size: 0.9rem;
                    margin-bottom: 0.5rem;
                  ">
                Responsável: Carlos
              </p>
              <p style="font-size: 0.9rem; margin-bottom: 0.5rem">
                📅 Prazo: 25/09/2025
              </p>
              <p style="font-size: 0.9rem; color: orange">⏳ Em Progresso</p>
            </div>
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
                viewBox="0 0 24 24"
              >
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                />
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
            <svg
              style="width: 1rem; height: 1rem"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
              />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>contato@weddingeasy.com</span>
          </div>
        </div>

        <div class="footer-links">
          <h3>Links Rápidos</h3>
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
    document.addEventListener('click', function(event) {
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

</body>

</html>