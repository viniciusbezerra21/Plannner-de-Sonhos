<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";


if (!isset($_SESSION['usuario_id']) && isset($_COOKIE[$cookieName])) {
  $cookieUserId = (int) $_COOKIE[$cookieName];
  if ($cookieUserId > 0) {
    $chk = $pdo->prepare("SELECT id_usuario, nome, cargo FROM usuarios WHERE id_usuario = ?");
    $chk->execute([$cookieUserId]);
    $u = $chk->fetch(PDO::FETCH_ASSOC);
    if ($u) {
      $_SESSION['usuario_id'] = (int)$u['id_usuario'];
      $_SESSION['nome'] = $u['nome'];
      $_SESSION['cargo'] = $u['cargo'] ?? 'cliente';
    } else {
    
      setcookie($cookieName, "", time() - 3600, "/");
    }
  }
}

$user_data = ['nome' => 'Usuário', 'email' => '', 'foto_perfil' => 'default.png'];


if (isset($_SESSION['usuario_id'])) {
  try {
    $stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([(int)$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
      $user_data = [
        'nome' => $result['nome'] ?? 'Usuário',
        'email' => $result['email'] ?? '',
        'foto_perfil' => !empty($result['foto_perfil']) ? $result['foto_perfil'] : 'default.png'
      ];
    
      if (!empty($result['foto_perfil'])) {
        $_SESSION['foto_perfil'] = $result['foto_perfil'];
      } else {
        $_SESSION['foto_perfil'] = 'default.png';
      }
    }
  } catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
  }
}

if (isset($_POST['logout'])) {
  $cookieName = "lembrar_me";
  try {
    if (isset($_SESSION['usuario_id'])) {
      $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id_usuario = ?");
      $stmt->execute([$_SESSION['usuario_id']]);
    }
  } catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
  }
  
  setcookie($cookieName, "", time() - 3600, "/");
  session_unset();
  session_destroy();
  header("Location: ../index.php");
  exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];


if (isset($_POST['save_rating'])) {
  $idOrc = (int) $_POST['id_orcamento'];
  $rating = (int) $_POST['rating'];
  
  if ($rating >= 1 && $rating <= 5) {
    $stmt = $pdo->prepare("UPDATE orcamentos SET avaliacao = ? WHERE id_orcamento = ? AND id_usuario = ?");
    $stmt->execute([$rating, $idOrc, $idUsuario]);
  }
  
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
  }
  
  header("Location: orcamento.php");
  exit;
}


