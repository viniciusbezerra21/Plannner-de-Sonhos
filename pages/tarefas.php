<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";

/* ------------------------
   🔐 LOGIN POR COOKIE
-------------------------*/
if (!isset($_SESSION['id_usuario']) && isset($_COOKIE[$cookieName])) {
  $usuarioId = (int) $_COOKIE[$cookieName]; // garante que é inteiro

  $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
  $stmt->execute([$usuarioId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['foto_perfil'] = $user['foto_perfil'] ?: "default.png";
  } else {
    // cookie inválido → limpa
    setcookie($cookieName, "", time() - 3600, "/");
  }
}

/* ------------------------
   🔑 VERIFICA LOGIN
-------------------------*/
if (!isset($_SESSION['id_usuario'])) {
  header("Location: ../user/login.php");
  exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];

/* ------------------------
   🚪 LOGOUT
-------------------------*/
if (isset($_POST['logout'])) {
  session_destroy();
  setcookie($cookieName, "", time() - 3600, "/"); // apaga cookie
  header("Location: ../user/login.php");
  exit;
}

/* ------------------------
   ➕ ADICIONAR TAREFA
-------------------------*/
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

/* ------------------------
   📋 LISTAR TAREFAS
-------------------------*/
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

    /* ------------------------
       🎨 CUSTOM SELECT
    -------------------------*/
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
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="../index.php" class="logo">
          <span class="logo-text">Planner de Sonhos</span>
        </a>

        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <div class="dropdown">
            <a href="funcionalidades.html" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
            <div class="dropdown-menu">
              <a href="calendario.html">Calendário</a>
              <a href="orcamento.html">Orçamento</a>
              <a href="gestao-contratos.html">Gestão de Contratos</a>
              <a href="tarefas.php">Lista de Tarefas</a>
            </div>
          </div>
          <a href="contato.html" class="nav-link">Contato</a>

          <?php if (isset($_SESSION["id_usuario"])): ?>
            <div class="dropdown">
              <img src="../user/fotos/<?php echo $_SESSION['foto_perfil']; ?>" alt="Foto de perfil" class="user-avatar" />
              <div class="dropdown-menu">
                <a href="../user/perfil.php">Meu Perfil</a>
                <form method="post" style="margin: 0">
                  <button type="submit" name="logout" style="all: unset; cursor: pointer">Sair</button>
                </form>
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

  <footer>
    <div class="footer-bottom">
      <p>&copy; 2024 Planner de Sonhos. Todos os direitos reservados.</p>
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
</body>

</html>