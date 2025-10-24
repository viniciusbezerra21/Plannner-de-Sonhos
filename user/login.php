<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";
$cookieTime = time() + (86400 * 30);
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "login") {
  $email = $_POST["email"];
  $senha = $_POST["senha"];

  $stmt = $pdo->prepare("SELECT id_usuario, nome, email, senha, cargo, foto_perfil FROM usuarios WHERE email = ? OR nome = ?");
  $stmt->execute([$email, $email]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($usuario && password_verify($senha, $usuario["senha"])) {

    session_regenerate_id(true);

    $_SESSION["usuario_id"] = (int) $usuario["id_usuario"];
    $_SESSION["nome"] = $usuario["nome"];
    $_SESSION["cargo"] = $usuario["cargo"];
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
    .login-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      align-items: center;
      min-height: calc(100vh - 120px);
      padding: 2rem 0;
    }

    .login-content {
      padding: 2rem;
    }

    .login-content h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: hsl(var(--foreground));
    }

    .login-content p {
      color: hsl(var(--muted-foreground));
      margin-bottom: 2rem;
      line-height: 1.6;
    }

    .login-form {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .login-form h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: hsl(var(--foreground));
    }

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

    .form-actions {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 2rem;
    }

    .form-actions button,
    .form-actions a {
      padding: 0.75rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      text-align: center;
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.5rem 0;
      color: hsl(var(--muted-foreground));
      font-size: 0.85rem;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: hsl(var(--border));
    }

    .supplier-section {
      background: hsl(var(--primary) / 0.05);
      border: 1px solid hsl(var(--primary) / 0.2);
      border-radius: 0.75rem;
      padding: 1.5rem;
      text-align: center;
    }

    .supplier-section h3 {
      margin-top: 0;
      margin-bottom: 0.5rem;
      color: hsl(var(--foreground));
      font-size: 1rem;
    }

    .supplier-section p {
      margin-bottom: 1rem;
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
    }

    .supplier-buttons {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .supplier-buttons a {
      padding: 0.75rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      text-align: center;
      font-size: 0.9rem;
    }

    .btn-supplier-login {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
    }

    .btn-supplier-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-supplier-register {
      background: transparent;
      color: hsl(var(--primary));
      border: 2px solid hsl(var(--primary));
    }

    .btn-supplier-register:hover {
      background: hsl(var(--primary) / 0.1);
    }

    @media (max-width: 768px) {
      .login-container {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .login-content h1 {
        font-size: 1.5rem;
      }

      .login-form {
        padding: 1.5rem;
      }

      .supplier-buttons {
        flex-direction: row;
      }

      .supplier-buttons a {
        flex: 1;
      }
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
        <nav
          style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem 0; border-top: 1px solid hsl(var(--border)); margin-top: 0.5rem;">
          <a href="../index.php" class="nav-link">Início</a>
          <a href="cadastro.php" class="btn-primary" style="align-items: center">Cadastre-se</a>
        </nav>
      </div>
    </div>
  </header>
  <main>
    <section class="page-content" style="padding-top: 6rem">
      <div class="container">
        <div class="login-container">

          <div class="login-content">
            <h1>Bem-vindo!</h1>
            <p>Faça login para gerenciar seus eventos e casamento com facilidade. Acesse seu calendário, orçamento,
              fornecedores e muito mais.</p>

            <div style="background: hsl(var(--muted)); border-radius: 0.75rem; padding: 1.5rem; margin-top: 2rem;">
              <h3 style="margin-top: 0; color: hsl(var(--foreground));">Novo por aqui?</h3>
              <p style="color: hsl(var(--muted-foreground)); margin-bottom: 1rem;">Crie sua conta para começar a
                organizar o casamento dos seus sonhos.</p>
              <a href="cadastro.php" class="btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem;">Criar
                Conta</a>
            </div>
          </div>


          <div class="login-form">
            <h2>Login de Cliente</h2>

            <?php if ($mensagem)
              echo $mensagem; ?>

            <form method="POST">
              <input type="hidden" name="acao" value="login">

              <div class="input-group">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                  </path>
                </svg>
                <input type="text" name="email" placeholder="E-mail ou Nome de Usuário" required />
              </div>

              <div class="input-group">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                  </path>
                </svg>
                <input type="password" name="senha" placeholder="Senha" required />
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary">Entrar</button>
              </div>

              <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem">
                Não tem uma conta?
                <a href="cadastro.php" style="color: hsl(var(--primary)); text-decoration: underline">Cadastre-se
                  aqui</a>.
              </p>
            </form>


            <div class="divider">Ou</div>

            <div class="supplier-section">
              <h3>Sou Fornecedor</h3>
              <p>Gerencie seus serviços e pacotes</p>
              <div class="supplier-buttons">
                <a href="../supplier/login.php" class="btn-supplier-login">Login de Fornecedor</a>
                <a href="../supplier/register.php" class="btn-supplier-register">Cadastrar-se</a>
              </div>
            </div>
          </div>
        </div>
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