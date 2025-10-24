<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me_fornecedor";
$cookieTime = time() + (86400 * 30); 
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"]) && $_POST["acao"] === "login") {
    $email = trim($_POST["email"] ?? "");
    $senha = trim($_POST["senha"] ?? "");

    if (empty($email) || empty($senha)) {
        $mensagem = "<div class='mensagem-erro'>Por favor, preencha todos os campos.</div>";
    } else {
        try {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM fornecedores LIKE 'senha'");
            if ($checkColumn->rowCount() === 0) {
                $mensagem = "<div class='mensagem-erro'>Sistema de login não configurado. Execute o script SQL em scripts/add_senha_to_fornecedores.sql</div>";
            } else {
                $stmt = $pdo->prepare("SELECT id_fornecedor, nome_fornecedor, email, senha FROM fornecedores WHERE email = ?");
                $stmt->execute([$email]);
                $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$fornecedor) {
                    $mensagem = "<div class='mensagem-erro'>Email não encontrado. Verifique seu email ou cadastre-se.</div>";
                } elseif (empty($fornecedor["senha"])) {
                    $mensagem = "<div class='mensagem-erro'>Conta sem senha configurada. Por favor, cadastre-se novamente.</div>";
                } elseif (password_verify($senha, $fornecedor["senha"])) {
                    session_regenerate_id(true);
                    $_SESSION["fornecedor_id"] = (int)$fornecedor["id_fornecedor"];
                    $_SESSION["fornecedor_nome"] = $fornecedor["nome_fornecedor"];
                    $_SESSION["fornecedor_email"] = $fornecedor["email"];

                    $checkTokenColumn = $pdo->query("SHOW COLUMNS FROM fornecedores LIKE 'remember_token'");
                    if ($checkTokenColumn->rowCount() > 0) {
                        $token = bin2hex(random_bytes(16));
                        setcookie($cookieName, $token, $cookieTime, "/", "", false, true);
                        $stmt = $pdo->prepare("UPDATE fornecedores SET remember_token = ? WHERE id_fornecedor = ?");
                        $stmt->execute([$token, $fornecedor["id_fornecedor"]]);
                    }

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $mensagem = "<div class='mensagem-erro'>Senha incorreta. Tente novamente.</div>";
                }
            }
        } catch (PDOException $e) {
            $mensagem = "<div class='mensagem-erro'>Erro ao fazer login: " . htmlspecialchars($e->getMessage()) . "</div>";
            error_log("Login error: " . $e->getMessage());
        }
    }
}


if (!isset($_SESSION['fornecedor_id']) && isset($_COOKIE[$cookieName])) {
    $cookieToken = $_COOKIE[$cookieName];
    try {
        $checkTokenColumn = $pdo->query("SHOW COLUMNS FROM fornecedores LIKE 'remember_token'");
        if ($checkTokenColumn->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id_fornecedor, nome_fornecedor, email FROM fornecedores WHERE remember_token = ?");
            $stmt->execute([$cookieToken]);
            $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fornecedor) {
                $_SESSION['fornecedor_id'] = (int)$fornecedor['id_fornecedor'];
                $_SESSION['fornecedor_nome'] = $fornecedor['nome_fornecedor'];
                $_SESSION['fornecedor_email'] = $fornecedor['email'];
                header("Location: dashboard.php");
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Cookie restore error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login de Fornecedor - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .supplier-login-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      align-items: center;
      min-height: calc(100vh - 120px);
      padding: 2rem 0;
    }

    .supplier-login-content {
      padding: 2rem;
    }

    .supplier-login-content h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: hsl(var(--foreground));
    }

    .supplier-login-content p {
      color: hsl(var(--muted-foreground));
      margin-bottom: 2rem;
      line-height: 1.6;
    }

    .supplier-login-form {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: hsl(var(--foreground));
    }

    .form-group input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      font-family: inherit;
      font-size: 1rem;
      color: hsl(var(--foreground));
      background: hsl(var(--background));
      box-sizing: border-box;
      transition: all 0.2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: hsl(var(--primary));
      box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
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

    .form-actions button {
      width: 100%;
      padding: 0.75rem;
      border: none;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .form-actions a {
      text-align: center;
      padding: 0.75rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s;
    }

    .benefits-list {
      list-style: none;
      padding: 0;
    }

    .benefits-list li {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      color: hsl(var(--foreground));
    }

    .benefits-list svg {
      width: 24px;
      height: 24px;
      color: hsl(var(--primary));
      flex-shrink: 0;
    }

    @media (max-width: 768px) {
      .supplier-login-container {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .supplier-login-content h1 {
        font-size: 1.5rem;
      }

      .supplier-login-form {
        padding: 1.5rem;
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
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>
        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <a href="../user/login.php" class="nav-link">Cliente</a>
          <a href="register.php" class="btn-primary">Cadastre-se</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="supplier-login-container">
          <div class="supplier-login-content">
            <h1>Bem-vindo, Fornecedor!</h1>
            <p>Gerencie seus serviços, itens e pacotes de forma simples e eficiente. Conecte-se com clientes que procuram por seus serviços.</p>
            
            <ul class="benefits-list">
              <li>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Cadastre seus serviços e itens
              </li>
              <li>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Crie pacotes completos
              </li>
              <li>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Receba avaliações de clientes
              </li>
              <li>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Gerencie contratos e orçamentos
              </li>
            </ul>
          </div>

          <div class="supplier-login-form">
            <h2 style="text-align: center; margin-bottom: 1.5rem; color: hsl(var(--foreground));">Login de Fornecedor</h2>

            <?php if ($mensagem) echo $mensagem; ?>

            <form method="POST">
              <input type="hidden" name="acao" value="login">

              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required />
              </div>

              <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Sua senha" required />
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary">Entrar</button>
                <a href="register.php" class="btn-outline">Não tem conta? Cadastre-se</a>
              </div>
            </form>

            <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
              <a href="../user/login.php" style="color: hsl(var(--primary)); text-decoration: underline;">Sou cliente</a>
            </p>
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
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">Planner de Sonhos</span>
          </a>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>
</body>

</html>
