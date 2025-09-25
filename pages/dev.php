<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";

// Verifica se está logado
if (!isset($_SESSION['id_usuario'])) {
  header("Location: ../user/login.php");
  exit;
}

// Busca cargo do usuário
$stmt = $pdo->prepare("SELECT nome, cargo FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['id_usuario']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir usuário ou não for dev → expulsa
if (!$usuario || $usuario['cargo'] !== 'dev') {
  header("Location: ../index.php");
  exit;
}

// Deixa nome disponível pra saudação no header
$_SESSION['nome'] = $usuario['nome'];

// === Logout ===
if (isset($_POST['logout'])) {
  setcookie($cookieName, "", time() - 3600, "/"); // apaga cookie
  session_unset();
  session_destroy();
  header("Location: ../index.php");
  exit;
}

// Contadores
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE cargo = 'cliente'")->fetchColumn();
$totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
$totalMensagens = $pdo->query("SELECT COUNT(*) FROM contatos")->fetchColumn();

// Listas
$usuarios = $pdo->query("SELECT id_usuario, nome, email FROM usuarios WHERE cargo = 'cliente' ORDER BY id_usuario DESC")->fetchAll();
$eventos = $pdo->query("
  SELECT e.id_evento, e.nome_evento, e.data_evento, e.local, u.nome AS usuario
  FROM eventos e
  JOIN usuarios u ON e.id_usuario = u.id_usuario
  ORDER BY e.id_evento DESC
")->fetchAll();
$mensagens = $pdo->query("SELECT id, nome, email, mensagem FROM contatos ORDER BY id DESC")->fetchAll();
if (isset($_POST['delete_task'])) {
  $idTarefa = (int) $_POST['delete_task'];
  $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id_tarefa = ?");
  $stmt->execute([$idTarefa]);
  header("Location: dev.php");
  exit;
}
$tarefas = $pdo->query("
  SELECT t.id_tarefa, t.titulo, t.responsavel, t.prazo, t.status, u.nome AS usuario
  FROM tarefas t
  JOIN usuarios u ON t.id_usuario = u.id_usuario
  ORDER BY t.prazo ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <title>WeddingEasy - Painel Desenvolvedor</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/devicon.png" type="image/x-icon" />
  <style>
    :root {
      --primary: 345 91% 58%;
      --primary-foreground: 345 100% 96%;
      --secondary: 345 60% 45%;
      --secondary-foreground: 345 100% 96%;
      --background: 0 0% 12%;
      --foreground: 345 30% 95%;
      --muted: 345 20% 25%;
      --muted-foreground: 345 20% 70%;
      --card: 345 20% 18%;
      --card-foreground: 345 30% 95%;
      --border: 345 20% 30%;
      --accent: 345 91% 58%;
      --accent-foreground: 345 100% 96%;
    }

    #pageContent.blurred {
      filter: blur(6px);
      transition: filter 0.3s;
    }

    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal {
      background: rgba(20, 20, 20, 0.95);
      border-radius: 1rem;
      padding: 2rem;
      width: 90%;
      max-width: 1000px;
      max-height: 90%;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
      border: 1px solid hsl(var(--secondary));
    }

    .modal h2 {
      color: hsl(var(--primary));
      margin-bottom: 1rem;
    }

    .close-modal {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: white;
      cursor: pointer;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    th,
    td {
      padding: 0.75rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      text-align: left;
    }

    th {
      color: hsl(var(--primary));
    }

    body {
      background: linear-gradient(135deg, rgb(194, 43, 81) 0%, rgb(0, 0, 0) 50%, rgb(100, 4, 20) 100%);
      color: white;
      font-family: "Roboto", sans-serif;
      line-height: 1.6;
      min-height: 100vh;
    }

    .dev-header {
      background: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1rem 0;
    }

    .dev-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }

    .dev-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin: 2rem 0;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 1rem;
      padding: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: hsl(var(--primary));
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.9rem;
    }

    .dev-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin: 2rem 0;
    }

    .action-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 1rem;
      padding: 2rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      text-align: center;
    }

    .action-icon {
      width: 48px;
      height: 48px;
      margin: 0 auto 1rem;
      color: hsl(var(--primary));
    }

    .btn-dev {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
      margin: 1rem;
    }

    .btn-dev:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .logo-text {
      font-family: "Poppins", sans-serif;
      font-weight: 600;
      font-size: 1.25rem;
      color: white;
    }
  </style>
</head>

