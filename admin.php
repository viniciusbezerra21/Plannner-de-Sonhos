<?php
session_start();
require_once "config/conexao.php"; 

$cookieName = "lembrar_me";
$cookieTime = time() + (86400 * 30); 


if (isset($_COOKIE[$cookieName]) && !isset($_SESSION['id_usuario'])) {
    $token = $_COOKIE[$cookieName];
    $stmt = $pdo->prepare("SELECT id_usuario, nome, cargo FROM usuarios WHERE remember_token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome']       = $usuario['nome'];
        $_SESSION['cargo']      = $usuario['cargo'];

        
        if ($usuario['cargo'] === 'dev') {
            header("Location: pages/dev.php");
            exit;
        } else {
            header("Location: index.php");
            exit;
        }
    }
}


if (isset($_SESSION['id_usuario']) && isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'dev') {
        header("Location: pages/dev.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}


$erro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $email_usuario = $_POST['email'];
    $senha_usuario = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT id_usuario, nome, senha, cargo FROM usuarios WHERE email = ? OR nome = ?");
    $stmt->execute([$email_usuario, $email_usuario]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha_usuario, $usuario['senha'])) {
       
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome']       = $usuario['nome'];
        $_SESSION['cargo']      = $usuario['cargo'];

        
        $token = bin2hex(random_bytes(16));
        setcookie($cookieName, $token, $cookieTime, "/");
        $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id_usuario = ?");
        $stmt->execute([$token, $usuario['id_usuario']]);

        
        if ($usuario['cargo'] === 'dev') {
            header("Location: pages/dev.php");
            exit;
        } else {
            header("Location: index.php");
            exit;
        }
    } else {
        $erro = "<div class='mensagem-erro'>E-mail/usuário ou senha inválidos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos - Administrador</title>
  <link rel="stylesheet" href="Style/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <link rel="shortcut icon" href="Style/assets/devicon.png" type="image/x-icon">
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
      --wedding-lavender: hsl(345, 60%, 90%);
      --wedding-purple: hsl(345, 60%, 40%);
      --wedding-rose-white: hsl(345, 100%, 98%);
      --wedding-dark-purple: hsl(345, 70%, 15%);
    }

    .input-group {
      position: relative;
      margin-top: 1rem;
      display: flex;
      align-items: center;
    }

    body {
      background: linear-gradient(135deg,
          rgb(194, 43, 81) 0%,
          rgb(0, 0, 0) 50%,
          rgb(100, 4, 20) 100%);
      color: white;
      font-family: "Roboto", sans-serif;
      line-height: 1.6;
      min-height: 100vh;
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
      color: #4caf50;
      background-color: #e8f5e8;
      border: 1px solid #4caf50;
      padding: 1rem;
      border-radius: 0.75rem;
      margin-bottom: 1rem;
      text-align: center;
    }

    .mensagem-erro {
      color: #f44336;
      background-color: #ffebee;
      border: 1px solid #f44336;
      padding: 1rem;
      border-radius: 0.75rem;
      margin-bottom: 1rem;
      text-align: center;
    }

    .logo-text {
      font-family: "Poppins", sans-serif;
      font-weight: 600;
      font-size: 1.25rem;
      color: white;
    }

    .page-title {
      font-size: clamp(2.5rem, 5vw, 3rem);
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: hsl(var(--primary-foreground));
    }

    .page-description {
      font-size: 1.25rem;
      animation: slideInFromTop 0.8s ease-out;
      color: hsl(var(--secondary-foreground));
      max-width: 48rem;
      margin: 0 auto;
    }

    input::placeholder {
      color: #fff;
      opacity: 0.7;
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="index.php" class="logo">
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
          <span class="logo-text">Planner de Sonhos</span>
        </a>
      </div>
    </div>
  </header>
  <main>
    <section class="page-content" style="padding-top: 6rem">
      <div class="container" style="max-width: 400px; margin: auto">
        <div class="page-header" style="text-align: center">
          <h1 class="page-title">Administrador</h1>
          <p class="page-description">
            Insira seus dados para acessar o painel de gestão.
          </p>
        </div>
       
        <form action="admin.php" method="POST">
          <input type="hidden" name="acao" value="login" />

          <div class="card form-section" style="margin-bottom: 1rem">
            <div
              class="input-group"
              style="fill: hsl(var(--primary-foreground))">
              <svg viewBox="0 0 24 24">
                <path
                  d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
              </svg>
              <input
                type="text"
                name="email"
                placeholder="E-mail ou Nome de Usuário"
                required />
            </div>

            <div
              class="input-group"
              style="fill: hsl(var(--primary-foreground))">
              <svg viewBox="0 0 24 24">
                <path
                  d="M12 17a2 2 0 0 0 2-2v-3a2 2 0 1 0-4 0v3a2 2 0 0 0 2 2zm6-6v3a6 6 0 0 1-12 0v-3a6 6 0 0 1 12 0z" />
              </svg>
              <input
                type="password"
                name="senha"
                placeholder="Senha"
                required />
            </div>

            <label
              style="
                  display: flex;
                  justify-content: flex-end;
                  font-size: 0.875rem;
                  margin-top: 0.5rem;
                ">
              <a
                href="recuperar_senha.html"
                style="color: hsl(var(--primary)); text-decoration: underline">Esqueceu a senha?</a>
            </label>

            <button
              type="submit"
              class="btn-primary"
              style="margin-top: 1rem; width: 100%; text-align: center">
              Entrar
            </button>
          </div>

          <p
            style="text-align: center; margin-top: 1rem; font-size: 0.875rem">
            Não tem uma conta?
            <a
              href="user/cadastro.php"
              style="color: hsl(var(--primary)); text-decoration: underline">Cadastre-se aqui</a>.
          </p>
        </form>
      </div>
    </section>
  </main>
</body>

</html>