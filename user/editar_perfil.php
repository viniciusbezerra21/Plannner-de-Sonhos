<?php
session_start();

// Exemplo de dados do usuário (substituir pelo banco de dados)
$user = [
    "nome" => "Kauê Feltrin",
    "email" => "kaue@email.com",
    "telefone" => "(11) 99999-9999",
    "cidade" => "São Paulo - SP",
    "foto" => "" // vazio = sem foto
];

// Pega a primeira letra do nome para placeholder
$iniciais = strtoupper(substr($user['nome'], 0, 1));

// Se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $cidade = $_POST['cidade'];

    // Se foi enviada uma nova foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novoNome = "perfil_" . time() . "." . $ext;
        $destino = "uploads/" . $novoNome;

        if (!is_dir("uploads")) {
            mkdir("uploads", 0777, true);
        }

        move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
        $user['foto'] = $destino;
    }

    $_SESSION['user'] = [
        "nome" => $nome,
        "email" => $email,
        "telefone" => $telefone,
        "cidade" => $cidade,
        "foto" => $user['foto']
    ];

    header("Location: perfil.php?atualizado=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeddingEasy</title>
    <link rel="stylesheet" href="../Style/styles.css">
    <style>
        .edit-profile-card {
            max-width: 600px;
            margin: 6rem auto 3rem auto;
            padding: 2.5rem;
            background: white;
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            animation: slideInFromBottom 0.8s ease-out;
            text-align: center;
        }

        .edit-profile-card h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: hsl(var(--foreground));
        }

        .profile-photo {
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .profile-photo img,
        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            display: block;
            margin: 0 auto;
        }

        .profile-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            font-size: 3rem;
            font-weight: bold;
            user-select: none;
        }

        /* Botão customizado */
        .custom-file {
            margin-top: 1rem;
        }

        .custom-file input[type="file"] {
            display: none;
        }

        .custom-file label {
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
        }

        .custom-file label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .custom-file label:hover {
            animation: glow 0.5s ease-in-out;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            color: hsl(var(--foreground));
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: hsl(var(--primary));
            box-shadow: 0 0 0 3px hsl(var(--primary) / 0.2);
        }

        .btn-submit {
            display: block;
            width: 100%;
            margin-top: 1.5rem;
            padding: 0.75rem;
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            animation: glow 0.5s ease-in-out;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-content">
            <a href="index.php" class="logo">
                <div class="heart-icon">❤</div>
                <span class="logo-text">WeddingEasy</span>
            </a>
            <nav class="nav">
                <a href="perfil.php" class="nav-link">Voltar</a>
            </nav>
            <button class="mobile-menu-btn"
                onclick="document.querySelector('.mobile-menu').classList.toggle('active');this.classList.toggle('hamburger-active')">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
        <div class="mobile-menu">
            <a href="perfil.php" class="nav-link">Voltar</a>
        </div>
    </header>

    <!-- Formulário -->
    <main class="container">
        <div class="edit-profile-card">
            <h2>Editar Perfil</h2>
            <div class="profile-photo">
                <?php if (!empty($user['foto'])): ?>
                    <img id="fotoPreview" src="<?php echo $user['foto']; ?>" alt="Foto de perfil">
                <?php else: ?>
                    <div id="fotoPlaceholder" class="profile-placeholder"><?php echo $iniciais; ?></div>
                <?php endif; ?>
                <div class="custom-file">
                    <input type="file" id="foto" name="foto" accept="image/*" onchange="previewFoto(event)">
                    <label for="foto">Trocar Foto de Perfil</label>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" value="<?php echo $user['nome']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo $user['telefone']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?php echo $user['cidade']; ?>" required>
                </div>
                <button type="submit" class="btn-submit">Salvar Alterações</button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <a href="index.php" class="logo">
            <div class="heart-icon">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">WeddingEasy</span>
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
            <span>contato@weddingeasy.com</span>
          </div>
        </div>
        <div class="footer-links">
          <h3>Navegação</h3>
          <ul>
            <li><a href="index.php">Início</a></li>
            <li>
                <a href="pages/funcionalidades.php">Funcionalidades</a>
            </li>
            <li>
              <?php if (isset($_SESSION['usuario_logado'])): ?>
                <a href="pages/contato.php">Contato</a>
              <?php else: ?>
                <a href="#" onclick="openLoginModal()"></a>
              <?php endif; ?>
            </li>
          </ul>
        </div>
        <div class="footer-modules">
          <h3>Legal</h3>
          <ul>
            <li><a href="legal-pages/about.html">Sobre</a></li>
            <li>
              <a href="legal-pages/privacity-politics.html">Política de Privacidade</a>
            </li>
            <li><a href="legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 WeddingEasy. Todos os direitos reservados.</p>
        <div style="
              display: flex;
              align-items: center;
              gap: 0.25rem;
              font-size: 0.875rem;
              color: hsl(var(--muted-foreground));
            ">
          <span>Feito com</span>
          <svg style="
                width: 1rem;
                height: 1rem;
                color: hsl(var(--primary));
                margin: 0 0.25rem;
              " fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
          <span>para cerimonialistas</span>
        </div>
      </div>
    </div>
  </footer>

    <script>
        function previewFoto(event) {
            const file = event.target.files[0];
            if (file) {
                const foto = document.getElementById('fotoPreview');
                const placeholder = document.getElementById('fotoPlaceholder');
                if (placeholder) placeholder.style.display = 'none';

                if (foto) {
                    foto.src = URL.createObjectURL(file);
                } else {
                    const img = document.createElement('img');
                    img.id = 'fotoPreview';
                    img.src = URL.createObjectURL(file);
                    img.style.width = "120px";
                    img.style.height = "120px";
                    img.style.borderRadius = "50%";
                    img.style.objectFit = "cover";
                    img.style.boxShadow = "0 6px 15px rgba(0,0,0,0.15)";
                    document.querySelector('.profile-photo').prepend(img);
                }
            }
        }
    </script>
</body>

</html>