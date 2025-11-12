<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";
$cookieTime = time() + (86400 * 30);
$mensagem = "";
$userType = $_GET['type'] ?? 'cliente'; // cliente, fornecedor, cerimonialista

// Validar tipo de usuário
if (!in_array($userType, ['cliente', 'fornecedor', 'cerimonialista'])) {
    $userType = 'cliente';
}

// Cookie restore logic
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['fornecedor_id']) && isset($_COOKIE[$cookieName])) {
    $cookieToken = $_COOKIE[$cookieName];
    try {
        // Try usuario first
        $stmt = $pdo->prepare("SELECT id_usuario, nome, cargo, foto_perfil, email, tipo_usuario FROM usuarios WHERE remember_token = ?");
        $stmt->execute([$cookieToken]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $_SESSION['usuario_id'] = (int) $usuario['id_usuario'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
            $_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?? 'default.png';
            
            if ($usuario['tipo_usuario'] === 'cerimonialista') {
                header("Location: ../pages/cerimonialista-dashboard.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
        }
        
        // Try fornecedor
        $stmt = $pdo->prepare("SELECT id_fornecedor, nome_fornecedor FROM fornecedores WHERE remember_token = ?");
        $stmt->execute([$cookieToken]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fornecedor) {
            $_SESSION['fornecedor_id'] = (int) $fornecedor['id_fornecedor'];
            $_SESSION['fornecedor_nome'] = $fornecedor['nome_fornecedor'];
            header("Location: ../supplier/dashboard.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Cookie restore error: " . $e->getMessage());
    }
}

// Login logic
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "login") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $lembrarMe = isset($_POST["lembrar_me"]) && $_POST["lembrar_me"] === "1";

    if ($userType === 'fornecedor') {
        // Fornecedor login
        $stmt = $pdo->prepare("SELECT id_fornecedor, nome_fornecedor, email, senha FROM fornecedores WHERE email = ?");
        $stmt->execute([$email]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fornecedor && password_verify($senha, $fornecedor["senha"])) {
            session_regenerate_id(true);
            $_SESSION["fornecedor_id"] = (int) $fornecedor["id_fornecedor"];
            $_SESSION["fornecedor_nome"] = $fornecedor["nome_fornecedor"];
            $_SESSION["fornecedor_email"] = $fornecedor["email"];

            if ($lembrarMe) {
                $token = bin2hex(random_bytes(16));
                setcookie($cookieName, $token, $cookieTime, "/", "", false, true);
                $stmt = $pdo->prepare("UPDATE fornecedores SET remember_token = ? WHERE id_fornecedor = ?");
                $stmt->execute([$token, $fornecedor["id_fornecedor"]]);
            }

            header("Location: ../supplier/dashboard.php");
            exit;
        } else {
            $mensagem = "<div class='mensagem-erro'>E-mail ou senha inválidos.</div>";
        }
    } else {
        // Cliente ou Cerimonialista login - search by email only
        $stmt = $pdo->prepare("SELECT id_usuario, nome, email, senha, tipo_usuario, foto_perfil FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario["senha"])) {
            session_regenerate_id(true);
            $_SESSION["usuario_id"] = (int) $usuario["id_usuario"];
            $_SESSION["nome"] = $usuario["nome"];
            $_SESSION["tipo_usuario"] = $usuario["tipo_usuario"];
            $_SESSION["foto_perfil"] = $usuario['foto_perfil'] ?? 'default.png';

            if ($lembrarMe) {
                $token = bin2hex(random_bytes(16));
                setcookie($cookieName, $token, $cookieTime, "/", "", false, true);
                $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id_usuario = ?");
                $stmt->execute([$token, $usuario["id_usuario"]]);
            }

            if ($usuario['tipo_usuario'] === 'cerimonialista') {
                header("Location: ../pages/cerimonialista-dashboard.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
        } else {
            $mensagem = "<div class='mensagem-erro'>E-mail ou senha inválidos.</div>";
        }
    }
}

// Define titles and descriptions for each user type
$titles = [
    'cliente' => 'Login de Cliente',
    'fornecedor' => 'Login de Fornecedor',
    'cerimonialista' => 'Login de Cerimonialista'
];

$descriptions = [
    'cliente' => 'Acesse sua conta para organizar o casamento dos seus sonhos.',
    'fornecedor' => 'Gerencie seus serviços e pacotes',
    'cerimonialista' => 'Acesse seus eventos e clientes'
];

$placeholders = [
    'cliente' => 'E-mail ou Nome de Usuário',
    'fornecedor' => 'seu@email.com',
    'cerimonialista' => 'seu@email.com'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $titles[$userType]; ?> - Planner de Sonhos</title>
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

    .type-selector {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .type-button {
      padding: 0.75rem 1.5rem;
      border: 2px solid hsl(var(--border));
      border-radius: 0.75rem;
      background: transparent;
      color: hsl(var(--foreground));
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-block;
      font-size: 0.9rem;
    }

    .type-button:hover {
      border-color: hsl(var(--primary));
      color: hsl(var(--primary));
    }

    .type-button.active {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      border-color: hsl(var(--primary));
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

    .remember-me-container {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1rem;
      padding: 0.5rem 0;
    }

    .remember-me-container input[type="checkbox"] {
      width: 1.125rem;
      height: 1.125rem;
      cursor: pointer;
      accent-color: hsl(var(--primary));
    }

    .remember-me-container label {
      font-size: 0.9rem;
      color: hsl(var(--foreground));
      cursor: pointer;
      user-select: none;
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

    .form-actions button {
      padding: 0.75rem;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
    }

    .form-actions button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .form-footer {
      text-align: center;
      margin-top: 1rem;
      font-size: 0.875rem;
    }

    .form-footer a {
      color: hsl(var(--primary));
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .login-container {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .login-content h1 {
        font-size: 1.5rem;
      }

      .type-selector {
        justify-content: center;
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
          <a href="signup-unified.php" class="btn-primary" style="align-items: center">Cadastre-se</a>
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
            <h2><?php echo $titles[$userType]; ?></h2>

            <div class="type-selector">
              <a href="?type=cliente" class="type-button <?php echo $userType === 'cliente' ? 'active' : ''; ?>">Cliente</a>
              <a href="?type=fornecedor" class="type-button <?php echo $userType === 'fornecedor' ? 'active' : ''; ?>">Fornecedor</a>
              <a href="?type=cerimonialista" class="type-button <?php echo $userType === 'cerimonialista' ? 'active' : ''; ?>">Cerimonialista</a>
            </div>

            <?php if ($mensagem) echo $mensagem; ?>

            <form method="POST">
              <input type="hidden" name="acao" value="login">

              <div class="input-group">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M3 8l7.89 5.26a2 2 0 002.22 0L12 5.67l7.78-7.78M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <input type="text" name="email" placeholder="<?php echo $placeholders[$userType]; ?>" required />
              </div>

              <div class="input-group">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <input type="password" name="senha" placeholder="Senha" required />
              </div>

              <div class="remember-me-container">
                <input type="checkbox" id="lembrar_me" name="lembrar_me" value="1" />
                <label for="lembrar_me">Manter-me conectado</label>
              </div>

              <div class="form-actions">
                <button type="submit">Entrar</button>
              </div>

              <div class="form-footer">
                Não tem uma conta? <a href="signup-unified.php?type=<?php echo $userType; ?>">Cadastre-se aqui</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>

</html>
