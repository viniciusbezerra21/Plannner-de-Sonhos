<?php
session_start();
require_once "../config/conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION["usuario_id"];

// Pega os dados do usuário
$sql = "SELECT nome, nome_conjuge, telefone, email FROM usuarios WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Foto padrão
$foto_perfil = $usuario["foto_perfil"] ?? "default.png";
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
    display: flex;               /* Deixa a foto e as infos lado a lado */
    align-items: center;         /* Centraliza verticalmente */
    gap: 2rem;                   /* Espaço entre foto e infos */
    padding: 3rem 2rem;
    border-radius: 1rem;
    grid-column: span 2;
    background: hsl(var(--card-background, 0, 0%, 100%));
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    width: 100%;
    margin: 0 auto;
}

.profile-photo {
    flex-shrink: 0;              /* Foto não encolhe */
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
    flex: 1;                     /* Ocupa o resto do espaço */
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

    <!-- Adicionando alertas de sucesso/erro -->
    
    <div class="features-detailed-grid">
      <!-- Card Perfil -->
      <div class="profile-card">
    <div class="profile-photo">
        <img src="fotos/<?php echo $foto_perfil; ?>" alt="Foto de perfil">
    </div>
    <div class="profile-info">
        <h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>
        <p>E-mail: <?php echo htmlspecialchars($usuario['email']); ?></p>
        <p>Telefone: <?php echo htmlspecialchars($usuario['telefone']); ?></p>
        <p>Cônjuge: <?php echo htmlspecialchars($usuario['nome_conjuge']); ?></p>
        <button class="btn-primary" onclick="openEditModal()">Editar Perfil</button>
    </div>
</div>

      <!-- Card Preferências -->
      <div class="feature-detailed-card">
        <div class="feature-detailed-header">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 6v6l4 2" />
            </svg>
          </div>
          <h2 class="feature-detailed-title">Preferências</h2>
        </div>
        <ul class="feature-benefits">
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M12 19V6m-7 6h14" />
            </svg>
            Notificações por email: Ativadas
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="9" />
              <path d="M3 12h.01M3 12h.01M3 18h.01" />
            </svg>
            Tema: Padrão
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10" />
              <path d="M2 12h.01M3 12h.01M3 18h.01" />
            </svg>
            Idioma: Português (BR)
          </li>
        </ul>
      </div>

      <!-- Card Atividades -->
      <div class="feature-detailed-card">
        <div class="feature-detailed-header">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
          </div>
          <h2 class="feature-detailed-title">Atividades Recentes</h2>
        </div>
        <ul class="feature-benefits">
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            </svg>
            Você adicionou um evento na agenda.
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 .9 2 2 2 .9 2 2 2" />
            </svg>
            Você atualizou os dados financeiros.
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M12 8v4l3 3" />
              <circle cx="12" cy="12" r="10" />
            </svg>
            Você entrou pela última vez ontem.
          </li>
        </ul>
      </div>
    </div>
  </main>

  <!-- Adicionando modal de edição de perfil -->
  <div id="editModal" style="animation: staticFadeIn 0.3s ease-out;" class="modal">
    <div style="animation: slideInFromBottom 0.3s ease-out forwards;" class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2 style="text-align: center; margin-bottom: 0.5rem;">Editar Perfil</h2>

      <div class="modal-profile-photo" style="text-align: center; margin-bottom: 0.5rem;">
      <img src="fotos/<?php echo $_SESSION['foto_perfil']; ?>" alt="Foto de perfil" class="modal-profile-photo">
        <div class="custom-file">
          <input type="file" id="modalFoto" name="foto" accept="image/*" onchange="previewModalFoto(event)">
          <label for="modalFoto">Trocar Foto de Perfil</label>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="editForm">
        <input type="hidden" name="action" value="editar_perfil">

        <div class="form-group">
          <label for="modalNome">Nome Completo</label>
          <input type="text" id="modalNome" name="nome" value="" required>
        </div>

        <div class="form-group">
          <label for="modalNomeConj">Nome do Cônjuge</label>
          <input type="text" id="modalNomeConj" name="nome_conj" value="" required>
        </div>

        <div class="form-group">
          <label for="modalEmail">E-mail</label>
          <input type="email" id="modalEmail" name="email" value="" required>
        </div>

        <div class="form-group">
          <label for="modalTelefone">Telefone</label>
          <input type="text" id="modalTelefone" name="telefone" value="" required>
        </div>

        <div class="form-group">
          <label for="modalSenha">Nova Senha (opcional)</label>
          <input type="password" id="modalSenha" name="senha" placeholder="Deixe em branco para manter a senha atual">
        </div>

        <button type="submit" style="width: 100%; text-align: center; display: flex; justify-content: center;" class="btn-primary">Salvar Alterações</button>
      </form>
    </div>
  </div>

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
        const placeholder = document.getElementById('modalFotoPlaceholder');

        if (placeholder) placeholder.style.display = 'none';

        if (foto) {
          foto.src = URL.createObjectURL(file);
          foto.style.display = 'block';
        } else {
          const img = document.createElement('img');
          img.id = 'modalFotoPreview';
          img.src = URL.createObjectURL(file);
          img.className = 'modal-profile-photo';
          img.style.width = "120px";
          img.style.height = "120px";
          img.style.borderRadius = "50%";
          img.style.objectFit = "cover";
          img.style.boxShadow = "0 6px 15px rgba(0,0,0,0.15)";
          img.style.display = "block";
          img.style.margin = "0 auto";
          document.querySelector('.modal-profile-photo').prepend(img);
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

    // Sincronizar arquivo selecionado com o formulário
    document.getElementById('editForm').addEventListener('submit', function(e) {
      const fileInput = document.getElementById('modalFoto');
      if (fileInput.files.length > 0) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'file';
        hiddenInput.name = 'foto';
        hiddenInput.files = fileInput.files;
        hiddenInput.style.display = 'none';
        this.appendChild(hiddenInput);
      }
    });
  </script>
  <script>
    function openEditModal() {
      document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }
    window.onclick = function(e) {
      if (e.target.id === 'editModal') closeEditModal();
    }

    function toggleVisibility(fieldId) {
      const input = document.getElementById(fieldId);
      if (input.type === 'password') input.type = 'text';
      else input.type = 'password';
    }
  </script>
</body>

</html>
