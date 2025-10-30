<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me_fornecedor";

if (!isset($_SESSION['fornecedor_id'])) {
  header("Location: login.php");
  exit;
}

$fornecedor_id = (int) $_SESSION['fornecedor_id'];
$mensagem = "";
$tipo_mensagem = "";


try {
  $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
  $stmt->execute([$fornecedor_id]);
  $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$fornecedor) {
    session_destroy();
    header("Location: login.php");
    exit;
  }
} catch (PDOException $e) {
  error_log("Profile fetch error: " . $e->getMessage());
  $fornecedor = [];
}

if (isset($_POST['logout'])) {

  try {
    $stmt = $pdo->prepare("UPDATE fornecedores SET remember_token = NULL WHERE id_fornecedor = ?");
    $stmt->execute([$fornecedor_id]);
  } catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
  }


  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
  $nome = trim($_POST['nome_fornecedor'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefone = trim($_POST['telefone'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');
  $categoria = trim($_POST['categoria'] ?? '');

  if (empty($nome) || empty($email) || empty($categoria)) {
    $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
    $tipo_mensagem = 'erro';
  } else {
    try {
      $foto_perfil = null;
      $foto_dir = __DIR__ . '/fotos_perfil/';
      
      if (!file_exists($foto_dir)) {
        mkdir($foto_dir, 0755, true);
      }

      if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_perfil']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
          $new_filename = 'fornecedor_' . $fornecedor_id . '_' . time() . '.' . $ext;
          $upload_path = $foto_dir . $new_filename;
          
          if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_path)) {
            $foto_perfil = $new_filename;
            
            // Deletar foto antiga
            if (!empty($fornecedor['foto_perfil'])) {
              $old_file = $foto_dir . $fornecedor['foto_perfil'];
              if (file_exists($old_file)) {
                unlink($old_file);
              }
            }
          }
        }
      }

      $stmt = $pdo->prepare("SHOW COLUMNS FROM fornecedores LIKE 'foto_perfil'");
      $stmt->execute();
      $has_foto = $stmt->rowCount() > 0;

      if ($has_foto && $foto_perfil) {
        $sql = "UPDATE fornecedores SET nome_fornecedor = ?, email = ?, telefone = ?, descricao = ?, categoria = ?, foto_perfil = ? WHERE id_fornecedor = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $telefone, $descricao, $categoria, $foto_perfil, $fornecedor_id]);
      } else {
        $sql = "UPDATE fornecedores SET nome_fornecedor = ?, email = ?, telefone = ?, descricao = ?, categoria = ? WHERE id_fornecedor = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $telefone, $descricao, $categoria, $fornecedor_id]);
      }

      $_SESSION['fornecedor_nome'] = $nome;
      $_SESSION['fornecedor_email'] = $email;

      $mensagem = 'Perfil atualizado com sucesso!';
      $tipo_mensagem = 'sucesso';

      $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
      $stmt->execute([$fornecedor_id]);
      $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      $mensagem = 'Erro ao atualizar perfil. Tente novamente.';
      $tipo_mensagem = 'erro';
      error_log("Profile update error: " . $e->getMessage());
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
  $senha_atual = trim($_POST['senha_atual'] ?? '');
  $senha_nova = trim($_POST['senha_nova'] ?? '');
  $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');

  if (empty($senha_atual) || empty($senha_nova) || empty($confirmar_senha)) {
    $mensagem = 'Por favor, preencha todos os campos de senha.';
    $tipo_mensagem = 'erro';
  } elseif ($senha_nova !== $confirmar_senha) {
    $mensagem = 'As senhas não coincidem.';
    $tipo_mensagem = 'erro';
  } elseif (strlen($senha_nova) < 6) {
    $mensagem = 'A nova senha deve ter no mínimo 6 caracteres.';
    $tipo_mensagem = 'erro';
  } elseif (!password_verify($senha_atual, $fornecedor['senha'])) {
    $mensagem = 'Senha atual incorreta.';
    $tipo_mensagem = 'erro';
  } else {
    try {
      $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("UPDATE fornecedores SET senha = ? WHERE id_fornecedor = ?");
      $stmt->execute([$senha_hash, $fornecedor_id]);

      $mensagem = 'Senha alterada com sucesso!';
      $tipo_mensagem = 'sucesso';
    } catch (PDOException $e) {
      $mensagem = 'Erro ao alterar senha. Tente novamente.';
      $tipo_mensagem = 'erro';
      error_log("Password change error: " . $e->getMessage());
    }
  }
}

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
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Perfil - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <style>
    .profile-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }

    .profile-section {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .profile-section h2 {
      margin-top: 0;
      margin-bottom: 1.5rem;
      color: hsl(var(--foreground));
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

    .form-actions {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .form-actions button {
      flex: 1;
      padding: 0.75rem;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
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

    /* Estilos para foto de perfil */
    .profile-photo-section {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid hsl(var(--border));
    }

    .profile-photo-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 0 auto 1rem;
    }

    .profile-photo {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid hsl(var(--primary));
    }

    .profile-photo-placeholder {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: hsl(var(--muted));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: hsl(var(--muted-foreground));
      border: 4px solid hsl(var(--border));
    }

    .photo-upload-btn {
      display: inline-block;
      padding: 0.5rem 1rem;
      background: hsl(var(--primary));
      color: white;
      border-radius: 0.5rem;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }

    .photo-upload-btn:hover {
      background: hsl(var(--primary) / 0.9);
    }

    #foto_perfil_input {
      display: none;
    }

    @media (max-width: 768px) {
      .profile-container {
        grid-template-columns: 1fr;
      }

      .form-actions {
        flex-direction: column;
      }

      .form-actions button {
        flex: 1;
      }
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="dashboard.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>

        <nav class="nav">
          <a href="dashboard.php" class="nav-link">Dashboard</a>
          <a href="items.php" class="nav-link">Itens</a>
          <a href="packages.php" class="nav-link">Pacotes</a>
          <a href="profile.php" class="nav-link">Perfil</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header">
          <h1 class="page-title">Meu <span class="gradient-text">Perfil</span></h1>
          <p class="page-description">Gerencie suas informações e configurações.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
          <div class="message <?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
        <?php endif; ?>

        <div class="profile-container">

          <div class="profile-section">
            <h2>Informações da Empresa</h2>

            <!-- Adicionando enctype e seção de foto de perfil -->
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_profile">

              <!-- Seção de foto de perfil -->
              <div class="profile-photo-section">
                <div class="profile-photo-container">
                  <?php if (!empty($fornecedor['foto_perfil'])): ?>
                    <img src="fotos_perfil/<?php echo htmlspecialchars($fornecedor['foto_perfil']); ?>" alt="Foto de perfil" class="profile-photo" id="profile-photo-preview">
                  <?php else: ?>
                    <div class="profile-photo-placeholder" id="profile-photo-preview">
                      <?php echo strtoupper(substr($fornecedor['nome_fornecedor'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                </div>
                <label for="foto_perfil_input" class="photo-upload-btn">
                  Alterar Foto de Perfil
                </label>
                <input type="file" id="foto_perfil_input" name="foto_perfil" accept="image/*" onchange="previewProfilePhoto(event)">
              </div>

              <div class="form-group">
                <label for="nome_fornecedor">Nome da Empresa *</label>
                <input type="text" id="nome_fornecedor" name="nome_fornecedor"
                  value="<?php echo htmlspecialchars($fornecedor['nome_fornecedor'] ?? ''); ?>" required>
              </div>

              <div class="form-group">
                <label for="categoria">Categoria Principal *</label>
                <select id="categoria" name="categoria" required>
                  <option value="">Selecione uma categoria</option>
                  <?php foreach ($categorias as $key => $nome): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (($fornecedor['categoria'] ?? '') === $key) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($nome); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email"
                  value="<?php echo htmlspecialchars($fornecedor['email'] ?? ''); ?>" required>
              </div>

              <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone"
                  value="<?php echo htmlspecialchars($fornecedor['telefone'] ?? ''); ?>">
              </div>

              <div class="form-group">
                <label for="descricao">Descrição da Empresa</label>
                <textarea id="descricao"
                  name="descricao"><?php echo htmlspecialchars($fornecedor['descricao'] ?? ''); ?></textarea>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary">Salvar Alterações</button>
              </div>
            </form>
          </div>

          <div class="profile-section">
            <h2>Alterar Senha</h2>

            <form method="POST">
              <input type="hidden" name="action" value="change_password">

              <div class="form-group">
                <label for="senha_atual">Senha Atual *</label>
                <input type="password" id="senha_atual" name="senha_atual" required>
              </div>

              <div class="form-group">
                <label for="senha_nova">Nova Senha *</label>
                <input type="password" id="senha_nova" name="senha_nova" required placeholder="Mínimo 6 caracteres">
              </div>

              <div class="form-group">
                <label for="confirmar_senha">Confirmar Nova Senha *</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary">Alterar Senha</button>
              </div>
            </form>
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
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>

  <!-- Script para preview de foto de perfil -->
  <script>
    function previewProfilePhoto(event) {
      const preview = document.getElementById('profile-photo-preview');
      const file = event.target.files[0];
      
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="profile-photo">';
        }
        reader.readAsDataURL(file);
      }
    }
  </script>
</body>

</html>