<body>
  <div id="pageContent">
    <header class="dev-header">
      <div class="dev-container">
        <div class="header-content">
          <a href="../index.php" class="logo"><span class="logo-text">WeddingEasy - DEV</span></a>
          <nav style="display:flex; gap:1rem; align-items:center">
            <span style="color: rgba(255,255,255,0.8)">Bem-vindo, <?= htmlspecialchars($_SESSION['nome']); ?></span>
            <form method="post" style="margin:0;">
              <button type="submit" name="logout" class="btn-dev">Logout</button>
            </form>
          </nav>
        </div>
      </div>
    </header>

    <main class="dev-container">
      <div class="page-header" style="text-align:center; margin:2rem 0">
        <h1 style="font-size:3rem; color:hsl(var(--primary))">Painel Desenvolvedor</h1>
        <p style="font-size:1.2rem; color:rgba(255,255,255,0.8)">Gerencie o sistema WeddingEasy</p>
      </div>

      <div class="dev-stats">
        <div class="stat-card">
          <div class="stat-number"><?= $totalUsuarios ?></div>
          <div class="stat-label">Usuários Clientes</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $totalEventos ?></div>
          <div class="stat-label">Eventos Criados</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $totalMensagens ?></div>
          <div class="stat-label">Mensagens de Contato</div>
        </div>
      </div>

      <div class="dev-actions">
        <div class="action-card">
          <h3>Gerenciar Usuários</h3>
          <p>Visualizar, editar e gerenciar contas de usuários do sistema.</p><button class="btn-dev" onclick="openModal('userModal')">Acessar</button>
        </div>
        <div class="action-card">
          <h3>Eventos do Sistema</h3>
          <p>Monitorar e gerenciar todos os eventos criados pelos usuários.</p><button class="btn-dev" onclick="openModal('eventsModal')">Acessar</button>
        </div>
        <div class="action-card">
          <h3>Mensagens de Contato</h3>
          <p>Visualizar e responder mensagens enviadas pelos usuários.</p><button class="btn-dev" onclick="openModal('messagesModal')">Acessar</button>
        </div>
        <div class="action-card">
          <h3>Tarefas</h3>
          <p>Visualizar e gerenciar todas as tarefas criadas pelos clientes.</p>
          <button class="btn-dev" onclick="openModal('tasksModal')">Acessar</button>
        </div>
      </div>
    </main>
  </div>

  <!-- Modal Usuários -->
  <div class="modal-overlay" id="userModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('userModal')">&times;</button>
      <h2>Gerenciar Usuários</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= $u['id_usuario'] ?></td>
              <td><?= htmlspecialchars($u['nome']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><button class="btn-dev">Editar</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Eventos -->
  <div class="modal-overlay" id="eventsModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('eventsModal')">&times;</button>
      <h2>Eventos cadastrados</h2>
      <table border="1" cellpadding="8">
        <tr>
          <th>ID</th>
          <th>Evento</th>
          <th>Data</th>
          <th>Local</th>
          <th>Criado por</th>
        </tr>
        <?php foreach ($eventos as $e): ?>
          <tr>
            <td><?= $e['id_evento'] ?></td>
            <td><?= htmlspecialchars($e['nome_evento']) ?></td>
            <td><?= htmlspecialchars($e['data_evento']) ?></td>
            <td><?= htmlspecialchars($e['local']) ?></td>
            <td><?= htmlspecialchars($e['usuario']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <!-- Modal Mensagens -->
  <div class="modal-overlay" id="messagesModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('messagesModal')">&times;</button>
      <h2>Mensagens de Contato</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Mensagem</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($mensagens as $m): ?>
            <tr>
              <td><?= $m['id'] ?></td>
              <td><?= htmlspecialchars($m['nome']) ?></td>
              <td><?= htmlspecialchars($m['email']) ?></td>
              <td><?= htmlspecialchars($m['mensagem']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Modal Tarefas -->
  <div class="modal-overlay" id="tasksModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('tasksModal')">&times;</button>
      <h2>Tarefas dos Usuários</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Responsável</th>
            <th>Prazo</th>
            <th>Status</th>
            <th>Criado por</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tarefas as $t): ?>
            <tr>
              <td><?= $t['id_tarefa'] ?></td>
              <td><?= htmlspecialchars($t['titulo']) ?></td>
              <td><?= htmlspecialchars($t['responsavel']) ?></td>
              <td><?= htmlspecialchars($t['prazo']) ?></td>
              <td><?= htmlspecialchars($t['status']) ?></td>
              <td><?= htmlspecialchars($t['usuario']) ?></td>
              <td>
                <form method="post" style="display:inline">
                  <input type="hidden" name="delete_task" value="<?= $t['id_tarefa'] ?>">
                  <button type="submit" class="btn-dev" onclick="return confirm('Excluir tarefa?')">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function openModal(id) {
      document.getElementById(id).style.display = 'flex';
      document.getElementById("pageContent").classList.add("blurred");
    }

    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
      document.getElementById("pageContent").classList.remove("blurred");
    }
  </script>
</body>

</html>