<?php
session_start();

// Exemplo de dados (depois você vai puxar do banco)
$user = [
  "nome" => "Kauê Feltrin",
  "email" => "kaue@email.com",
  "telefone" => "(11) 99999-9999",
  "cidade" => "São Paulo - SP",
  "foto" => "" // vazio = sem foto cadastrada
];
$iniciais = strtoupper(substr($user['nome'], 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Perfil - WeddingEasy</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .profile-card {
      grid-column: span 2;
      text-align: center;
      padding: 3rem 2rem;
    }
    .profile-photo {
      margin-bottom: 1rem;
      position: relative;
      display: inline-block;
    }
    .profile-photo img,
    .profile-placeholder {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      display: block;
      margin: 0 auto;
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
    }
    .profile-card .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    @media (max-width: 768px) {
      .profile-card {
        grid-column: span 1;
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
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">WeddingEasy</span>
        </a>
        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <a href="perfil.php" class="nav-link">Perfil</a>
          <a href="calendar.php" class="nav-link">Agenda</a>
          <a href="financeiro.php" class="nav-link">Financeiro</a>
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
        <a href="calendar.php" class="nav-link">Agenda</a>
        <a href="financeiro.php" class="nav-link">Financeiro</a>
      </div>
    </div>
  </header>

  <!-- Conteúdo -->
  <main class="page-content container" style="margin-top:5rem;">
    <div class="page-header">
      <h1 class="page-title">Meu Perfil</h1>
      <p class="page-description">Gerencie suas informações pessoais e preferências.</p>
    </div>

    <div class="features-detailed-grid">
      <!-- Card Perfil -->
      <div class="feature-detailed-card profile-card">
        <div class="profile-photo">
          <?php if (!empty($user['foto'])): ?>
            <img src="<?php echo $user['foto']; ?>" alt="Foto de perfil">
          <?php else: ?>
            <div class="profile-placeholder"><?php echo $iniciais; ?></div>
          <?php endif; ?>
        </div>
        <h2><?php echo $user['nome']; ?></h2>
        <p><?php echo $user['email']; ?></p>
        <p><?php echo $user['telefone']; ?></p>
        <p><?php echo $user['cidade']; ?></p>
        <a href="editar_perfil.php" class="btn-primary">Editar Perfil</a>
      </div>

      <!-- Card Preferências -->
      <div class="feature-detailed-card">
        <div class="feature-detailed-header">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 6v6l4 2"/>
            </svg>
          </div>
          <h2 class="feature-detailed-title">Preferências</h2>
        </div>
        <ul class="feature-benefits">
          <li><svg class="star-icon"><path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/></svg> Notificações por email: Ativadas</li>
          <li><svg class="star-icon"><path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/></svg> Tema: Padrão</li>
          <li><svg class="star-icon"><path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/></svg> Idioma: Português (BR)</li>
        </ul>
      </div>

      <!-- Card Atividades -->
      <div class="feature-detailed-card">
        <div class="feature-detailed-header">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </div>
          <h2 class="feature-detailed-title">Atividades Recentes</h2>
        </div>
        <ul class="feature-benefits">
          <li><svg class="star-icon"><path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/></svg> Você adicionou um evento na agenda.</li>
          <li><svg class="star-icon"><path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/></svg> Você atualizou os dados financeiros.</li>
          <li><svg class="star-icon"><path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z"/></svg> Você entrou pela última vez ontem.</li>
        </ul>
      </div>
    </div>
  </main>

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
          <span class="logo-text">WeddingEasy</span>
        </a>
        <p class="footer-description">Organize cada detalhe do seu casamento com praticidade e elegância.</p>
      </div>
      <div class="footer-links">
        <h3>Links</h3>
        <ul>
          <li><a href="../index.php">Início</a></li>
          <li><a href="perfil.php">Perfil</a></li>
          <li><a href="calendar.php">Agenda</a></li>
          <li><a href="financeiro.php">Financeiro</a></li>
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
      <p>&copy; <?php echo date("Y"); ?> WeddingEasy. Todos os direitos reservados.</p>
    </div>
  </footer>

  <script>
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");
      mobileMenu.classList.toggle("active");
      hamburgerBtn.classList.toggle("hamburger-active");
    }
  </script>
</body>
</html>
