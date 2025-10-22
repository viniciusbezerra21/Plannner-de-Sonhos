<?php
session_start();
require_once "../config/conexao.php";

// Verificar se o fornecedor está logado
if (!isset($_SESSION['fornecedor_id'])) {
    header("Location: login.php");
    exit;
}

$fornecedor_id = (int)$_SESSION['fornecedor_id'];
$mensagem = "";
$tipo_mensagem = "";

// Handle add/edit item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nome_item = trim($_POST['nome_item'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_unitario = floatval($_POST['valor_unitario'] ?? 0);
    $id_item = isset($_POST['id_item']) ? (int)$_POST['id_item'] : null;

    if (empty($nome_item) || $valor_unitario <= 0) {
        $mensagem = 'Por favor, preencha todos os campos corretamente.';
        $tipo_mensagem = 'erro';
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM itens LIKE 'descricao'");
                $stmt->execute();
                $has_descricao = $stmt->rowCount() > 0;

                if ($has_descricao) {
                    $stmt = $pdo->prepare("
                        INSERT INTO itens (id_fornecedor, nome_item, descricao, valor_unitario, data_criacao)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$fornecedor_id, $nome_item, $descricao, $valor_unitario]);
                } else {
                    // Fallback if columns don't exist yet
                    $stmt = $pdo->prepare("
                        INSERT INTO itens (id_fornecedor, nome_item, valor_unitario)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$fornecedor_id, $nome_item, $valor_unitario]);
                    $mensagem = 'Item adicionado! Nota: Execute o script SQL para adicionar suporte a descrições.';
                    $tipo_mensagem = 'sucesso';
                }
                if ($has_descricao) {
                    $mensagem = 'Item adicionado com sucesso!';
                    $tipo_mensagem = 'sucesso';
                }
            } elseif ($action === 'edit' && $id_item) {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM itens LIKE 'descricao'");
                $stmt->execute();
                $has_descricao = $stmt->rowCount() > 0;

                if ($has_descricao) {
                    $stmt = $pdo->prepare("
                        UPDATE itens SET nome_item = ?, descricao = ?, valor_unitario = ?
                        WHERE id_item = ? AND id_fornecedor = ?
                    ");
                    $stmt->execute([$nome_item, $descricao, $valor_unitario, $id_item, $fornecedor_id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE itens SET nome_item = ?, valor_unitario = ?
                        WHERE id_item = ? AND id_fornecedor = ?
                    ");
                    $stmt->execute([$nome_item, $valor_unitario, $id_item, $fornecedor_id]);
                }
                $mensagem = 'Item atualizado com sucesso!';
                $tipo_mensagem = 'sucesso';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $mensagem = 'Erro: Tabela precisa ser atualizada. Execute o script SQL: scripts/create_items_and_packages_tables.sql';
            } else {
                $mensagem = 'Erro ao salvar item. Tente novamente.';
            }
            $tipo_mensagem = 'erro';
            error_log("Item error: " . $e->getMessage());
        }
    }
}

// Handle delete item
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id_item = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM itens WHERE id_item = ? AND id_fornecedor = ?");
        $stmt->execute([$id_item, $fornecedor_id]);
        $mensagem = 'Item deletado com sucesso!';
        $tipo_mensagem = 'sucesso';
    } catch (PDOException $e) {
        $mensagem = 'Erro ao deletar item.';
        $tipo_mensagem = 'erro';
    }
}

