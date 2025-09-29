<?php
session_start();
require_once "../config/conexao.php";

$cookieName = "lembrar_me";

/* --- Restaurar sessão a partir do cookie (seguro: valida no DB) --- */
if (!isset($_SESSION['id_usuario']) && isset($_COOKIE[$cookieName])) {
  $cookieUserId = (int) $_COOKIE[$cookieName];
  if ($cookieUserId > 0) {
    $chk = $pdo->prepare("SELECT id_usuario, nome, cargo FROM usuarios WHERE id_usuario = ?");
    $chk->execute([$cookieUserId]);
    $u = $chk->fetch(PDO::FETCH_ASSOC);
    if ($u) {
      $_SESSION['id_usuario'] = (int)$u['id_usuario'];
      $_SESSION['nome'] = $u['nome'];
      $_SESSION['cargo'] = $u['cargo'] ?? 'cliente';
    } else {
      // cookie inválido -> remover
      setcookie($cookieName, "", time() - 3600, "/");
    }
  }
}

/* --- Verifica login --- */
if (!isset($_SESSION['id_usuario'])) {
  header("Location: ../user/login.php");
  exit;
}
$idUsuario = (int) $_SESSION['id_usuario'];

/* ------------------------
   ⭐ SALVAR AVALIAÇÃO
-------------------------*/
if (isset($_POST['save_rating'])) {
  $idOrc = (int) $_POST['id_orcamento'];
  $rating = (int) $_POST['rating'];
  
  if ($rating >= 1 && $rating <= 5) {
    $stmt = $pdo->prepare("UPDATE orcamentos SET avaliacao = ? WHERE id_orcamento = ? AND id_usuario = ?");
    $stmt->execute([$rating, $idOrc, $idUsuario]);
  }
  
  header("Location: orcamento.php");
  exit;
}

