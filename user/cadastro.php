<?php
session_start();

// inclui conexão
require_once "../config/conexao.php";

// Verifica se veio POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nome       = $_POST["nome"];
  $nome_conj  = $_POST["nome_conj"];
  $genero     = $_POST["genero"];
  $idade      = $_POST["idade"];
  $telefone   = $_POST["num_telefone"];
  $email      = $_POST["email"];
  $senha      = password_hash($_POST["senha"], PASSWORD_DEFAULT);

  // Foto padrão de perfil
  $foto_perfil = "default.png";

  $sql = "INSERT INTO usuarios (nome, nome_conjuge, genero, idade, telefone, email, senha, cargo) 
          VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente')";
  $stmt = $pdo->prepare($sql);

  try {
    $stmt->execute([$nome, $nome_conj, $genero, $idade, $telefone, $email, $senha]);

    // pega ID do usuário inserido
    $id_usuario = $pdo->lastInsertId();

    // cria sessão
    $_SESSION["usuario_id"]   = $id_usuario;
    $_SESSION["nome"]         = $nome;
    $_SESSION["foto_perfil"]  = $foto_perfil;

    header("Location: ../index.php");
    exit;
  } catch (PDOException $e) {
    echo "<div class='mensagem-erro'>Erro ao cadastrar: " . $e->getMessage() . "</div>";
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WeddingEasy</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .input-group {
      position: relative;
      margin-top: 1rem;
      display: flex;
      align-items: center;
    }

    .input-group svg {
      position: absolute;
      left: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      width: 1.25rem;
      height: 1.25rem;
      color: hsl(var(--primary));
      pointer-events: none;
    }

    .input-group input,
    .input-group select {
      width: 100%;
      padding: 0.75rem 0.75rem 0.75rem 3rem;
      border-radius: 0.75rem;
      border: 1px solid hsl(var(--border));
      background-color: hsl(var(--card));
      color: hsl(var(--foreground));
      font-family: "Roboto", sans-serif;
      transition: all 0.2s;
    }

    .input-group input:focus,
    .input-group select:focus {
      border-color: hsl(var(--primary));
      outline: none;
    }

    .mensagem-sucesso {
      color: #4CAF50;
      background-color: #E8F5E8;
      border: 1px solid #4CAF50;
      padding: 1rem;
      border-radius: 0.75rem;
      margin-bottom: 1rem;
      text-align: center;
    }

    .mensagem-erro {
      color: #F44336;
      background-color: #FFEBEE;
      border: 1px solid #F44336;
      padding: 1rem;
      border-radius: 0.75rem;
      margin-bottom: 1rem;
      text-align: center;
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="../index.html" class="logo">
          <div class="heart-icon">
            <svg
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 24 24">
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">WeddingEasy</span>
        </a>
        <nav class="nav">
          <a href="../index.html" class="nav-link">Início</a>
          <a href="login.html" class="btn-primary" style="align-items: center">Login</a>
        </nav>
        <button
          id="hamburgerBtn"
          class="mobile-menu-btn"
          onclick="toggleMobileMenu()">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
      <div id="mobileMenu" class="mobile-menu">
        <nav
          style="
              display: flex;
              flex-direction: column;
              gap: 1rem;
              padding: 1rem 0;
              border-top: 1px solid hsl(var(--border));
              margin-top: 0.5rem;
            ">
          <a href="../index.html" class="nav-link">Início</a>
          <a href="login.html" class="btn-primary" style="align-items: center">Login</a>
        </nav>
      </div>
    </div>
  </header>
  <main>
    <section class="page-content" style="padding-top: 6rem">
      <div class="container" style="max-width: 600px; margin: auto">
        <div class="page-header" style="text-align: center">
          <h1 class="page-title">Cadastro</h1>
          <p class="page-description">
            Preencha suas informações para criar sua conta no WeddingEasy.
          </p>
        </div>
        <form class="cadastro-form" action="cadastro.php" method="POST">
          <div class="card form-section" style="margin-bottom: 1rem">
            <h2>Informações Pessoais</h2>
            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path
                  d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zM12 14c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z" />
              </svg>
              <input
                type="text"
                name="nome"
                placeholder="Seu nome"
                required />
            </div>
            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path
                  d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zM12 14c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z" />
              </svg>
              <input
                type="text"
                name="nome_conj"
                placeholder="Nome do cônjuge"
                required />
            </div>
            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path d="M12 2L2 7v13h20V7l-10-5zm0 2.18l7 3.5v9.64H5V7.68l7-3.5z" />
              </svg>
              <select name="genero" required>
                <option value="" disabled selected>Selecione o gênero</option>
                <option value="Masculino">Masculino</option>
                <option value="Feminino">Feminino</option>
              </select>
            </div>
            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path
                  d="M19 3h-1V1h-2v2H8V1H6v2H5C3.9 3 3 3.9 3 5v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H5V8h14v13z" />
              </svg>
              <input
                type="number"
                name="idade"
                placeholder="Idade"
                min="0"
                required />
            </div>
          </div>
          <div class="card form-section" style="margin-bottom: 1rem">
            <h2>Contatos</h2>

            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path
                  d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.11-.21 11.36 11.36 0 0 0 3.54.57 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1C7.83 21.5 2.5 16.17 2.5 10a1 1 0 0 1 1-1H7a1 1 0 0 1 1 1c0 1.23.21 2.42.62 3.5z" />
              </svg>
              <input
                type="text"
                name="num_telefone"
                placeholder="Número de telefone"
                required />
            </div>
            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path
                  d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
              </svg>
              <input
                type="email"
                name="email"
                placeholder="E-mail"
                required />
            </div>
          </div>
          <div class="card form-section" style="margin-bottom: 1rem">
            <h2>Segurança</h2>
            <div class="input-group">
              <svg viewBox="0 0 24 24">
                <path d="M12 1L3 5v6c0 5 9 11 9 11s9-6 9-11V5l-9-4z" />
              </svg>
              <input
                type="password"
                name="senha"
                placeholder="Senha"
                required />
            </div>
          </div>
          <button type="submit" class="btn-primary" style="margin-top: 1rem; width: 100%">
            Cadastrar
          </button>
          <div style="text-align: center; margin-top: 1rem">
            <a href="login.html" style="color: hsl(var(--primary)); text-decoration: none">
              Já tem uma conta? Fazer login
            </a>
          </div>
        </form>
      </div>
    </section>
  </main>
  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById("mobileMenu");
      menu.classList.toggle("active");
      document
        .getElementById("hamburgerBtn")
        .classList.toggle("hamburger-active");
    }
  </script>
</body>

</html>