// Get all items
try {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM itens LIKE 'descricao'");
    $stmt->execute();
    $has_descricao = $stmt->rowCount() > 0;

    if ($has_descricao) {
        $stmt = $pdo->prepare("SELECT * FROM itens WHERE id_fornecedor = ? ORDER BY data_criacao DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM itens WHERE id_fornecedor = ? ORDER BY id_item DESC");
    }
    $stmt->execute([$fornecedor_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $itens = [];
    error_log("Items fetch error: " . $e->getMessage());
}

// Get item for editing
$item_edit = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $id_item = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM itens WHERE id_item = ? AND id_fornecedor = ?");
        $stmt->execute([$id_item, $fornecedor_id]);
        $item_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Item edit fetch error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Itens - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .items-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }

    .form-section {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
      height: fit-content;
      position: sticky;
      top: 100px;
    }

    .form-section h2 {
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
      min-height: 80px;
    }

    .form-group input:focus,
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

    .form-actions button,
    .form-actions a {
      flex: 1;
      padding: 0.75rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      text-align: center;
    }

    .items-list {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .items-list h2 {
      margin-top: 0;
      margin-bottom: 1.5rem;
      color: hsl(var(--foreground));
    }

    .item-card {
      background: hsl(var(--background));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.2s;
    }

    .item-card:hover {
      border-color: hsl(var(--primary));
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .item-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }

    .item-name {
      font-size: 1.1rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      margin: 0;
    }

    .item-price {
      font-size: 1.25rem;
      font-weight: 700;
      color: hsl(var(--primary));
    }

    .item-description {
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
      margin-bottom: 1rem;
      line-height: 1.5;
    }

    .item-actions {
      display: flex;
      gap: 0.5rem;
    }

    .item-actions a,
    .item-actions button {
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
    }

    .btn-edit {
      background: hsl(var(--primary) / 0.1);
      color: hsl(var(--primary));
    }

    .btn-edit:hover {
      background: hsl(var(--primary) / 0.2);
    }

    .btn-delete {
      background: hsl(var(--destructive) / 0.1);
      color: hsl(var(--destructive));
    }

    .btn-delete:hover {
      background: hsl(var(--destructive) / 0.2);
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: hsl(var(--muted-foreground));
    }

    .empty-state svg {
      width: 48px;
      height: 48px;
      margin-bottom: 1rem;
      opacity: 0.5;
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

    @media (max-width: 768px) {
      .items-container {
        grid-template-columns: 1fr;
      }

      .form-section {
        position: static;
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
        <a href="dashboard.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
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
          <h1 class="page-title">Gerenciar <span class="gradient-text">Itens/Serviços</span></h1>
          <p class="page-description">Adicione, edite ou remova seus itens e serviços.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
          <div class="message <?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
        <?php endif; ?>

        <div class="items-container">
          <!-- Form -->
          <div class="form-section">
            <h2><?php echo $item_edit ? 'Editar Item' : 'Adicionar Novo Item'; ?></h2>

            <form method="POST">
              <input type="hidden" name="action" value="<?php echo $item_edit ? 'edit' : 'add'; ?>">
              <?php if ($item_edit): ?>
                <input type="hidden" name="id_item" value="<?php echo $item_edit['id_item']; ?>">
              <?php endif; ?>

              <div class="form-group">
                <label for="nome_item">Nome do Item/Serviço *</label>
                <input type="text" id="nome_item" name="nome_item" value="<?php echo htmlspecialchars($item_edit['nome_item'] ?? ''); ?>" required placeholder="Ex: Decoração com Flores">
              </div>

              <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" placeholder="Descreva o item/serviço em detalhes..."><?php echo htmlspecialchars($item_edit['descricao'] ?? ''); ?></textarea>
              </div>

              <div class="form-group">
                <label for="valor_unitario">Valor Unitário (R$) *</label>
                <input type="number" id="valor_unitario" name="valor_unitario" value="<?php echo htmlspecialchars($item_edit['valor_unitario'] ?? ''); ?>" required step="0.01" min="0" placeholder="0.00">
              </div>

              <div class="form-actions">
                <?php if ($item_edit): ?>
                  <a href="items.php" class="btn-outline">Cancelar</a>
                <?php endif; ?>
                <button type="submit" class="btn-primary">
                  <?php echo $item_edit ? 'Atualizar Item' : 'Adicionar Item'; ?>
                </button>
              </div>
            </form>
          </div>

          <!-- Items List -->
          <div class="items-list">
            <h2>Seus Itens/Serviços</h2>

            <?php if (empty($itens)): ?>
              <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                </svg>
                <h3>Nenhum item cadastrado</h3>
                <p>Comece adicionando seus primeiros itens/serviços</p>
              </div>
            <?php else: ?>
              <?php foreach ($itens as $item): ?>
              <div class="item-card">
                <div class="item-header">
                  <h3 class="item-name"><?php echo htmlspecialchars($item['nome_item']); ?></h3>
                  <div class="item-price">R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></div>
                </div>

                <?php if (!empty($item['descricao'])): ?>
                  <p class="item-description"><?php echo htmlspecialchars($item['descricao']); ?></p>
                <?php endif; ?>

                <div class="item-actions">
                  <a href="items.php?edit=1&id=<?php echo $item['id_item']; ?>" class="btn-edit">Editar</a>
                  <a href="items.php?delete=1&id=<?php echo $item['id_item']; ?>" class="btn-delete" onclick="return confirm('Tem certeza que deseja deletar este item?');">Deletar</a>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
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
