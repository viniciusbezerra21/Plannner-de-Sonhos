<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";
$cookieTime = time() + (86400 * 30); // 30 dias
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "login") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $stmt = $pdo->prepare("SELECT id_usuario, nome, email, senha, cargo FROM usuarios WHERE email = ? OR nome = ?");
    $stmt->execute([$email, $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario["senha"])) {
        // Protege a sessão
        session_regenerate_id(true);

        // PADRÃO: usar 'id_usuario' porque o resto do sistema espera essa chave
        $_SESSION["id_usuario"] = (int)$usuario["id_usuario"];
        $_SESSION["nome"] = $usuario["nome"];
        $_SESSION["cargo"] = $usuario["cargo"]; // Adicionar cargo na sessão
        $_SESSION["foto_perfil"] = $usuario['foto_perfil'] ?? 'default.png';

        $token = bin2hex(random_bytes(16));
        setcookie($cookieName, $token, $cookieTime, "/", "", false, true);
        $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id_usuario = ?");
        $stmt->execute([$token, $usuario["id_usuario"]]);

        if ($usuario["cargo"] === "dev") {
            header("Location: ../pages/dev.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        $mensagem = "<div class='mensagem-erro'>E-mail/usuário ou senha inválidos.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos</title>
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

    .input-group input {
      width: 100%;
      padding: 0.75rem 0.75rem 0.75rem 3rem;
      border-radius: 0.75rem;
      border: 1px solid hsl(var(--border));
      background-color: hsl(var(--card));
      color: hsl(var(--card-foreground));
      font-family: "Roboto", sans-serif;
      transition: all 0.2s;
    }

    .input-group input:focus {
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
          <a href="cadastro.php" class="btn-primary" style="align-items: center">Cadastre-se</a>
        </nav>
        <button id="hamburgerBtn" class="mobile-menu-btn" onclick="toggleMobileMenu()">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
      <div id="mobileMenu" class="mobile-menu">
        <nav style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem 0; border-top: 1px solid hsl(var(--border)); margin-top: 0.5rem;">
          <a href="../index.php" class="nav-link">Início</a>
          <a href="cadastro.php" class="btn-primary" style="align-items: center">Cadastre-se</a>
        </nav>
      </div>
    </div>
  </header>
  <main>
    <section class="page-content" style="padding-top: 6rem">
      <div class="container" style="max-width: 400px; margin: auto">
        <div class="page-header" style="text-align: center">
          <h1 class="page-title">Login</h1>
          <p class="page-description">
            Entre na sua conta para gerenciar seus eventos e casamento.
          </p>
        </div>

        <?php if ($mensagem) echo $mensagem; ?>

        <form method="POST">
          <input type="hidden" name="acao" value="login">

          <div class="card form-section" style="margin-bottom: 1rem">

            <div class="input-group">
              <input type="text" name="email" placeholder="E-mail ou Nome de Usuário" required />
            </div>

            <div class="input-group">
              <input type="password" name="senha" placeholder="Senha" required />
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 1rem; width: 100%; text-align: center">
              Entrar
            </button>
          </div>

          <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem">
            Não tem uma conta?
            <a href="cadastro.php" style="color: hsl(var(--primary)); text-decoration: underline">Cadastre-se aqui</a>.
          </p>
        </form>
      </div>
    </section>
  </main>

  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById("mobileMenu");
      menu.classList.toggle("active");
      document.getElementById("hamburgerBtn").classList.toggle("hamburger-active");
    }
  </script>
</body>

</html>
