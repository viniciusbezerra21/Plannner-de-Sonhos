<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";

if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../user/login.php");
  exit;
}

$stmt = $pdo->prepare("SELECT nome, cargo FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['cargo'] !== 'dev') {
  header("Location: ../index.php");
  exit;
}

$_SESSION['nome'] = $usuario['nome'];

if (isset($_POST['logout'])) {
  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: ../index.php");
  exit;
}

$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE cargo = 'cliente'")->fetchColumn();
$totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
$totalMensagens = $pdo->query("SELECT COUNT(*) FROM contatos")->fetchColumn();

$usuarios = $pdo->query("SELECT id_usuario, nome, email FROM usuarios WHERE cargo = 'cliente' ORDER BY id_usuario DESC")->fetchAll();
$eventos = $pdo->query("
  SELECT e.id_evento, e.nome_evento, e.data_evento, e.local, u.nome AS usuario
  FROM eventos e
  JOIN usuarios u ON e.id_usuario = u.id_usuario
  ORDER BY e.id_evento DESC
")->fetchAll();
$mensagens = $pdo->query("
  SELECT id, nome, email, mensagem, 
         DATE_FORMAT(data_envio, '%d/%m/%Y %H:%i') as data_formatada,
         COALESCE(status_resposta, 'pendente') as status_resposta
  FROM contatos 
  ORDER BY id DESC
")->fetchAll();

$orcamentos = $pdo->query("
  SELECT o.id_orcamento, o.item, o.fornecedor, o.quantidade, o.valor_unitario, o.avaliacao, u.nome AS usuario
  FROM orcamentos o
  JOIN usuarios u ON o.id_usuario = u.id_usuario
  ORDER BY o.id_orcamento DESC
  LIMIT 20
")->fetchAll();

if (isset($_POST['edit_user'])) {
  $userId = (int) $_POST['user_id'];
  $nome = trim($_POST['nome']);
  $email = trim($_POST['email']);

  if ($nome && $email) {
    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id_usuario = ?");
    $stmt->execute([$nome, $email, $userId]);
  }
  header("Location: dev.php");
  exit;
}

if (isset($_POST['delete_user'])) {
  $userId = (int) $_POST['user_id'];
  $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND cargo = 'cliente'");
  $stmt->execute([$userId]);
  header("Location: dev.php");
  exit;
}

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel do Desenvolvedor</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;600;700&family=Poppins:wght@600&display=swap"
    rel="stylesheet">
  <link rel="shortcut icon" href="../Style/assets/devicon.png" type="image/x-icon">
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

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
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

    /* Added header styles from first file */
    .header {
      background: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
      transition: transform 0.2s;
    }

    .logo:hover {
      transform: scale(1.05);
    }

    .heart-icon {
      width: 2rem;
      height: 2rem;
      background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--secondary)));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    .logo-text {
      font-family: "Poppins", sans-serif;
      font-weight: 600;
      font-size: 1.25rem;
      color: white;
    }
    /* </CHANGE> */

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
      grid-template-columns: repeat(3, 1fr);
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

    .action-card.messages-card {
      grid-column: span 2;
      grid-row: span 2;
      text-align: left;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      max-height: 600px;
    }

    .action-card.tasks-card {
      grid-row: span 2;
      text-align: left;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      max-height: 600px;
    }

    .messages-card .card-header,
    .tasks-card .card-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .messages-card .card-header h3,
    .tasks-card .card-header h3 {
      margin: 0;
      font-size: 1.25rem;
    }

    .messages-card .search-container,
    .tasks-card .search-container {
      padding: 0.5rem 0;
    }

    .messages-card .search-input,
    .tasks-card .search-input {
      width: 100%;
      padding: 0.75rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 0.5rem;
      color: white;
      font-size: 0.95rem;
    }

    .messages-card .search-input::placeholder,
    .tasks-card .search-input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    .messages-card .messages-list,
    .tasks-card .tasks-list {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .messages-card .message-item,
    .tasks-card .task-item {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 0.5rem;
      padding: 1rem;
      transition: all 0.2s;
    }

    .messages-card .message-item:hover,
    .tasks-card .task-item:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: hsl(var(--primary));
    }

    .messages-card .message-header,
    .tasks-card .task-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }

    .messages-card .message-sender,
    .tasks-card .task-title {
      font-weight: 600;
      color: hsl(var(--primary));
    }

    .messages-card .message-time {
      font-size: 0.85rem;
      color: rgba(255, 255, 255, 0.6);
    }

    .tasks-card .task-status {
      font-size: 0.8rem;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .tasks-card .task-status.status-pendente {
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
    }

    .tasks-card .task-status.status-em.andamento,
    .tasks-card .task-status.status-em {
      background: rgba(33, 150, 243, 0.2);
      color: #2196f3;
    }

    .tasks-card .task-status.status-concluída,
    .tasks-card .task-status.status-concluida {
      background: rgba(76, 175, 80, 0.2);
      color: #4caf50;
    }

    .messages-card .message-email {
      font-size: 0.9rem;
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 0.5rem;
    }

    .tasks-card .task-info {
      display: flex;
      gap: 1rem;
      font-size: 0.9rem;
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 0.5rem;
    }

    .tasks-card .task-responsible,
    .tasks-card .task-deadline {
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .messages-card .message-content {
      color: rgba(255, 255, 255, 0.9);
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .tasks-card .task-user {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.85rem;
      font-style: italic;
    }

    .messages-card .messages-list::-webkit-scrollbar,
    .tasks-card .tasks-list::-webkit-scrollbar {
      width: 8px;
    }

    .messages-card .messages-list::-webkit-scrollbar-track,
    .tasks-card .tasks-list::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 4px;
    }

    .messages-card .messages-list::-webkit-scrollbar-thumb,
    .tasks-card .tasks-list::-webkit-scrollbar-thumb {
      background: hsl(var(--primary));
      border-radius: 4px;
    }

    @media (max-width: 1024px) {
      .dev-actions {
        grid-template-columns: repeat(2, 1fr);
      }
      .action-card.messages-card {
        grid-column: span 2;
        grid-row: span 1;
      }
      .action-card.tasks-card {
        grid-row: span 1;
        max-height: 400px;
      }
    }

    @media (max-width: 768px) {
      .dev-actions {
        grid-template-columns: 1fr;
      }
      .action-card.messages-card,
      .action-card.tasks-card {
        grid-row: span 1;
        max-height: 400px;
      }
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

    .edit-form {
      display: none;
      background: rgba(255, 255, 255, 0.1);
      padding: 1rem;
      border-radius: 0.5rem;
      margin-top: 0.5rem;
    }

    .edit-form.active {
      display: block;
    }

    .edit-form input {
      width: 100%;
      padding: 0.5rem;
      margin: 0.25rem 0;
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 0.25rem;
      background: rgba(255, 255, 255, 0.1);
      color: white;
    }

    .edit-form input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .btn-small {
      padding: 0.25rem 0.5rem;
      font-size: 0.8rem;
      margin: 0.25rem;
    }


    .footer-dev {
      background: none;
      border-top: none;
      padding: 3rem 1rem 2rem;
    }

    .footer-content-dev {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }

    @media (min-width: 768px) {
      .footer-content-dev {
        grid-template-columns: 2fr 1fr 1fr;
      }
    }

    .footer-brand-dev {
      max-width: 24rem;
    }

    .footer-description-dev {
      color: hsl(var(--muted-foreground));
      margin: 1rem 0;
      line-height: 1.6;
    }

    .footer-contact-dev {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: hsl(var(--muted-foreground));
    }

    .footer-links-dev h3 {
      font-family: "Poppins", sans-serif;
      font-size: 1.125rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      margin-bottom: 1rem;
    }

    .footer-links-dev ul {
      list-style: none;
    }

    .footer-links-dev li {
      margin-bottom: 0.5rem;
    }

    .footer-links-dev a {
      color: hsl(var(--muted-foreground));
      text-decoration: none;
      transition: color 0.2s;
    }

    .footer-links-dev a:hover {
      color: hsl(var(--primary));
    }

    .footer-bottom-dev {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      align-items: center;
      padding-top: 2rem;
      color: hsl(var(--muted-foreground));
    }

    @media (min-width: 768px) {
      .footer-bottom-dev {
        flex-direction: row;
        justify-content: space-between;
      }
    }
    select {
      color: white !important;
      background: rgba(255, 255, 255, 0.1) !important;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }

    select option {
      background: rgb(30, 30, 30);
      color: white;
      padding: 0.5rem;
    }

    select:focus {
      outline: 2px solid hsl(var(--primary));
      outline-offset: 2px;
    }
    
  </style>
</head>

<body>
  <div id="pageContent">
    <!-- Added proper header structure from first file -->
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
            <span class="logo-text">Planner de Sonhos - Dev</span>
          </a>

          <nav style="display: flex; gap: 1rem; align-items: center;">
            <span style="color: rgba(255, 255, 255, 0.8);">
              Bem-vindo, <?= htmlspecialchars($_SESSION['nome']); ?>
            </span>

            <form method="post" style="margin: 0;">
              <button type="submit" name="logout" class="btn-dev">Logout</button>
            </form>
          </nav>
        </div>
      </div>
    </header>

    <main class="dev-container">
      <section class="dev-stats">
        <div class="stat-card">
          <div class="stat-number"><?php echo $totalUsuarios; ?></div>
          <div class="stat-label">Usuários Cadastrados</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $totalEventos; ?></div>
          <div class="stat-label">Eventos Criados</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $totalMensagens; ?></div>
          <div class="stat-label">Mensagens Recebidas</div>
        </div>
      </section>

      <div class="dev-actions">
        <div class="action-card">
          <h3>Gerenciar Usuários</h3>
          <p>Visualizar, editar e gerenciar contas de usuários do sistema.</p>
          <button class="btn-dev" onclick="openModal('userModal')">Acessar</button>
        </div>

        <div class="action-card">
          <h3>Eventos do Sistema</h3>
          <p>Monitorar e gerenciar todos os eventos criados pelos usuários.</p>
          <button class="btn-dev" onclick="openModal('eventsModal')">Acessar</button>
        </div>

        <div class="action-card">
          <h3>Orçamentos</h3>
          <p>Visualizar e monitorar itens de orçamento dos usuários.</p>
          <button class="btn-dev" onclick="openModal('budgetModal')">Acessar</button>
        </div>

        <div class="action-card messages-card">
          <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <h3>Responder Mensagens</h3>
          </div>

          <div class="search-container">
            <input type="text" class="search-input" id="searchMessages"
              placeholder="Pesquisar mensagens por nome, email ou conteúdo...">
          </div>

          <div class="messages-list" id="messagesList">
            <?php foreach ($mensagens as $msg): ?>
              <div class="message-item"
                data-search="<?php echo htmlspecialchars(strtolower($msg['nome'] . ' ' . $msg['email'] . ' ' . $msg['mensagem'])); ?>"
                data-id="<?php echo $msg['id']; ?>" data-nome="<?php echo htmlspecialchars($msg['nome']); ?>"
                data-email="<?php echo htmlspecialchars($msg['email']); ?>"
                data-mensagem="<?php echo htmlspecialchars($msg['mensagem']); ?>" onclick="openReplyModal(this)"
                style="cursor: pointer; <?php echo ($msg['status_resposta'] === 'respondida') ? 'opacity: 0.6; border-left: 3px solid #4caf50;' : ''; ?>">
                <div class="message-header">
                  <span class="message-sender"><?php echo htmlspecialchars($msg['nome']); ?></span>
                  <span class="message-time">
                    <?php echo $msg['data_formatada'] ?? 'Data não disponível'; ?>
                    <?php if ($msg['status_resposta'] === 'respondida'): ?>
                      <span style="margin-left: 0.5rem; color: #4caf50; font-weight: 600;">✓ Respondida</span>
                    <?php endif; ?>
                  </span>
                </div>
                <div class="message-email"><?php echo htmlspecialchars($msg['email']); ?></div>
                <div class="message-content"><?php echo htmlspecialchars($msg['mensagem']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="action-card tasks-card">
          <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 11l3 3L22 4"></path>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <h3>Tarefas</h3>
          </div>

          <div class="search-container">
            <input type="text" class="search-input" id="searchTasks"
              placeholder="Pesquisar tarefas por título, responsável ou status...">
          </div>

          <div class="tasks-list" id="tasksList">
            <?php foreach ($tarefas as $tarefa): ?>
              <div class="task-item"
                data-search="<?php echo htmlspecialchars(strtolower($tarefa['titulo'] . ' ' . $tarefa['responsavel'] . ' ' . $tarefa['status'])); ?>"
                data-id="<?php echo $tarefa['id_tarefa']; ?>"
                data-titulo="<?php echo htmlspecialchars($tarefa['titulo']); ?>"
                data-responsavel="<?php echo htmlspecialchars($tarefa['responsavel']); ?>"
                data-prazo="<?php echo date('d/m/Y', strtotime($tarefa['prazo'])); ?>"
                data-status="<?php echo htmlspecialchars($tarefa['status']); ?>"
                data-usuario="<?php echo htmlspecialchars($tarefa['usuario']); ?>" onclick="openCompleteTaskModal(this)"
                style="cursor: pointer;">
                <div class="task-header">
                  <span class="task-title"><?php echo htmlspecialchars($tarefa['titulo']); ?></span>
                  <span
                    class="task-status status-<?php echo strtolower($tarefa['status']); ?>"><?php echo htmlspecialchars($tarefa['status']); ?></span>
                </div>
                <div class="task-info">
                  <span class="task-responsible">👤 <?php echo htmlspecialchars($tarefa['responsavel']); ?></span>
                  <span class="task-deadline">📅 <?php echo date('d/m/Y', strtotime($tarefa['prazo'])); ?></span>
                </div>
                <div class="task-user">Cliente: <?php echo htmlspecialchars($tarefa['usuario']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Modals -->
  <div class="modal-overlay" id="userModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('userModal')">×</button>
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
          <?php foreach ($usuarios as $user): ?>
            <tr>
              <td><?php echo $user['id_usuario']; ?></td>
              <td><?php echo htmlspecialchars($user['nome']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td>
                <button class="btn-dev btn-small" onclick="toggleEdit(<?php echo $user['id_usuario']; ?>)">Editar</button>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="user_id" value="<?php echo $user['id_usuario']; ?>">
                  <button type="submit" name="delete_user" class="btn-dev btn-small"
                    onclick="return confirm('Tem certeza?')">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-overlay" id="eventsModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('eventsModal')">×</button>
      <h2>Eventos do Sistema</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Evento</th>
            <th>Data</th>
            <th>Local</th>
            <th>Usuário</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($eventos as $evento): ?>
            <tr>
              <td><?php echo $evento['id_evento']; ?></td>
              <td><?php echo htmlspecialchars($evento['nome_evento']); ?></td>
              <td><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></td>
              <td><?php echo htmlspecialchars($evento['local']); ?></td>
              <td><?php echo htmlspecialchars($evento['usuario']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-overlay" id="messagesModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('messagesModal')">×</button>
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
          <?php foreach ($mensagens as $msg): ?>
            <tr>
              <td><?php echo $msg['id']; ?></td>
              <td><?php echo htmlspecialchars($msg['nome']); ?></td>
              <td><?php echo htmlspecialchars($msg['email']); ?></td>
              <td><?php echo htmlspecialchars($msg['mensagem']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-overlay" id="tasksModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('tasksModal')">×</button>
      <h2>Tarefas</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Responsável</th>
            <th>Prazo</th>
            <th>Status</th>
            <th>Usuário</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tarefas as $tarefa): ?>
            <tr>
              <td><?php echo $tarefa['id_tarefa']; ?></td>
              <td><?php echo htmlspecialchars($tarefa['titulo']); ?></td>
              <td><?php echo htmlspecialchars($tarefa['responsavel']); ?></td>
              <td><?php echo date('d/m/Y', strtotime($tarefa['prazo'])); ?></td>
              <td><?php echo htmlspecialchars($tarefa['status']); ?></td>
              <td><?php echo htmlspecialchars($tarefa['usuario']); ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="delete_task" value="<?php echo $tarefa['id_tarefa']; ?>">
                  <button type="submit" class="btn-dev btn-small"
                    onclick="return confirm('Tem certeza?')">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-overlay" id="budgetModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('budgetModal')">×</button>
      <h2>Orçamentos</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Fornecedor</th>
            <th>Quantidade</th>
            <th>Valor Unitário</th>
            <th>Avaliação</th>
            <th>Usuário</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orcamentos as $orc): ?>
            <tr>
              <td><?php echo $orc['id_orcamento']; ?></td>
              <td><?php echo htmlspecialchars($orc['item']); ?></td>
              <td><?php echo htmlspecialchars($orc['fornecedor']); ?></td>
              <td><?php echo $orc['quantidade']; ?></td>
              <td>R$ <?php echo number_format($orc['valor_unitario'], 2, ',', '.'); ?></td>
              <td><?php echo htmlspecialchars($orc['avaliacao']); ?></td>
              <td><?php echo htmlspecialchars($orc['usuario']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Added Reply Message Modal -->
  <div class="modal-overlay" id="replyMessageModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('replyMessageModal')">×</button>
      <h2>Responder Mensagem</h2>

      <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <p><strong>De:</strong> <span id="replyNome"></span></p>
        <p><strong>Email:</strong> <span id="replyEmail"></span></p>
        <p style="margin-top: 1rem;"><strong>Mensagem original:</strong></p>
        <p style="color: rgba(255, 255, 255, 0.8); font-style: italic;" id="replyMensagemOriginal"></p>
      </div>

      <form method="POST" action="../api/responder_mensagem.php" id="replyForm">
        <input type="hidden" name="mensagem_id" id="replyMensagemId">
        <input type="hidden" name="destinatario_email" id="replyDestinatarioEmail">
        <input type="hidden" name="destinatario_nome" id="replyDestinatarioNome">

        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: hsl(var(--primary));">Assunto:</label>
          <input type="text" name="assunto" required
            style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: white; font-size: 1rem;"
            placeholder="Re: Sua mensagem">
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: hsl(var(--primary));">Sua resposta:</label>
          <textarea name="resposta" required rows="8"
            style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: white; font-size: 1rem; resize: vertical; font-family: inherit;"
            placeholder="Digite sua resposta aqui..."></textarea>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button type="button" class="btn-dev" onclick="closeModal('replyMessageModal')"
            style="background: rgba(255, 255, 255, 0.1);">Cancelar</button>
          <button type="submit" class="btn-dev">Enviar Resposta</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Added Complete Task Modal -->
  <div class="modal-overlay" id="completeTaskModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('completeTaskModal')">×</button>
      <h2>Gerenciar Tarefa</h2>

      <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <p><strong>Título:</strong> <span id="taskTitulo"></span></p>
        <p><strong>Responsável:</strong> <span id="taskResponsavel"></span></p>
        <p><strong>Prazo:</strong> <span id="taskPrazo"></span></p>
        <p><strong>Status Atual:</strong> <span id="taskStatusAtual"
            style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-weight: 600; font-size: 0.9rem;"></span></p>
        <p><strong>Cliente:</strong> <span id="taskUsuario"></span></p>
      </div>

      <form method="POST" action="../api/concluir_tarefa.php" id="completeTaskForm">
        <input type="hidden" name="tarefa_id" id="taskId">

        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: hsl(var(--primary));">Alterar Status:</label>
          <select name="novo_status" required
            style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: white; font-size: 1rem;">
            <option value="Pendente">Pendente</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="Concluída">Concluída</option>
          </select>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: hsl(var(--primary));">Observações
            (opcional):</label>
          <textarea name="observacoes" rows="4"
            style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: white; font-size: 1rem; resize: vertical; font-family: inherit;"
            placeholder="Adicione observações sobre a tarefa..."></textarea>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button type="button" class="btn-dev" onclick="closeModal('completeTaskModal')"
            style="background: rgba(255, 255, 255, 0.1);">Cancelar</button>
          <button type="submit" class="btn-dev">Atualizar Tarefa</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="completeTaskModal">
    <div class="modal">
      <button class="close-modal" onclick="closeModal('completeTaskModal')">×</button>
      <h2>Gerenciar Tarefa</h2>
      
      <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <p><strong>Título:</strong> <span id="taskTitulo"></span></p>
        <p><strong>Responsável:</strong> <span id="taskResponsavel"></span></p>
        <p><strong>Prazo:</strong> <span id="taskPrazo"></span></p>
        <p><strong>Status Atual:</strong> <span id="taskStatusAtual" style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-weight: 600; font-size: 0.9rem;"></span></p>
        <p><strong>Cliente:</strong> <span id="taskUsuario"></span></p>
      </div>

      <form method="POST" action="../api/concluir_tarefa.php" id="completeTaskForm">
        <input type="hidden" name="tarefa_id" id="taskId">
        
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: hsl(var(--primary));">Alterar Status:</label>
          <select 
            name="novo_status" 
            required
            style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: white; font-size: 1rem;"
          >
            <option value="Pendente">Pendente</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="Concluída">Concluída</option>
          </select>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; color: hsl(var(--primary));">Observações (opcional):</label>
          <textarea 
            name="observacoes"
            rows="4"
            style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: white; font-size: 1rem; resize: vertical; font-family: inherit;"
            placeholder="Adicione observações sobre a tarefa..."
          ></textarea>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button type="button" class="btn-dev" onclick="closeModal('completeTaskModal')" style="background: rgba(255, 255, 255, 0.1);">Cancelar</button>
          <button type="submit" class="btn-dev">Atualizar Tarefa</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="footer-dev" style="background: none; border-top: none;">
    <div class="container">
      <div class="footer-content-dev">
        <div class="footer-brand-dev">
          <a href="../index.php" class="logo">
            <div class="heart-icon">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">Planner de Sonhos - Dev</span>
          </a>
          <p class="footer-description-dev">
            A plataforma mais completa para cerimonialistas organizarem casamentos perfeitos. Simplifique sua gestão e encante seus clientes.
          </p>
          <div class="footer-contact-dev">
            <svg style="width: 1rem; height: 1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>contato@plannerdesonhos.com</span>
          </div>
        </div>
        <div class="footer-links-dev">
          <h3>Links Rápidos</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li><a href="../legal-pages/privacity-politics.html">Política de Privacidade</a></li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom-dev">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
      </div>
    </div>
</footer>


  
  <script>
    function openModal(modalId) {
      document.getElementById('pageContent').classList.add('blurred');
      document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
      document.getElementById('pageContent').classList.remove('blurred');
      document.getElementById(modalId).style.display = 'none';
    }

    function toggleEdit(userId) {
      const form = document.getElementById('editForm' + userId);
      if (form) {
        form.classList.toggle('active');
      }
    }

    document.getElementById('searchMessages').addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const messages = document.querySelectorAll('.message-item');
      
      messages.forEach(message => {
        const searchData = message.getAttribute('data-search');
        if (searchData.includes(searchTerm)) {
          message.style.display = 'block';
        } else {
          message.style.display = 'none';
        }
      });
    });

    document.getElementById('searchTasks').addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const tasks = document.querySelectorAll('.task-item');
      
      tasks.forEach(task => {
        const searchData = task.getAttribute('data-search');
        if (searchData.includes(searchTerm)) {
          task.style.display = 'block';
        } else {
          task.style.display = 'none';
        }
      });
    });

    window.onclick = function(event) {
      if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
        document.getElementById('pageContent').classList.remove('blurred');
      }
    }

    function openReplyModal(element) {
      event.stopPropagation();
      const id = element.getAttribute('data-id');
      const nome = element.getAttribute('data-nome');
      const email = element.getAttribute('data-email');
      const mensagem = element.getAttribute('data-mensagem');

      document.getElementById('replyMensagemId').value = id;
      document.getElementById('replyDestinatarioEmail').value = email;
      document.getElementById('replyDestinatarioNome').value = nome;
      document.getElementById('replyNome').textContent = nome;
      document.getElementById('replyEmail').textContent = email;
      document.getElementById('replyMensagemOriginal').textContent = mensagem;

      openModal('replyMessageModal');
    }

    function openCompleteTaskModal(element) {
      event.stopPropagation();
      const id = element.getAttribute('data-id');
      const titulo = element.getAttribute('data-titulo');
      const responsavel = element.getAttribute('data-responsavel');
      const prazo = element.getAttribute('data-prazo');
      const status = element.getAttribute('data-status');
      const usuario = element.getAttribute('data-usuario');

      document.getElementById('taskId').value = id;
      document.getElementById('taskTitulo').textContent = titulo;
      document.getElementById('taskResponsavel').textContent = responsavel;
      document.getElementById('taskPrazo').textContent = prazo;
      document.getElementById('taskStatusAtual').textContent = status;
      document.getElementById('taskUsuario').textContent = usuario;

      // Set status badge color
      const statusBadge = document.getElementById('taskStatusAtual');
      statusBadge.className = '';
      if (status.toLowerCase() === 'pendente') {
        statusBadge.style.background = 'rgba(255, 193, 7, 0.2)';
        statusBadge.style.color = '#ffc107';
      } else if (status.toLowerCase().includes('andamento')) {
        statusBadge.style.background = 'rgba(33, 150, 243, 0.2)';
        statusBadge.style.color = '#2196f3';
      } else if (status.toLowerCase().includes('conclu')) {
        statusBadge.style.background = 'rgba(76, 175, 80, 0.2)';
        statusBadge.style.color = '#4caf50';
      }

      // Pre-select current status in dropdown
      const selectStatus = document.querySelector('select[name="novo_status"]');
      selectStatus.value = status;

      openModal('completeTaskModal');
    }

    function showPopup(type, title, message) {
      const popup = document.getElementById('popupNotification');
      const overlay = document.getElementById('popupOverlay');
      const icon = document.getElementById('popupIcon');
      const titleEl = document.getElementById('popupTitle');
      const messageEl = document.getElementById('popupMessage');

      // Reset classes
      popup.className = 'popup-notification';
      
      // Set type (success or error)
      popup.classList.add(type);
      
      // Set content
      if (type === 'success') {
        icon.textContent = '✓';
      } else {
        icon.textContent = '✕';
      }
      
      titleEl.textContent = title;
      messageEl.textContent = message;

      // Show popup
      overlay.classList.add('show');
      setTimeout(() => {
        popup.classList.add('show');
      }, 10);
    }

    function closePopup() {
      const popup = document.getElementById('popupNotification');
      const overlay = document.getElementById('popupOverlay');
      
      popup.classList.remove('show');
      setTimeout(() => {
        overlay.classList.remove('show');
        // Reload page to show updated data
        location.reload();
      }, 300);
    }

    document.getElementById('replyForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const destinatarioNome = formData.get('destinatario_nome');
      
      fetch('../api/responder_mensagem.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        closeModal('replyMessageModal');
        
        if (data.success) {
          showPopup('success', 'Mensagem Enviada!', `Sua resposta foi enviada com sucesso para ${destinatarioNome}.`);
        } else {
          showPopup('error', 'Erro ao Enviar', data.message || 'Ocorreu um erro ao enviar a resposta.');
        }
      })
      .catch(error => {
        closeModal('replyMessageModal');
        showPopup('error', 'Erro de Conexão', 'Não foi possível conectar ao servidor.');
        console.error('Error:', error);
      });
    });

    document.getElementById('completeTaskForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const novoStatus = formData.get('novo_status');
      
      fetch('../api/concluir_tarefa.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        closeModal('completeTaskModal');
        
        if (data.success) {
          showPopup('success', 'Tarefa Atualizada!', `O status da tarefa foi alterado para "${novoStatus}" com sucesso.`);
        } else {
          showPopup('error', 'Erro ao Atualizar', data.message || 'Ocorreu um erro ao atualizar a tarefa.');
        }
      })
      .catch(error => {
        closeModal('completeTaskModal');
        showPopup('error', 'Erro de Conexão', 'Não foi possível conectar ao servidor.');
        console.error('Error:', error);
      });
    });
  </script>

  <script>
    function openModal(modalId) {
      document.getElementById('pageContent').classList.add('blurred');
      document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
      document.getElementById('pageContent').classList.remove('blurred');
      document.getElementById(modalId).style.display = 'none';
    }

    function toggleEdit(userId) {
      const form = document.getElementById('editForm' + userId);
      if (form) {
        form.classList.toggle('active');
      }
    }

    document.getElementById('searchMessages').addEventListener('input', function (e) {
      const searchTerm = e.target.value.toLowerCase();
      const messages = document.querySelectorAll('.message-item');

      messages.forEach(message => {
        const searchData = message.getAttribute('data-search');
        if (searchData.includes(searchTerm)) {
          message.style.display = 'block';
        } else {
          message.style.display = 'none';
        }
      });
    });

    document.getElementById('searchTasks').addEventListener('input', function (e) {
      const searchTerm = e.target.value.toLowerCase();
      const tasks = document.querySelectorAll('.task-item');

      tasks.forEach(task => {
        const searchData = task.getAttribute('data-search');
        if (searchData.includes(searchTerm)) {
          task.style.display = 'block';
        } else {
          task.style.display = 'none';
        }
      });
    });

    window.onclick = function (event) {
      if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
        document.getElementById('pageContent').classList.remove('blurred');
      }
    }

    function openReplyModal(element) {
      event.stopPropagation();
      const id = element.getAttribute('data-id');
      const nome = element.getAttribute('data-nome');
      const email = element.getAttribute('data-email');
      const mensagem = element.getAttribute('data-mensagem');

      document.getElementById('replyMensagemId').value = id;
      document.getElementById('replyDestinatarioEmail').value = email;
      document.getElementById('replyDestinatarioNome').value = nome;
      document.getElementById('replyNome').textContent = nome;
      document.getElementById('replyEmail').textContent = email;
      document.getElementById('replyMensagemOriginal').textContent = mensagem;

      openModal('replyMessageModal');
    }

    function openCompleteTaskModal(element) {
      event.stopPropagation();
      const id = element.getAttribute('data-id');
      const titulo = element.getAttribute('data-titulo');
      const responsavel = element.getAttribute('data-responsavel');
      const prazo = element.getAttribute('data-prazo');
      const status = element.getAttribute('data-status');
      const usuario = element.getAttribute('data-usuario');

      document.getElementById('taskId').value = id;
      document.getElementById('taskTitulo').textContent = titulo;
      document.getElementById('taskResponsavel').textContent = responsavel;
      document.getElementById('taskPrazo').textContent = prazo;
      document.getElementById('taskStatusAtual').textContent = status;
      document.getElementById('taskUsuario').textContent = usuario;

      // Set status badge color
      const statusBadge = document.getElementById('taskStatusAtual');
      statusBadge.className = '';
      if (status.toLowerCase() === 'pendente') {
        statusBadge.style.background = 'rgba(255, 193, 7, 0.2)';
        statusBadge.style.color = '#ffc107';
      } else if (status.toLowerCase().includes('andamento')) {
        statusBadge.style.background = 'rgba(33, 150, 243, 0.2)';
        statusBadge.style.color = '#2196f3';
      } else if (status.toLowerCase().includes('conclu')) {
        statusBadge.style.background = 'rgba(76, 175, 80, 0.2)';
        statusBadge.style.color = '#4caf50';
      }

      // Pre-select current status in dropdown
      const selectStatus = document.querySelector('select[name="novo_status"]');
      selectStatus.value = status;

      openModal('completeTaskModal');
    }
  </script>
</body>

</html>