/* ------------------------
   ➕ ADICIONAR ITEM
-------------------------*/
if (isset($_POST['add_item'])) {
  $item = trim($_POST['item'] ?? '');
  $fornecedor = trim($_POST['fornecedor'] ?? '');
  $quantidade = (int) ($_POST['quantidade'] ?? 0);
  // aceitar vírgula decimal no input
  $valor_unitario = (float) str_replace(',', '.', ($_POST['valor_unitario'] ?? 0));

  if ($item !== "" && $valor_unitario > 0) {
    $stmt = $pdo->prepare("INSERT INTO orcamentos (id_usuario, item, fornecedor, quantidade, valor_unitario, avaliacao) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->execute([$idUsuario, $item, $fornecedor, $quantidade, $valor_unitario]);
  }
  header("Location: orcamento.php");
  exit;
}

/* ------------------------
   🗑️ EXCLUIR ITEM
-------------------------*/
if (isset($_POST['delete_item'])) {
  $idOrc = (int) $_POST['delete_item'];
  $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_orcamento = ? AND id_usuario = ?");
  $stmt->execute([$idOrc, $idUsuario]);
  header("Location: orcamento.php");
  exit;
}

/* ------------------------
   📋 LISTAR ITENS
-------------------------*/
$stmt = $pdo->prepare("SELECT * FROM orcamentos WHERE id_usuario = ? ORDER BY id_orcamento DESC");
$stmt->execute([$idUsuario]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WeddingEasy</title>
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

    /* Enhanced star rating styles */
    .estrela-rating {
      display: flex;
      gap: 2px;
      align-items: center;
    }

    .estrela-icon {
      width: 18px;
      height: 18px;
      fill: #ddd;
      stroke: #ddd;
      stroke-width: 1;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }

    /* Fixed star states to properly show filled stars */
    .estrela-icon.active {
      fill: #ffc107 !important;
      stroke: #ffc107 !important;
    }

    .estrela-icon.hover {
      fill: #ffb300 !important;
      stroke: #ffb300 !important;
    }

    .estrela-icon.success-feedback {
      fill:rgb(213, 114, 216) !important;
      stroke:rgb(213, 114, 216) !important;
    }

    .rating-form {
      display: inline-block;
    }
  </style>
</head>

<body>
  <div class="create-event-modal" id="janela-modal-orcamentos">
    <div class="modal-content">
      <div class="card" style="
      background: linear-gradient(
        135deg,
        var(--wedding-rose-white) 0%,
        rgba(225, 190, 231, 0.2) 50%,
        rgba(186, 104, 200, 0.3) 100%
      );
    ">
        <h2 style="margin-bottom: 1rem">Adicionar Item ao Orçamento</h2>
        <form method="post" action="orcamento.php">
          <table style="width: 100%; border-collapse: collapse">
            <tbody>
              <!-- Linha de inputs -->
              <tr>
                <td style="padding: 0.5rem">
                  <div class="form-group">
                    <label for="item">Item:</label>
                    <input type="text" name="item" id="item" placeholder="Nome do item" style="width: 100%" required>
                  </div>
                </td>
                <td style="padding: 0.5rem">
                  <div class="form-group">
                    <label for="fornecedor">Fornecedor:</label>
                    <input type="text" name="fornecedor" id="fornecedor" placeholder="Nome do fornecedor" style="width: 100%">
                  </div>
                </td>
                <td style="padding: 0.5rem">
                  <div class="form-group">
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" name="quantidade" id="quantidade" placeholder="Qtd" style="width: 100%" min="1" value="1" required>
                  </div>
                </td>
                <td style="padding: 0.5rem">
                  <div class="form-group">
                    <label for="valor_unitario">Valor Unitário:</label>
                    <input type="number" name="valor_unitario" id="valor_unitario" placeholder="0,00" style="width: 100%" step="0.01" min="0.01" required>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div style="margin-top: 1rem; display: flex; gap: 1rem;">
            <button type="button" id="sair" class="btn-outline">Cancelar</button>
            <button type="submit" name="add_item" class="btn-primary">Adicionar Item</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="../index.php" class="logo">
          <span class="logo-text">WeddingEasy</span>
        </a>

        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <div class="dropdown">
            <a href="funcionalidades.html" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
            <div class="dropdown-menu">
              <a href="calendario.html">Calendário</a>
              <a href="orcamento.html">Orçamento</a>
              <a href="gestao-contratos.html">Gestão de Contratos</a>
              <a href="tarefas.php">Lista de Tarefas</a>
            </div>
          </div>
          <a href="contato.html" class="nav-link">Contato</a>

          <?php if (isset($_SESSION["id_usuario"])): ?>
            <div class="dropdown">
              <img src="../user/fotos/<?php echo $_SESSION['foto_perfil']; ?>" alt="Foto de perfil" class="user-avatar" />
              <div class="dropdown-menu" id="profileDropdown">
                <a href="../user/perfil.php">Meu Perfil</a>
                <form method="post" style="margin: 0">
                  <button type="submit" name="logout" style="all: unset; cursor: pointer">Sair</button>
                </form>
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
                                   viewBox="0 0 24 24">
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
          <button class="btn-primary" id="abrirModal">Adicionar Item</button>

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
            <span class="logo-text">WeddingEasy</span>
          </a>
          <p class="footer-description">
            A plataforma mais completa para cerimonialistas organizarem casamentos perfeitos. Simplifique sua gestão e encante seus clientes.
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
          <h3>Links Rápidos</h3>
          <ul>
            <li><a href="../legal-pages/about.html">Sobre</a></li>
            <li><a href="../legal-pages/privacity-politics.html">Política de Privacidade</a></li>
            <li><a href="../legal-pages/uses-terms.html">Termos de Uso</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 WeddingEasy. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>
  <script>
    function toggleMobileMenu() {
      const mobileMenu = document.getElementById("mobileMenu");
      const hamburgerBtn = document.getElementById("hamburgerBtn");
      mobileMenu.classList.toggle("active");
      hamburgerBtn.classList.toggle("hamburger-active");
    }

    function toggleProfileDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.classList.toggle("active");
    }

    // Fechar dropdown quando clicar fora
    document.addEventListener('click', function(event) {
      const profile = document.querySelector('.user-profile');
      const dropdown = document.getElementById("profileDropdown");
      if (profile && !profile.contains(event.target)) {
        dropdown?.classList.remove("active");
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
          mobileMenu.classList.remove("active");
          hamburgerBtn.classList.remove("hamburger-active");
        }
      });
    });

    document.getElementById('abrirModal').addEventListener('click', function() {
      document.getElementById('janela-modal-orcamentos').style.display = 'block';
    });

    document.getElementById('sair').addEventListener('click', function() {
      document.getElementById('janela-modal-orcamentos').style.display = 'none';
      document.querySelector('#janela-modal-orcamentos form').reset();
    });

    document.addEventListener('DOMContentLoaded', function() {
      console.log('[v0] Initializing star rating system');
      
      document.querySelectorAll('.estrela-rating').forEach(rating => {
        const stars = rating.querySelectorAll('.estrela-icon');
        const form = rating.closest('.rating-form');
        const ratingInput = form.querySelector('input[name="rating"]');
        const currentRating = parseInt(ratingInput.value) || 0;
        
        console.log('[v0] Setting up rating for item with current rating:', currentRating);
        
        // Initialize visual state
        updateStarDisplay(stars, currentRating);
        
        stars.forEach((star, index) => {
          const starRating = parseInt(star.dataset.rating);
          
          star.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('[v0] Star clicked, rating:', starRating);
            
            ratingInput.value = starRating;
            updateStarDisplay(stars, starRating);
            
            // Submit form via AJAX
            const formData = new FormData(form);
            formData.append('save_rating', '1');
            
            console.log('[v0] Submitting rating via AJAX');
            
            fetch('orcamento.php', {
              method: 'POST',
              body: formData
            })
            .then(response => {
              if (response.ok) {
                console.log('[v0] Rating saved successfully');
                // Show brief success feedback
                stars.forEach(s => {
                  s.classList.add('success-feedback');
                });
                setTimeout(() => {
                  stars.forEach(s => s.classList.remove('success-feedback'));
                  updateStarDisplay(stars, starRating);
                }, 500);
              } else {
                console.error('[v0] Error saving rating');
              }
            })
            .catch(error => {
              console.error('[v0] Network error:', error);
            });
          });
          
          // Hover effects
          star.addEventListener('mouseenter', function() {
            updateStarDisplay(stars, starRating, true);
          });
        });
        
        // Reset hover effect
        rating.addEventListener('mouseleave', function() {
          updateStarDisplay(stars, parseInt(ratingInput.value) || 0);
        });
      });
      
      function updateStarDisplay(stars, rating, isHover = false) {
        stars.forEach((star, index) => {
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
  <script src="../js/orcamento.js"></script>
</body>

</html>
