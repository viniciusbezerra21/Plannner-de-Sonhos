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
/* 🔑 VERIFICA LOGIN */
/* ------------------------ */
if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../user/login.php");
  exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];

if (isset($_POST['logout'])) {
  try {
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id_usuario = ?");
    $stmt->execute([$usuario_id]);
  } catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
  }

  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: ../index.php");
  exit;
}

/* ------------------------ */
/* ➕ ADICIONAR TAREFA */
/* ------------------------ */
if (isset($_POST['add_task'])) {
  $titulo = trim($_POST['titulo']);
  $responsavel = trim($_POST['responsavel']);
  $prazo = $_POST['prazo'];
  $status = $_POST['status'];

  if ($titulo !== "" && $prazo !== "") {
    $stmt = $pdo->prepare("INSERT INTO tarefas (titulo, responsavel, prazo, status, id_usuario) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $responsavel, $prazo, $status, $idUsuario]);
  }

  header("Location: tarefas.php");
  exit;
}

/* ------------------------ */
/* 📋 LISTAR TAREFAS */
/* ------------------------ */
$stmt = $pdo->prepare("SELECT * FROM tarefas WHERE id_usuario = ? ORDER BY prazo ASC");
$stmt->execute([$idUsuario]);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />

  <style>
    /* Faz o layout ocupar a tela inteira */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
    }

    /* O conteúdo principal cresce e empurra o footer */
    main {
      flex: 1;
    }

    /* Footer fixo no fim */
    footer {
      margin-top: auto;
      background: hsl(var(--card));
      text-align: center;
      padding: 1rem;
    }

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

    .create-task-modal {
      background: white;
      padding: 2rem;
      border-radius: 1rem;
      width: 100%;
      max-width: 500px;
    }

    /* ------------------------ */
    /* 🎨 CUSTOM SELECT */
    /* ------------------------ */
    .custom-select {
      position: relative;
      user-select: none;
      width: 100%;
      font-family: 'Poppins', sans-serif;
    }

    .custom-select .selected {
      padding: 0.75rem 1rem;
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      background: hsl(var(--card));
      cursor: pointer;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .custom-select .selected:hover,
    .custom-select .selected:focus {
      border-color: hsl(var(--primary));
      box-shadow: 0 0 0 3px hsl(var(--primary) / 0.2);
    }

    .custom-select .options {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      z-index: 10;
      margin-top: 0.25rem;
    }

    .custom-select .options li {
      padding: 0.75rem 1rem;
      cursor: pointer;
      transition: background 0.2s;
    }

    .custom-select .options li:hover {
      background: hsl(var(--accent));
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
                onclick="toggleProfileDropdown()">
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <div class="profile-dropdown-user">
                    <img
                      src="../user/fotos/<?php echo htmlspecialchars($user_data['foto_perfil'] ?? 'default.png'); ?>"
                      alt="Avatar"
                      class="profile-dropdown-avatar">
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
                    <button type="submit" name="logout" class="profile-dropdown-item logout" style="width: 100%; text-align: left; background: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem;">
                    <svg fill="hsl(var(--foreground))" width="800px" height="800px" viewBox="0 0 36 36" version="1.1"  preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <title>logout-line</title>
    <path d="M7,6H23v9.8h2V6a2,2,0,0,0-2-2H7A2,2,0,0,0,5,6V30a2,2,0,0,0,2,2H23a2,2,0,0,0,2-2H7Z" class="clr-i-outline clr-i-outline-path-1"></path><path d="M28.16,17.28a1,1,0,0,0-1.41,1.41L30.13,22H15.63a1,1,0,0,0-1,1,1,1,0,0,0,1,1h14.5l-3.38,3.46a1,1,0,1,0,1.41,1.41L34,23.07Z" class="clr-i-outline clr-i-outline-path-2"></path>
    <rect x="0" y="0" width="36" height="36" fill-opacity="0"/>
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
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
          <h1 class="page-title">Lista de <span class="gradient-text">Tarefas</span></h1>
          <button class="btn-primary" onclick="openModal()">+ Nova Tarefa</button>
        </div>

        <div class="tasks-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;">
          <?php if (empty($tarefas)): ?>
            <p>Você ainda não tem tarefas cadastradas.</p>
          <?php else: ?>
            <?php foreach ($tarefas as $t):
              $cor = "gray";
              $statusTxt = "";
              if ($t['status'] === "pendente") {
                $cor = "red";
                $statusTxt = "Pendente";
              }
              if ($t['status'] === "progresso") {
                $cor = "orange";
                $statusTxt = "Em Progresso";
              }
              if ($t['status'] === "concluido") {
                $cor = "green";
                $statusTxt = "Concluído";
              }
            ?>
              <div class="task-card" style="display:flex;border:1px solid hsl(var(--border));border-radius:0.5rem;overflow:hidden;">
                <div style="width:6px;background-color:<?php echo $cor; ?>"></div>
                <div style="padding:1rem;flex:1">
                  <h3><?php echo htmlspecialchars($t['titulo']); ?></h3>
                  <p>Responsável: <?php echo htmlspecialchars($t['responsavel']); ?></p>
                  <p>📅 Prazo: <?php echo date("d/m/Y", strtotime($t['prazo'])); ?></p>
                  <p style="color:<?php echo $cor; ?>"><?php echo $statusTxt; ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <!-- MODAL -->
  <div class="modal-overlay" id="modalTask">
    <div class="create-task-modal">
      <form method="post">
        <h1 class="text-primary">Adicionar Nova Tarefa</h1>
        <div class="form-group">
          <label for="titulo">Título</label>
          <input type="text" id="titulo" name="titulo" required>
        </div>
        <div class="form-group">
          <label for="responsavel">Responsável</label>
          <input type="text" id="responsavel" name="responsavel">
        </div>
        <div class="form-group">
          <label for="prazo">Prazo</label>
          <input type="date" id="prazo" name="prazo" required>
        </div>
        <div class="form-group">
          <label for="status">Status</label>
          <div class="custom-select">
            <div class="selected">Pendente</div>
            <ul class="options">
              <li data-value="pendente">Pendente</li>
              <li data-value="progresso">Em Progresso</li>
              <li data-value="concluido">Concluído</li>
            </ul>
            <input type="hidden" name="status" id="status" value="pendente">
          </div>
        </div>
        <div class="form-row" style="display:flex;gap:1rem; margin-top:11rem;">
          <button type="submit" name="add_task" class="btn-primary">Salvar</button>
          <button type="button" class="btn-outline" onclick="closeModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <a href="../index.php" class="logo">
            <div class="heart-icon">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">Planner de Sonhos</span>
          </a>
          <p class="footer-description">
            A plataforma mais completa para cerimonialistas organizarem casamentos perfeitos. Simplifique sua gestão e encante seus clientes.
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
          <h3>Links Rápidos</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li><a href="../legal-pages/privacity-politics.html">Política de Privacidade</a></li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>

  <script>
    function openModal() {
      document.getElementById("modalTask").classList.add("active");
    }

    function closeModal() {
      document.getElementById("modalTask").classList.remove("active");
    }
    document.addEventListener("click", function(event) {
      const modal = document.getElementById("modalTask");
      if (modal.classList.contains("active") && !event.target.closest(".create-task-modal") && !event.target.closest("button[onclick='openModal()']")) {
        closeModal();
      }
    });

    // Dropdown customizado
    document.querySelectorAll(".custom-select").forEach(select => {
      const selected = select.querySelector(".selected");
      const options = select.querySelector(".options");
      const hiddenInput = select.querySelector("input[type=hidden]");

      selected.addEventListener("click", () => {
        options.style.display = options.style.display === "block" ? "none" : "block";
      });

      options.querySelectorAll("li").forEach(option => {
        option.addEventListener("click", () => {
          selected.textContent = option.textContent;
          hiddenInput.value = option.dataset.value;
          options.style.display = "none";
        });
      });

      document.addEventListener("click", (e) => {
        if (!select.contains(e.target)) options.style.display = "none";
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