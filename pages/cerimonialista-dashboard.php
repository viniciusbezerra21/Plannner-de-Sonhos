<?php
session_start();
require_once "../config/conexao.php";

if (isset($_POST['logout'])) {
    try {
        // Clear remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id_usuario = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
    
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Check if user is logged in and is a cerimonialista
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'cerimonialista') {
    header("Location: ../user/login-unified.php?type=cerimonialista");
    exit;
}

$id_cerimonialista = $_SESSION['usuario_id'];
$clientes = [];

try {
    $stmt = $pdo->prepare("SELECT cc.id_assoc, u.id_usuario, u.nome, u.nome_conjuge, cc.data_casamento, COUNT(o.id_orcamento) as total_itens FROM cliente_cerimonialista cc INNER JOIN usuarios u ON cc.id_cliente = u.id_usuario LEFT JOIN orcamentos o ON u.id_usuario = o.id_usuario WHERE cc.id_cerimonialista = ? AND cc.status = 'ativo' GROUP BY cc.id_assoc, u.id_usuario, u.nome, u.nome_conjuge, cc.data_casamento ORDER BY cc.data_casamento ASC");
    $stmt->execute([$id_cerimonialista]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard do Cerimonialista - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .dashboard-header h1 {
      font-size: 2rem;
      color: hsl(var(--foreground));
    }

    .clientes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }

    .cliente-card {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 1.5rem;
      transition: all 0.3s ease;
    }

    .cliente-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .cliente-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .cliente-name {
      font-size: 1.25rem;
      font-weight: 600;
      color: hsl(var(--foreground));
    }

    .cliente-date {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .cliente-couple {
      color: hsl(var(--muted-foreground));
      font-size: 0.95rem;
      margin-bottom: 1rem;
    }

    .cliente-stats {
      display: flex;
      gap: 1rem;
      padding: 1rem;
      background: hsl(var(--muted));
      border-radius: 0.5rem;
      margin-bottom: 1rem;
    }

    .stat {
      flex: 1;
      text-align: center;
    }

    .stat-number {
      font-size: 1.5rem;
      font-weight: 700;
      color: hsl(var(--primary));
    }

    .stat-label {
      font-size: 0.75rem;
      color: hsl(var(--muted-foreground));
    }

    .cliente-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn-view {
      flex: 1;
      padding: 0.75rem;
      background: hsl(var(--muted));
      color: hsl(var(--foreground));
      border: none;
      border-radius: 0.5rem;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .btn-view:hover {
      background: hsl(var(--primary) / 0.2);
    }

    @media (max-width: 768px) {
      .clientes-grid {
        grid-template-columns: 1fr;
      }

      .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="cerimonialista-home.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>
        <nav class="nav">
          <a href="cerimonialista-home.php" class="nav-link">Home</a>
          <a href="cerimonialista-dashboard.php" class="nav-link">Dashboard</a>
          <a href="cerimonialista-disponibilidade.php" class="nav-link">Disponibilidade</a>
          <a href="cerimonialista-fornecedores.php" class="nav-link">Fornecedores</a>

          <div class="profile-dropdown-wrapper">
            <img src="../user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>" alt="Foto de perfil" class="profile-avatar" onclick="toggleProfileDropdown()">
            <div class="profile-dropdown" id="profileDropdown">
              <div class="profile-dropdown-header">
                <div class="profile-dropdown-user">
                  <img src="../user/fotos/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>" alt="Avatar" class="profile-dropdown-avatar">
                  <div class="profile-dropdown-info">
                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></div>
                    <div class="profile-dropdown-email">Cerimonialista</div>
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
                <!-- Removed action attribute from logout form -->
                <form method="post" style="margin:0;">
                  <button type="submit" name="logout" class="profile-dropdown-item logout">
                    <svg fill="currentColor" width="18" height="18" viewBox="0 0 36 36">
                      <path d="M7,6H23v9.8h2V6a2,2,0,0,0-2-2H7A2,2,0,0,0,5,6V30a2,2,0,0,0,2,2H23a2,2,0,0,0,2-2H7Z" class="clr-i-outline"></path>
                      <path d="M28.16,17.28a1,1,0,0,0-1.41,1.41L30.13,22H15.63a1,1,0,0,0-1,1,1,1,0,0,0,1,1h14.5l-3.38,3.46a1,1,0,1,0,1.41,1.41L34,23.07Z" class="clr-i-outline"></path>
                    </svg>
                    Sair
                  </button>
                </form>
              </div>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content" style="padding-top: 6rem; min-height: 80vh">
      <div class="container">
        <div class="dashboard-header">
          <h1>Meus Clientes</h1>
          <span style="color: hsl(var(--muted-foreground));"><?php echo count($clientes); ?> casamentos</span>
        </div>

        <?php if (!empty($clientes)): ?>
          <div class="clientes-grid">
            <?php foreach ($clientes as $cliente): ?>
              <div class="cliente-card">
                <div class="cliente-header">
                  <div>
                    <div class="cliente-name"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                    <div class="cliente-couple">& <?php echo htmlspecialchars($cliente['nome_conjuge'] ?? ''); ?></div>
                  </div>
                  <div class="cliente-date">
                    <?php 
                    $data = new DateTime($cliente['data_casamento']);
                    echo $data->format('d/m/Y');
                    ?>
                  </div>
                </div>

                <div class="cliente-stats">
                  <div class="stat">
                    <div class="stat-number"><?php echo $cliente['total_itens']; ?></div>
                    <div class="stat-label">Itens</div>
                  </div>
                </div>

                <div class="cliente-actions">
                  <a href="cliente-detalhes.php?id=<?php echo $cliente['id_usuario']; ?>" class="btn-view">Ver Detalhes</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 4rem 2rem; background: hsl(var(--muted)); border-radius: 1rem;">
            <p style="color: hsl(var(--muted-foreground)); font-size: 1.1rem;">Você ainda não tem clientes atribuídos.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <a href="cerimonialista-home.php" class="logo">
            <div class="heart-icon">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
              </svg>
            </div>
            <span class="logo-text">Planner de Sonhos</span>
          </a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.classList.toggle("active");
    }

    document.addEventListener('click', function (event) {
      const dropdown = document.getElementById("profileDropdown");
      const wrapper = document.querySelector('.profile-dropdown-wrapper');

      if (dropdown && wrapper && !wrapper.contains(event.target)) {
        dropdown.classList.remove("active");
      }
    });
  </script>
</body>

</html>
