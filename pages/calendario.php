<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";


if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
  $usuarioId = (int) $_COOKIE[$cookieName];

  $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
  $stmt->execute([$usuarioId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $_SESSION['usuario_id'] = $user['id_usuario'];
    $_SESSION['foto_perfil'] = $user['foto_perfil'] ?: "default.png";
  } else {
    setcookie($cookieName, "", time() - 3600, "/");
  }
}


$user_data = ['nome' => 'Usuário', 'email' => '', 'foto_perfil' => 'default.png'];


if (isset($_SESSION['usuario_id'])) {
  try {
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([(int) $_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      $user_data = [
        'nome' => $result['nome'] ?? 'Usuário',
        'email' => $result['email'] ?? '',
        'foto_perfil' => !empty($result['foto_perfil']) ? $result['foto_perfil'] : 'default.png'
      ];

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


if (empty($_SESSION['foto_perfil'])) {
  $_SESSION['foto_perfil'] = 'default.png';
}


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


try {
  $stmt = $pdo->prepare("SELECT 
    id_evento,
    id_usuario,
    nome_evento,
    descricao,
    data_evento,
    horario,
    local,
    status,
    COALESCE(prioridade, 'media') as prioridade,
    COALESCE(cor_tag, 'azul') as cor_tag,
    data_criacao
    FROM eventos 
    WHERE id_usuario = ? 
    ORDER BY data_evento ASC, horario ASC");
  $stmt->execute([$idUsuario]);
  $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id_usuario = ? ORDER BY data_evento ASC, horario ASC");
  $stmt->execute([$idUsuario]);
  $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($eventos as &$evento) {
    if (!isset($evento['prioridade'])) {
      $evento['prioridade'] = 'media';
    }
    if (!isset($evento['cor_tag'])) {
      $evento['cor_tag'] = 'azul';
    }
  }
}


$eventosJson = json_encode($eventos);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos - Calendário</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
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

    .create-event-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.199);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .create-event-modal .content {
      background: white;
      width: 400px;
      height: 400px;
      border-radius: 10px;
      padding: 20px;
    }

    .calendar-day-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.199);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .content-day {
      background: white;
      width: 400px;
      height: 400px;
      border-radius: 10px;
      padding: 20px;
    }

    /* Adicionando estilos para o modal de visualização de eventos */
    .view-events-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .view-events-modal.active {
      display: flex;
    }
    /* </CHANGE> */

    .card-modal {
      background-color: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1.5rem;
      padding: 1.5rem;
      transition: all 0.3s;
      animation: fadeIn 0.5s ease-out;
    }

    .card-view {
      width: 70%;
      max-width: 800px;
      padding: 2rem;
    }

    .card-view h1 {
      margin-bottom: 1.5rem;
    }

    .event-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-bottom: 2rem;
      max-height: 400px;
      overflow-y: auto;
      padding-right: 0.5rem;
    }

    .event-list .event-item {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1rem 1.25rem;
      transition: all 0.2s;
    }

    .evento-concluido{
      text-decoration: line-through;
      opacity: 0.6;
    }

    .evento-concluido h1{
      text-decoration: line-through;
      opacity: 0.6;
    }

    .evento-concluido span{
      text-decoration: line-through;
      opacity: 0.6;
    }

    .event-list .event-item:hover {
      background: hsl(var(--muted));
      transform: translateX(4px);
    }

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


    .search-container {
      position: relative;
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }

    .search-input {
      width: 0;
      opacity: 0;
      padding: 0;
      border: none;
      transition: all 0.3s ease;
      overflow: hidden;
    }

    .search-input.expanded {
      width: 200px;
      opacity: 1;
      padding: 0.5rem 1rem;
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      margin-right: 0.5rem;
    }

    .action-btn {
      background: none;
      border: none;
      padding: 0.5rem;
      cursor: pointer;
      border-radius: 0.5rem;
      transition: background-color 0.2s;
    }

    .action-btn:hover {
      background: hsl(var(--accent));
    }


    .filter-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .filter-modal.active {
      display: flex;
    }

    .filter-content {
      background: white;
      border-radius: 1rem;
      padding: 2rem;
      max-width: 500px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }

    .filter-group {
      margin-bottom: 1.5rem;
    }

    .filter-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: hsl(var(--foreground));
    }

    .filter-options {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .filter-chip {
      padding: 0.5rem 1rem;
      border: 1px solid hsl(var(--border));
      border-radius: 2rem;
      cursor: pointer;
      transition: all 0.2s;
      background: white;
    }

    .filter-chip:hover {
      background: hsl(var(--accent));
    }

    .filter-chip.active {
      background: hsl(var(--primary));
      color: white;
      border-color: hsl(var(--primary));
    }

    .color-chip {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.2s;
    }

    .color-chip.active {
      border-color: #000;
      transform: scale(1.2);
    }

    .color-chip.azul {
      background-color: #3b82f6;
    }

    .color-chip.vermelho {
      background-color: #ef4444;
    }

    .color-chip.verde {
      background-color: #10b981;
    }

    .color-chip.amarelo {
      background-color: #f59e0b;
    }

    .color-chip.rosa {
      background-color: #ec4899;
    }

    @media (max-width: 768px) {
      .user-profile {
        order: -1;
      }

      .profile-dropdown {
        right: -1rem;
      }
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

  <div class="filter-modal" id="filter-modal">
    <div class="filter-content card-modal">
      <h2 class="text-primary">Filtrar Eventos</h2>

      <div class="filter-group">
        <label>Prioridade</label>
        <div class="filter-options">
          <div class="filter-chip" data-filter="prioridade" data-value="alta">Alta</div>
          <div class="filter-chip" data-filter="prioridade" data-value="media">Média</div>
          <div class="filter-chip" data-filter="prioridade" data-value="baixa">Baixa</div>
        </div>
      </div>

      <div class="filter-group">
        <label>Cor da Tag</label>
        <div class="filter-options">
          <div class="color-chip azul" data-filter="cor" data-value="azul" title="Azul"></div>
          <div class="color-chip vermelho" data-filter="cor" data-value="vermelho" title="Vermelho"></div>
          <div class="color-chip verde" data-filter="cor" data-value="verde" title="Verde"></div>
          <div class="color-chip amarelo" data-filter="cor" data-value="amarelo" title="Amarelo"></div>
          <div class="color-chip rosa" data-filter="cor" data-value="rosa" title="Rosa"></div>
        </div>
      </div>

      <div class="filter-group">
        <label>Status</label>
        <div class="filter-options">
          <div class="filter-chip" data-filter="status" data-value="pendente">Pendente</div>
          <div class="filter-chip" data-filter="status" data-value="concluido">Concluído</div>
        </div>
      </div>

      <div class="form-row" style="margin-top: 2rem;">
        <button class="btn-primary" id="apply-filters">Aplicar Filtros</button>
        <button class="btn-outline" id="clear-filters">Limpar Filtros</button>
        <button class="btn-outline" id="close-filter-modal">Fechar</button>
      </div>
    </div>
  </div>

  <div class="create-event-modal" id="janela-modal-prioridade">
    <form class="card-modal contact-form">
      <h1>Definir Prioridade</h1>
      <div class="form-group">
        <label for="prioridadeInput">Prioridade do Evento</label>
        <select id="prioridadeInput" placeholder="Selecione a prioridade">
          <option value="alta" id="alta">Alta</option>
          <option value="media" id="media">Média</option>
          <option value="baixa" id="baixa">Baixa</option>
        </select>
      </div>


      <div class="form-group">
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
          <input type="checkbox" id="statusConcluidoInput" style="width: 18px; height: 18px; cursor: pointer;">
          <span>Marcar como concluído</span>
        </label>
      </div>

      <div class="form-row">
        <button type="button" class="btn-primary" id="btnSalvarPrioridade">Salvar</button>
      </div>
    </form>
  </div>
  <div class="create-event-modal" id="janela-modal">
    <form class="card-modal contact-form">
      <h1 class="text-primary">Adicionar Novo Evento</h1>
      <div class="form-group">
        <label for="organizarPorCor">Cor da Tag</label>
        <select id="organizarPorCor" placeholder="Selecione uma cor">
          <option value="azul" id="azul">Azul</option>
          <option value="vermelho" id="vermelho">Vermelho</option>
          <option value="verde" id="verde">Verde</option>
          <option value="amarelo" id="amarelo">Amarelo</option>
          <option value="rosa" id="rosa">Rosa</option>
        </select>
        <div class="form-group">
          <label for="descricao">Adicionar Tag</label>
          <input type="text" id="descricao" name="descricao">
        </div>
      </div>
      <div class="form-group">
        <label for="nome">Nome do evento</label>
        <input type="text" id="nome" name="nome">
      </div>
      <div class="form-group">
        <label for="data">Selecione a data</label>
        <input type="date" id="data" name="data">
      </div>
      <div class="form-group">
        <label for="hora">Selecione a hora</label>
        <input type="time" id="hora" name="hora">
      </div>
      <div class="form-group">
        <label for="local">Local</label>
        <input type="text" id="local" name="local" placeholder="Local do evento">
      </div>
      <div class="form-row">
        <button type="button" id="btnSalvarPrincipal" class="btn-primary">Criar novo evento</button>
        <button type="button" id="btnCancelarPrincipal" class="btn-outline">Cancelar</button>
      </div>
    </form>
  </div>
  <div class="calendar-day-modal" id="janela-modal-day">
    <form class="card-modal contact-form">
      <h1 class="text-primary">Criar Novo Evento</h1>
      <div class="form-group">
        <label for="nome">Nome do evento</label>
        <input type="text" id="nome" name="nome">
      </div>
      <div class="form-group">
        <label for="hora">Selecione a hora</label>
        <input type="time" name="hora" id="hora">
      </div>
      <div class="form-group">
        <label for="local">Local</label>
        <input type="text" name="local" id="local" placeholder="Local do evento">
      </div>
      <div class="form-group">
        <label for="descricao">Descrição do evento</label>
        <input type="text" id="descricao" name="descricao">
      </div>
      <div class="form-row">
        <button type="button" id="btnSalvarCalendario" class="btn-primary">Criar novo evento</button>
        <button type="button" id="btnCancelarCalendario" class="btn-outline">Cancelar</button>
      </div>
    </form>
  </div>
  <div class="view-events-modal" id="janela-modal-view">
    <div class="card card-view">
      <h1 class="text-primary">Eventos do Dia</h1>

      <div id="listaEventos" class="event-list">
        <ul id="eventList"></ul>
      </div>

      <div class="form-row">
        <button id="btnFecharView" class="btn-outline">Fechar</button>
      </div>
    </div>
  </div>
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
          <span class="logo-text">Planner de Sonhos</span>
        </a>

        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <div class="dropdown">
            <a href="funcionalidades.php" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
            <div class="dropdown-menu">
              <a href="calendario.php">Calendário</a>
              <a href="orcamento.php">Orçamento</a>
              <a href="itens.php">Serviços</a>
              <a href="gestao-contratos.php">Gestão de Contratos</a>
              <a href="tarefas.php">Lista de Tarefas</a>
            </div>
          </div>
          <a href="contato.php" class="nav-link">Contato</a>

          <?php if (isset($_SESSION["usuario_id"])): ?>
            <div class="profile-dropdown-wrapper">
              <img src="../user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>"
                alt="Foto de perfil" class="profile-avatar" onclick="toggleProfileDropdown()">
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <div class="profile-dropdown-user">
                    <img src="../user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>"
                      alt="Avatar" class="profile-dropdown-avatar">
                    <div class="profile-dropdown-info">

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
                    <button type="submit" name="logout" class="profile-dropdown-item logout"
                      style="width: 100%; text-align: left; background: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem;">
                      <svg fill="hsl(var(--foreground))" width="800px" height="800px" viewBox="0 0 36 36" version="1.1"
                        preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink">
                        <title>logout-line</title>
                        <path d="M7,6H23v9.8h2V6a2,2,0,0,0-2-2H7A2,2,0,0,0,5,6V30a2,2,0,0,0,2,2H23a2,2,0,0,0,2-2H7Z"
                          class="clr-i-outline clr-i-outline-path-1"></path>
                        <path
                          d="M28.16,17.28a1,1,0,0,0-1.41,1.41L30.13,22H15.63a1,1,0,0,0-1,1,1,1,0,0,0,1,1h14.5l-3.38,3.46a1,1,0,1,0,1.41,1.41L34,23.07Z"
                          class="clr-i-outline clr-i-outline-path-2"></path>
                        <rect x="0" y="0" width="36" height="36" fill-opacity="0" />
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
    <section class="calendar-page">
      <div class="container">
        <div class="calendar-header">
          <div class="calendar-title-section">
            <h1 class="calendar-title">Calendário do Casamento</h1>
            <p class="calendar-description">
              Organize todas as datas importantes e compromissos do seu grande
              dia
            </p>
          </div>
          <div class="calendar-actions">
            <button class="btn-outline" id="criar-novo-evento">
              <svg class="plus-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
              </svg>
              Novo Evento
            </button>
          </div>
        </div>
        <div class="calendar-layout">
          <div class="calendar-main">
            <div class="calendar-controls-card">
              <div class="calendar-controls">
                <div class="calendar-nav">
                  <button class="nav-btn prev" id="prev-month-btn">
                    <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <polyline points="15,18 9,12 15,6" />
                    </svg>
                  </button>
                  <h2 class="calendar-month" id="calendar-month">Agosto 2025</h2>
                  <button class="nav-btn next" id="next-month-btn">
                    <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <polyline points="9,18 15,12 9,6" />
                    </svg>
                  </button>
                </div>
                <div class="view-modes">
                  <button class="view-btn active" data-view="month">
                    Mês
                  </button>
                  <button class="view-btn" data-view="week">Semana</button>
                  <button class="view-btn" data-view="day">Dia</button>
                </div>
              </div>
              <div id="view-month" class="calendar-view active">
                <div class="calendar-grid">
                  <div class="calendar-header-row">
                    <div class="calendar-day-header">Dom</div>
                    <div class="calendar-day-header">Seg</div>
                    <div class="calendar-day-header">Ter</div>
                    <div class="calendar-day-header">Qua</div>
                    <div class="calendar-day-header">Qui</div>
                    <div class="calendar-day-header">Sex</div>
                    <div class="calendar-day-header">Sáb</div>
                  </div>
                  <div class="calendar-days" id="calendar-days-container"></div>
                </div>
              </div>
              <div id="view-week" class="calendar-view" style="display: none">
                <div class="calendar-week-grid"
                  style="display: grid; grid-template-columns: repeat(7, 1fr); border-top: 1px solid #eee;">
                  <div class="calendar-week-day">
                    Dom<br /><small>04/08</small>
                  </div>
                  <div class="calendar-week-day">
                    Seg<br /><small>05/08</small>
                  </div>
                  <div class="calendar-week-day">
                    Ter<br /><small>06/08</small>
                  </div>
                  <div class="calendar-week-day">
                    Qua<br /><small>07/08</small>
                  </div>
                  <div class="calendar-week-day">
                    Qui<br /><small>08/08</small>
                  </div>
                  <div class="calendar-week-day">
                    Sex<br /><small>09/08</small>
                    <div class="event-dot"></div>
                  </div>
                  <div class="calendar-week-day">
                    Sáb<br /><small>10/08</small>
                  </div>
                </div>
              </div>
              <div id="view-day" class="calendar-view" style="display: none">
                <div class="calendar-day-view"
                  style="display: flex; flex-direction: column; border: 1px solid #f3f4f6;">
                  <div class="calendar-hour-slot"
                    style="padding: 0.75rem; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;">
                    <span class="hour-label" style="font-weight: 500; color: #6b7280">09:00</span>
                    <span class="hour-event"
                      style="background: hsl(var(--primary)); color: white; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.875rem;">Reunião
                      com fotógrafo</span>
                  </div>
                  <div class="calendar-hour-slot"
                    style="padding: 0.75rem; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;">
                    <span class="hour-label" style="font-weight: 500; color: #6b7280">14:00</span>
                    <span class="hour-event"
                      style="background: hsl(var(--primary)); color: white; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.875rem;">Visita
                      ao local</span>
                  </div>
                  <div class="calendar-hour-slot"
                    style="padding: 0.75rem; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;">
                    <span class="hour-label" style="font-weight: 500; color: #6b7280">18:00</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="upcoming-events-card">
              <div class="events-header">
                <h3>Próximos Eventos</h3>
                <div class="events-actions">
                  <button class="action-btn" id="filter-btn">
                    <svg class="filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46 22,3" />
                    </svg>
                  </button>

                  <div class="search-container">
                    <input type="text" class="search-input" id="search-input" placeholder="Buscar eventos..." />
                    <button type="button" class="action-btn" id="search-btn">
                      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M21 21l-4.35-4.35" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              <div class="events-list">
                <ul id="eventList"></ul>
              </div>
            </div>
          </div>
          <div class="calendar-sidebar">
            <div class="stats-card">
              <h3>Resumo</h3>
              <div class="stats-list">
                <div class="stat-row">
                  <span>Eventos este mês</span>
                  <span class="stat-value">4</span>
                </div>
                <div class="stat-row">
                  <span>Dias até o casamento</span>
                  <span class="stat-value highlight">127</span>
                </div>
                <div class="stat-row">
                  <span>Tarefas pendentes</span>
                  <span class="stat-value warning">8</span>
                </div>
              </div>
            </div>
            <div class="timeline-card">
              <h3>Timeline do Casamento</h3>
              <div class="timeline">
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <h4>12 meses antes</h4>
                    <ul>
                      <li>Definir data e local</li>
                      <li>Lista de convidados inicial</li>
                    </ul>
                  </div>
                </div>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <h4>9 meses antes</h4>
                    <ul>
                      <li>Contratar fotógrafo</li>
                      <li>Escolher buffet</li>
                    </ul>
                  </div>
                </div>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <h4>6 meses antes</h4>
                    <ul>
                      <li>Finalizar decoração</li>
                      <li>Música e DJ</li>
                    </ul>
                  </div>
                </div>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <h4>3 meses antes</h4>
                    <ul>
                      <li>Confirmações finais</li>
                      <li>Últimos ajustes</li>
                    </ul>
                  </div>
                </div>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <h4>1 mês antes</h4>
                    <ul>
                      <li>Lista final de convidados</li>
                      <li>Cronograma do dia</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="quick-actions-card">
              <h3>Ações Rápidas</h3>
              <div class="quick-actions">
                <button class="quick-action">
                  <svg class="bell-icon" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                  </svg>
                  <span>Configurar Lembretes</span>
                </button>
                <button class="quick-action">
                  <svg class="users-icon" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  <span>Convidar Fornecedores</span>
                </button>
                <button class="quick-action">
                  <svg class="calendar-icon" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                  </svg>
                  <span>Sincronizar Calendário</span>
                </button>
              </div>
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
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
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
            <svg style="width: 1rem; height: 1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>contato@plannerdesonhos.com</span>
          </div>
        </div>
        <div class="footer-links">
          <h3>Navegação</h3>
          <ul>
            <li><a href="../index.php">Início</a></li>
            <li>
              <a href="funcionalidades.php">Funcionalidades</a>
            </li>
            <li>
              <a href="contato.php">Contato</a>
            </li>
          </ul>
        </div>
        <div class="footer-modules">
          <h3>Legal</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li>
              <a href="../legal-pages/privacity-politics.html">Política de Privacidade</a>
            </li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
        <div
          style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
          <span>Feito com</span>
          <svg style="width: 1rem; height: 1rem; color: hsl(var(--primary)); margin: 0 0.25rem;" fill="currentColor"
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
    const eventosFromDB = <?php echo $eventosJson; ?>;
    const userId = <?php echo $idUsuario; ?>;
  </script>
  <script src="../js/calendario.js"></script>
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
    document.addEventListener('click', function (event) {
      const profile = document.querySelector('.user-profile');
      const dropdown = document.getElementById("profileDropdown");
      if (profile && !profile.contains(event.target)) {
        dropdown?.classList.remove("active");
      }
    });
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
          const mobileMenu = document.getElementById("mobileMenu");
          const hamburgerBtn = document.getElementById("hamburgerBtn");
          mobileMenu.classList.remove("active");
          hamburgerBtn.classList.remove("hamburger-active");
        }
      });
    });
    document.addEventListener("DOMContentLoaded", () => {
      const customSelects = document.querySelectorAll(".custom-select");

      customSelects.forEach((select) => {
        const selected = select.querySelector(".selected");
        const options = select.querySelector(".options");
        const hiddenInput = select.querySelector("input[type=hidden]");


        selected.addEventListener("click", () => {
          options.style.display =
            options.style.display === "block" ? "none" : "block";
        });


        options.querySelectorAll("li").forEach((option) => {
          option.addEventListener("click", () => {
            selected.textContent = option.textContent;
            hiddenInput.value = option.getAttribute("data-value");
            options.style.display = "none";
          });
        });


        document.addEventListener("click", (e) => {
          if (!select.contains(e.target)) {
            options.style.display = "none";
          }
        });
      });
    });

    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');

    searchBtn.addEventListener('click', () => {
      searchInput.classList.toggle('expanded');
      if (searchInput.classList.contains('expanded')) {
        searchInput.focus();
      }
    });


    document.addEventListener('click', (e) => {
      if (!searchBtn.contains(e.target) && !searchInput.contains(e.target)) {
        searchInput.classList.remove('expanded');
      }
    });

    const filterBtn = document.getElementById('filter-btn');
    const filterModal = document.getElementById('filter-modal');
    const closeFilterBtn = document.getElementById('close-filter-modal');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const clearFiltersBtn = document.getElementById('clear-filters');

    let activeFilters = {
      prioridade: [],
      cor: [],
      status: []
    };

    window.addEventListener('click', (e) => {
      if (e.target === filterModal) {
        filterModal.classList.remove('active');
      }
    })

    filterBtn.addEventListener('click', () => {
      filterModal.classList.add('active');
    });

    closeFilterBtn.addEventListener('click', () => {
      filterModal.classList.remove('active');
    });


    document.querySelectorAll('.filter-chip, .color-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        chip.classList.toggle('active');
      });
    });

    applyFiltersBtn.addEventListener('click', () => {

      activeFilters = {
        prioridade: [],
        cor: [],
        status: []
      };

      document.querySelectorAll('.filter-chip.active, .color-chip.active').forEach(chip => {
        const filterType = chip.dataset.filter;
        const filterValue = chip.dataset.value;
        activeFilters[filterType].push(filterValue);
      });


      filtrarEventos(activeFilters);
      filterModal.classList.remove('active');
    });

    clearFiltersBtn.addEventListener('click', () => {
      document.querySelectorAll('.filter-chip, .color-chip').forEach(chip => {
        chip.classList.remove('active');
      });
      activeFilters = { prioridade: [], cor: [], status: [] };
      filtrarEventos(activeFilters);
    });


    function filtrarEventos(filters) {
      const eventItems = document.querySelectorAll('.event-item');

      eventItems.forEach(item => {
        let shouldShow = true;


        if (filters.prioridade.length > 0) {
          const itemPrioridade = item.dataset.prioridade;
          if (!filters.prioridade.includes(itemPrioridade)) {
            shouldShow = false;
          }
        }


        if (filters.cor.length > 0) {
          const itemCor = item.dataset.cor;
          if (!filters.cor.includes(itemCor)) {
            shouldShow = false;
          }
        }


        if (filters.status.length > 0) {
          const itemStatus = item.dataset.status;
          if (!filters.status.includes(itemStatus)) {
            shouldShow = false;
          }
        }

        item.style.display = shouldShow ? 'flex' : 'none';
      });
    }


    searchInput.addEventListener('input', (e) => {
      const searchTerm = e.target.value.toLowerCase();
      const eventItems = document.querySelectorAll('.event-item');

      eventItems.forEach(item => {
        const eventTitle = item.querySelector('#nomeEvento')?.textContent.toLowerCase() || '';
        const eventLocation = item.querySelector('#localEvento')?.textContent.toLowerCase() || '';
        const eventTag = item.querySelector('#tagEvento')?.textContent.toLowerCase() || '';

        if (eventTitle.includes(searchTerm) || eventLocation.includes(searchTerm) || eventTag.includes(searchTerm)) {
          item.style.display = 'flex';
        } else {
          item.style.display = 'none';
        }
      });
    });
  </script>
</body>

</html>
