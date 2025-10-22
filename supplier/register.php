<?php
session_start();
require_once "../config/conexao.php";

$mensagem = "";
$tipo_mensagem = "";

$categorias = [
  'buffet' => 'Buffet',
  'bolo' => 'Bolo e Confeitaria',
  'fotografia' => 'Fotografia e Vídeo',
  'decoracao' => 'Decoração',
  'entretenimento' => 'Entretenimento (Bandas, DJs)',
  'moda' => 'Moda (Vestidos, Noivo)',
  'papelaria' => 'Papelaria',
  'pacote_completo' => 'Pacotes Completos'
];

$nome_fornecedor = '';
$categoria = '';
$email = '';
$telefone = '';
$descricao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_supplier'])) {
    $nome_fornecedor = trim($_POST['nome_fornecedor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($nome_fornecedor) || empty($categoria) || empty($email) || empty($senha)) {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
        $tipo_mensagem = 'erro';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Por favor, insira um email válido.';
        $tipo_mensagem = 'erro';
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = 'As senhas não coincidem.';
        $tipo_mensagem = 'erro';
    } elseif (strlen($senha) < 6) {
        $mensagem = 'A senha deve ter no mínimo 6 caracteres.';
        $tipo_mensagem = 'erro';
    } elseif (!array_key_exists($categoria, $categorias)) {
        $mensagem = 'Categoria inválida selecionada.';
        $tipo_mensagem = 'erro';
    } else {
        try {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $mensagem = 'Este email já está cadastrado.';
                $tipo_mensagem = 'erro';
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO fornecedores (nome_fornecedor, categoria, email, telefone, descricao, senha, apenas_pacotes)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$nome_fornecedor, $categoria, $email, $telefone, $descricao, $senha_hash]);
                
                $fornecedor_id = $pdo->lastInsertId();
                $mensagem = 'Fornecedor cadastrado com sucesso! Faça login para continuar.';
                $tipo_mensagem = 'sucesso';
                
                // Clear form values
                $nome_fornecedor = '';
                $categoria = '';
                $email = '';
                $telefone = '';
                $descricao = '';
            }
        } catch (PDOException $e) {
            $mensagem = 'Erro ao cadastrar fornecedor. Tente novamente.';
            $tipo_mensagem = 'erro';
            error_log("Error registering supplier: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cadastro de Fornecedor - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .registration-container {
      max-width: 600px;
      margin: 2rem auto;
    }

    .registration-form {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
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

    .form-group input,
    .form-group select,
    .form-group textarea {
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

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: hsl(var(--primary));
      box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
    }

    .form-group input.error,
    .form-group select.error {
      border-color: #dc3545;
    }

    .form-group input.success,
    .form-group select.success {
      border-color: #28a745;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .form-actions button,
    .form-actions a {
      flex: 1;
      padding: 0.75rem;
      text-align: center;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s;
      border: none;
      cursor: pointer;
    }

    .message {
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      border: 1px solid;
    }

    .message.sucesso {
      background: #d4edda;
      color: #155724;
      border-color: #c3e6cb;
    }

    .message.erro {
      background: #f8d7da;
      color: #721c24;
      border-color: #f5c6cb;
    }

    .success-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }

    .success-actions a {
      flex: 1;
      padding: 0.75rem;
      text-align: center;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s;
    }

    .password-group {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .password-group .form-group {
      margin-bottom: 0;
    }

    @media (max-width: 768px) {
      .password-group {
        grid-template-columns: 1fr;
      }

      .form-actions {
        flex-direction: column;
      }

      .form-actions button,
      .form-actions a {
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
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>
        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <a href="login.php" class="btn-primary">Login</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header">
          <h1 class="page-title">
            Cadastre-se como <span class="gradient-text">Fornecedor</span>
          </h1>
          <p class="page-description">
            Preencha o formulário abaixo para se registrar e começar a oferecer seus serviços.
          </p>
        </div>

        <div class="registration-container">
          <?php if (!empty($mensagem)): ?>
            <div class="message <?php echo $tipo_mensagem; ?>">
              <?php echo htmlspecialchars($mensagem); ?>
              <?php if ($tipo_mensagem === 'sucesso'): ?>
                <div class="success-actions">
                  <a href="login.php" class="btn-primary">Fazer Login</a>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <form method="post" class="registration-form" id="registrationForm">
            <div class="form-group">
              <label for="nome_fornecedor">Nome da Empresa *</label>
              <input type="text" id="nome_fornecedor" name="nome_fornecedor" value="<?php echo htmlspecialchars($nome_fornecedor ?? ''); ?>" required placeholder="Ex: Buffet Delícias">
            </div>

            <div class="form-group">
              <label for="categoria">Categoria Principal *</label>
              <select id="categoria" name="categoria" required>
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categorias as $key => $nome): ?>
                  <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (($categoria ?? '') === $key) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($nome); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="email">Email *</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required placeholder="seu@email.com">
            </div>

            <div class="form-group">
              <label for="telefone">Telefone</label>
              <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" placeholder="(11) 99999-9999">
            </div>

            <div class="password-group">
              <div class="form-group">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres">
              </div>
              <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha *</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Confirme sua senha">
              </div>
            </div>

            <div class="form-group">
              <label for="descricao">Descrição do Serviço</label>
              <textarea id="descricao" name="descricao" placeholder="Descreva seus serviços, experiência e diferenciais..."><?php echo htmlspecialchars($descricao ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
              <a href="../index.php" class="btn-outline">Cancelar</a>
              <button type="submit" name="register_supplier" class="btn-primary">Cadastrar Fornecedor</button>
            </div>

            <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
              Já tem uma conta? <a href="login.php" style="color: hsl(var(--primary)); text-decoration: underline;">Faça login aqui</a>.
            </p>
          </form>
        </div>
      </div>
    </section>
  </main>

  <script>
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
      const senha = document.getElementById('senha').value;
      const confirmarSenha = document.getElementById('confirmar_senha').value;
      const email = document.getElementById('email').value;

      if (senha !== confirmarSenha) {
        e.preventDefault();
        alert('As senhas não coincidem!');
        return false;
      }

      if (senha.length < 6) {
        e.preventDefault();
        alert('A senha deve ter no mínimo 6 caracteres!');
        return false;
      }

      if (!email.includes('@')) {
        e.preventDefault();
        alert('Por favor, insira um email válido!');
        return false;
      }
    });

    // Real-time password match validation
    document.getElementById('confirmar_senha').addEventListener('input', function() {
      const senha = document.getElementById('senha').value;
      const confirmarSenha = this.value;
      
      if (confirmarSenha.length > 0) {
        if (senha === confirmarSenha) {
          this.classList.remove('error');
          this.classList.add('success');
        } else {
          this.classList.remove('success');
          this.classList.add('error');
        }
      } else {
        this.classList.remove('success', 'error');
      }
    });
  </script>

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