if (isset($_POST['add_item'])) {
  $item = trim($_POST['item'] ?? '');
  $fornecedor = trim($_POST['fornecedor'] ?? '');
  $quantidade = (int) ($_POST['quantidade'] ?? 0);
 
  $valor_unitario = (float) str_replace(',', '.', ($_POST['valor_unitario'] ?? 0));

  if ($item !== "" && $valor_unitario > 0) {
    $stmt = $pdo->prepare("INSERT INTO orcamentos (id_usuario, item, fornecedor, quantidade, valor_unitario, avaliacao) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->execute([$idUsuario, $item, $fornecedor, $quantidade, $valor_unitario]);
  }
  header("Location: orcamento.php");
  exit;
}


if (isset($_POST['delete_item'])) {
  $idOrc = (int) $_POST['delete_item'];
  $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_orcamento = ? AND id_usuario = ?");
  $stmt->execute([$idOrc, $idUsuario]);
  header("Location: orcamento.php");
  exit;
}


$stmt = $pdo->prepare("SELECT * FROM orcamentos WHERE id_usuario = ? ORDER BY id_orcamento DESC");
$stmt->execute([$idUsuario]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <style>
    .create-event-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 5% auto;
      padding: 1rem;
      border-radius: 8px;
      max-width: 800px;
      width: 90%;
      position: relative;
    }

    .fechar {
      color: #aaa;
      position: absolute;
      top: 0.5rem;
      right: 1rem;
      font-size: 2rem;
      font-weight: bold;
      cursor: pointer;
    }

    .fechar:hover {
      color: #000;
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      position: relative;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid hsl(var(--primary));
      cursor: pointer;
    }

    .user-avatar-default {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary)) 80%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      border: 2px solid hsl(var(--primary));
    }

    .profile-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      min-width: 200px;
      display: none;
      z-index: 1000;
      margin-top: 0.5rem;
    }

    .profile-dropdown.active {
      display: block;
    }

    .profile-dropdown-header {
      padding: 1rem;
      border-bottom: 1px solid hsl(var(--border));
    }

    .profile-dropdown-name {
      font-weight: 600;
      color: hsl(var(--foreground));
      margin: 0;
      font-size: 0.9rem;
    }

    .profile-dropdown-email {
      color: hsl(var(--muted-foreground));
      margin: 0;
      font-size: 0.8rem;
      margin-top: 0.25rem;
    }

    .profile-dropdown-menu {
      padding: 0.5rem 0;
    }

    .profile-dropdown-item {
      display: block;
      padding: 0.75rem 1rem;
      color: hsl(var(--foreground));
      text-decoration: none;
      transition: background-color 0.2s;
      font-size: 0.9rem;
    }

    .profile-dropdown-item:hover {
      background-color: hsl(var(--accent));
    }

    .profile-dropdown-item.logout {
      color: #ef4444;
      border-top: 1px solid hsl(var(--border));
      margin-top: 0.5rem;
    }

    .profile-dropdown-item.logout:hover {
      background-color: #fef2f2;
    }

    @media (max-width: 768px) {
      .user-profile {
        order: -1;
      }

      .profile-dropdown {
        right: -1rem;
      }
    }

    
    .estrela-rating {
      display: flex;
      gap: 4px; 
      align-items: center;
    }

    .estrela-icon {
      width: 20px; 
      height: 20px; 
      fill: #ddd;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }

    
    .estrela-icon.active {
      fill: #ffc107;
    }

    .estrela-icon.hover {
      fill: #ffb300;
    }

    .estrela-icon.success-feedback {
      fill: #d572d8; 
      transform: scale(1.2);
    }

    .rating-form {
      display: inline-block;
    } 
    .profile-dropdown-wrapper {
      position: relative;
    }

    .profile-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.3s ease;
      object-fit: cover;
    }

    .profile-avatar:hover {
      border-color: hsl(var(--primary));
      transform: scale(1.05);
    }

    .profile-dropdown {
      position: absolute;
      top: calc(100% + 0.5rem);
      right: 0;
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      min-width: 280px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow: hidden;
    }

    .profile-dropdown.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .profile-dropdown-header {
      padding: 1.25rem;
      border-bottom: 1px solid hsl(var(--border));
      background: linear-gradient(135deg, hsl(var(--primary) / 0.05), hsl(var(--secondary) / 0.05));
    }

    .profile-dropdown-user {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .profile-dropdown-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid hsl(var(--primary));
    }

    .profile-dropdown-info {
      flex: 1;
      min-width: 0;
    }

    .profile-dropdown-name {
      font-weight: 600;
      font-size: 0.95rem;
      color: hsl(var(--foreground));
      margin-bottom: 0.125rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .profile-dropdown-email {
      font-size: 0.8rem;
      color: hsl(var(--muted-foreground));
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .profile-dropdown-menu {
      padding: 0.5rem;
    }

    .profile-dropdown-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      color: hsl(var(--foreground));
      text-decoration: none;
      border-radius: 0.5rem;
      transition: all 0.2s ease;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .profile-dropdown-item:hover {
      background: hsl(var(--muted));
      transform: translateX(4px);
    }

    .profile-dropdown-item svg {
      width: 18px;
      height: 18px;
      stroke-width: 2;
    }

    .profile-dropdown-item.logout {
      color: hsl(var(--destructive));
      border-top: 1px solid hsl(var(--border));
      margin-top: 0.5rem;
      padding-top: 1rem;
    }

    .profile-dropdown-item.logout:hover {
      background: hsl(var(--destructive) / 0.1);
    }

    .profile-dropdown-item.logout svg {
      stroke: hsl(var(--destructive));
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
          <div class="dropdown">
            <a href="funcionalidades.php" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
            <div class="dropdown-menu">
              <a href="calendario.php">Calendário</a>
              <a href="orcamento.php">Orçamento</a>
              <a href="fornecedores.php">Fornecedores</a>
              <a href="gestao-contratos.php">Gestão de Contratos</a>
              <a href="tarefas.php">Lista de Tarefas</a>
            </div>
          </div>
          <a href="contato.php" class="nav-link">Contato</a>

          <?php if (isset($_SESSION["usuario_id"])): ?>
            <div class="profile-dropdown-wrapper">
              <img 
                src="../user/fotos/<?php echo htmlspecialchars($user_data['foto_perfil'] ?? 'default.png'); ?>"
                alt="Foto de perfil"
                class="profile-avatar"
                onclick="toggleProfileDropdown()"
              >
              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                  <div class="profile-dropdown-user">
                    <img 
                      src="../user/fotos/<?php echo htmlspecialchars($user_data['foto_perfil'] ?? 'default.png'); ?>" 
                      alt="Avatar" 
                      class="profile-dropdown-avatar"
                    >
                    <div class="profile-dropdown-info">
                      
                      <div class="profile-dropdown-name">
                        <?php echo htmlspecialchars($user_data['nome']); ?>
                      </div>
                      <div class="profile-dropdown-email">
                        <?php echo htmlspecialchars($user_data['email']); ?>
                      </div>
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
                  <a href="funcionalidades.php" class="profile-dropdown-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <rect x="3" y="4" width="7" height="7"></rect>
                      <rect x="14" y="3" width="7" height="7"></rect>
                      <rect x="14" y="14" width="7" height="7"></rect>
                      <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Funcionalidades
                  </a>
                  <form method="post" style="margin:0;">
                   
                    <button type="submit" name="logout" class="profile-dropdown-item"> 
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                      </svg>
                      Sair
                    </button>
                  </form>
                </div>
              </div>
            </div>
               
          <?php else: ?>
            <a href="../user/login.php" class="btn-primary" style="align-items: center">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
  <main>
    <section class="page-content">
      <div class="container">
        <div class="page-header">
          <h1 class="page-title">
            Gerencie seu <span class="gradient-text">Orçamento</span>
          </h1>
          <p class="page-description">
            Organize e visualize todos os custos e fornecedores do seu evento.
          </p>
        </div>

        <div class="card" style="background: linear-gradient(135deg, var(--wedding-rose-white) 0%, rgba(225, 190, 231, 0.2) 50%, rgba(186, 104, 200, 0.3) 100%);">
          <h2 style="margin-bottom: 1rem">Itens do Orçamento</h2>

          <div class="tabela-wraper" style="max-height: none; overflow-y: visible;">
            <input type="text" id="pesquisarItem" placeholder="Pesquisar item..." style="display: none; margin-bottom: 0.5rem; padding: 0.5rem; width: 100%;">

            <table style="width: 100%; border-collapse: collapse">
              <thead>
                <tr style="text-align: left; border-bottom: 1px solid hsl(var(--border));">
                  <th style="padding: 0.75rem; cursor: default">Item</th>
                  <th style="padding: 0.75rem; cursor: default">Fornecedor</th>
                  <th style="padding: 0.75rem; cursor: default">Avaliação</th>
                  <th style="padding: 0.75rem; cursor: default">Quantidade</th>
                  <th style="padding: 0.75rem; cursor: default">Valor Unitário</th>
                  <th style="padding: 0.75rem; cursor: default">Valor Total</th>
                  <th style="padding: 0.75rem; cursor: default">Ações</th>
                </tr>
              </thead>
              <tbody id="tabelaPrincipal">
                <?php if (empty($itens)): ?>
                  <tr>
                    <td colspan="7" style="text-align:center; padding:1rem;">Nenhum item no orçamento.</td>
                  </tr>
                <?php else: ?>
                  <?php
                  $total = 0;
                  foreach ($itens as $i):
                    $valorTotal = $i['quantidade'] * $i['valor_unitario'];
                    $total += $valorTotal;
                  ?>
                    <tr>
                      <td style="padding: 0.75rem"><?= htmlspecialchars($i['item']) ?></td>
                      <td style="padding: 0.75rem"><?= htmlspecialchars($i['fornecedor']) ?></td>
                      <td style="padding: 0.75rem">
                        <form method="post" class="rating-form" data-id="<?= $i['id_orcamento'] ?>">
                          <input type="hidden" name="id_orcamento" value="<?= $i['id_orcamento'] ?>">
                          <input type="hidden" name="rating" value="<?= $i['avaliacao'] ?? 0 ?>">
                          <div class="estrela-rating">
                            <?php for($star = 1; $star <= 5; $star++): ?>
                              <svg class="estrela-icon <?= ($i['avaliacao'] >= $star) ? 'active' : '' ?>" 
                                   data-rating="<?= $star ?>" 
                                   viewBox="0 0 24 24"
                                   xmlns="http://www.w3.org/2000/svg"> 
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                              </svg>
                            <?php endfor; ?>
                          </div>
                        </form>
                      </td>
                      <td style="padding: 0.75rem"><?= $i['quantidade'] ?></td>
                      <td style="padding: 0.75rem">R$ <?= number_format($i['valor_unitario'], 2, ',', '.') ?></td>
                      <td style="padding: 0.75rem">R$ <?= number_format($valorTotal, 2, ',', '.') ?></td>
                      <td style="padding: 0.75rem">
                        <form method="post" style="display:inline">
                          <button type="submit" name="delete_item" value="<?= $i['id_orcamento'] ?>" class="btn-outline" onclick="return confirm('Tem certeza que deseja excluir este item?')">Excluir</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
              <tfoot>
                <tr style="font-weight: bold; border-top: 2px solid hsl(var(--border));">
                  <td colspan="5" style="text-align:right; padding: 0.75rem;">Total Geral:</td>
                  <td style="padding: 0.75rem;">R$ <?= isset($total) ? number_format($total, 2, ',', '.') : "0,00" ?></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <a href="fornecedores.php" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
            Ver Fornecedores
          </a>

          <div class="card" style="margin-top: 2rem">
            <h2 style="margin-bottom: 1rem">Observações</h2>
            <p style="color: hsl(var(--muted-foreground))">
              Os valores apresentados são estimativas e podem sofrer alterações conforme negociações com fornecedores.
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
          <p class="footer-description">
            A plataforma mais completa para cerimonialistas organizarem casamentos perfeitos. Simplifique sua gestão e encante seus clientes.
          </p>
          <div class="footer-contact">
            <svg style="width: 1rem; height: 1rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>contato@plannerdesonhos.com</span>
          </div>
        </div>
        <div class="footer-links">
          <h3>Links Rápidos</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li><a href="../legal-pages/privacity-politics.html">Política de Privacidade</a></li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2025 Planner de Sonhos. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>
  <script>
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");
      if (mobileMenu && hamburgerBtn) { 
        mobileMenu.classList.toggle("active");
        hamburgerBtn.classList.toggle("hamburger-active");
      }
    }

    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      if (dropdown) { 
        dropdown.classList.toggle("active");
      }
    }

   
    document.addEventListener('click', function(event) {
      const profileWrapper = document.querySelector('.profile-dropdown-wrapper');
      const dropdown = document.getElementById("profileDropdown");
      if (profileWrapper && dropdown && !profileWrapper.contains(event.target)) { 
        dropdown.classList.remove("active");
      }
    });
    
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
          const mobileMenu = document.getElementById("mobileMenu");
          const hamburgerBtn = document.getElementById("hamburgerBtn");
          if (mobileMenu && hamburgerBtn) { 
            mobileMenu.classList.remove("active");
            hamburgerBtn.classList.remove("hamburger-active");
          }
        }
      });
    });

    document.addEventListener('DOMContentLoaded', function() {
      console.log('[v0] Initializing star rating system');
      
      document.querySelectorAll('.estrela-rating').forEach(rating => {
        const stars = rating.querySelectorAll('.estrela-icon');
        const form = rating.closest('.rating-form');
        const ratingInput = form.querySelector('input[name="rating"]');
        const currentRating = parseInt(ratingInput.value) || 0;
        
        console.log('[v0] Setting up rating for item with current rating:', currentRating);
        
      
        updateStarDisplay(stars, currentRating);
        
        stars.forEach((star) => {
          const starRating = parseInt(star.dataset.rating);
          
          star.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); 
            console.log('[v0] Star clicked, rating:', starRating);
            
            ratingInput.value = starRating;
            updateStarDisplay(stars, starRating);
            
            
            const formData = new FormData(form);
            formData.append('save_rating', '1');
            
            console.log('[v0] Submitting rating via AJAX');
            
            fetch('orcamento.php', {
              method: 'POST',
              body: formData,
              headers: {
                'X-Requested-With': 'XMLHttpRequest' 
              }
            })
            .then(response => {
              if (response.ok) {
                console.log('[v0] Rating saved successfully');
               
                stars.forEach(s => {
                  s.classList.add('success-feedback');
                });
                setTimeout(() => {
                  stars.forEach(s => s.classList.remove('success-feedback'));
                  updateStarDisplay(stars, starRating);
                }, 400);
              } else {
                console.error('[v0] Error saving rating');
              }
            })
            .catch(error => {
              console.error('[v0] Network error:', error);
            });
          });
          
          
          star.addEventListener('mouseenter', function() {
            updateStarDisplay(stars, starRating, true);
          });
        });
        
      
        rating.addEventListener('mouseleave', function() {
          updateStarDisplay(stars, parseInt(ratingInput.value) || 0);
        });
      });
      
      function updateStarDisplay(stars, rating, isHover = false) {
        stars.forEach((star) => {
          const starValue = parseInt(star.dataset.rating);
          star.classList.remove('active', 'hover');
          
          if (starValue <= rating) {
            if (isHover) {
              star.classList.add('hover');
            } else {
              star.classList.add('active');
            }
          }
        });
      }
    });
  </script>
</body>

</html>
