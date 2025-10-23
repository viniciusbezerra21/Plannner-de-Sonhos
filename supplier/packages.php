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

// Handle add/edit package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nome_pacote = trim($_POST['nome_pacote'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_total = floatval($_POST['valor_total'] ?? 0);
    $itens_selecionados = isset($_POST['itens']) ? $_POST['itens'] : [];
    $id_pacote = isset($_POST['id_pacote']) ? (int)$_POST['id_pacote'] : null;

    if (empty($nome_pacote) || $valor_total <= 0 || empty($itens_selecionados)) {
        $mensagem = 'Por favor, preencha todos os campos e selecione pelo menos um item.';
        $tipo_mensagem = 'erro';
    } else {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'pacotes'");
            $stmt->execute();
            $table_exists = $stmt->rowCount() > 0;

            if (!$table_exists) {
                $mensagem = 'Erro: Tabela de pacotes não existe. Execute o script SQL: scripts/create_items_and_packages_tables.sql';
                $tipo_mensagem = 'erro';
            } else {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO pacotes (id_fornecedor, nome_pacote, descricao, valor_total, quantidade_itens, data_criacao)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$fornecedor_id, $nome_pacote, $descricao, $valor_total, count($itens_selecionados)]);
                    $id_pacote = $pdo->lastInsertId();

                    // Add items to package
                    foreach ($itens_selecionados as $id_item) {
                        $stmt = $pdo->prepare("
                            INSERT INTO pacote_itens (id_pacote, id_item)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$id_pacote, (int)$id_item]);
                    }

                    $mensagem = 'Pacote criado com sucesso!';
                    $tipo_mensagem = 'sucesso';
                } elseif ($action === 'edit' && $id_pacote) {
                    $stmt = $pdo->prepare("
                        UPDATE pacotes SET nome_pacote = ?, descricao = ?, valor_total = ?, quantidade_itens = ?
                        WHERE id_pacote = ? AND id_fornecedor = ?
                    ");
                    $stmt->execute([$nome_pacote, $descricao, $valor_total, count($itens_selecionados), $id_pacote, $fornecedor_id]);

                    // Delete old items and add new ones
                    $stmt = $pdo->prepare("DELETE FROM pacote_itens WHERE id_pacote = ?");
                    $stmt->execute([$id_pacote]);

                    foreach ($itens_selecionados as $id_item) {
                        $stmt = $pdo->prepare("
                            INSERT INTO pacote_itens (id_pacote, id_item)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$id_pacote, (int)$id_item]);
                    }

                    $mensagem = 'Pacote atualizado com sucesso!';
                    $tipo_mensagem = 'sucesso';
                }
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
                $mensagem = 'Erro: Tabelas não existem. Execute o script SQL: scripts/create_items_and_packages_tables.sql';
            } else {
                $mensagem = 'Erro ao salvar pacote. Tente novamente.';
            }
            $tipo_mensagem = 'erro';
            error_log("Package error: " . $e->getMessage());
        }
    }
}

// Handle delete package
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id_pacote = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM pacote_itens WHERE id_pacote = ?");
        $stmt->execute([$id_pacote]);
        $stmt = $pdo->prepare("DELETE FROM pacotes WHERE id_pacote = ? AND id_fornecedor = ?");
        $stmt->execute([$id_pacote, $fornecedor_id]);
        $mensagem = 'Pacote deletado com sucesso!';
        $tipo_mensagem = 'sucesso';
    } catch (PDOException $e) {
        $mensagem = 'Erro ao deletar pacote.';
        $tipo_mensagem = 'erro';
    }
}

// Get all packages
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'pacotes'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;

    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM pacotes WHERE id_fornecedor = ? ORDER BY data_criacao DESC");
        $stmt->execute([$fornecedor_id]);
        $pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $pacotes = [];
    }
} catch (PDOException $e) {
    $pacotes = [];
    error_log("Packages fetch error: " . $e->getMessage());
}

// Get all items for selection
try {
    $stmt = $pdo->prepare("SELECT * FROM itens WHERE id_fornecedor = ? ORDER BY nome_item ASC");
    $stmt->execute([$fornecedor_id]);
    $todos_itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $todos_itens = [];
    error_log("Items fetch error: " . $e->getMessage());
}

// Get package for editing
$pacote_edit = null;
$itens_pacote = [];
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $id_pacote = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM pacotes WHERE id_pacote = ? AND id_fornecedor = ?");
        $stmt->execute([$id_pacote, $fornecedor_id]);
        $pacote_edit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pacote_edit) {
            $stmt = $pdo->prepare("SELECT id_item FROM pacote_itens WHERE id_pacote = ?");
            $stmt->execute([$id_pacote]);
            $itens_pacote = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        error_log("Package edit fetch error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Pacotes - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css">
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    .packages-container {
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
    .form-group textarea,
    .form-group select {
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
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: hsl(var(--primary));
      box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
    }

    .items-selection {
      background: hsl(var(--background));
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      padding: 1rem;
      max-height: 200px;
      overflow-y: auto;
    }

    .item-checkbox {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .item-checkbox input[type="checkbox"] {
      width: auto;
      margin: 0;
      cursor: pointer;
    }

    .item-checkbox label {
      margin: 0;
      cursor: pointer;
      flex: 1;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .item-price-small {
      color: hsl(var(--primary));
      font-weight: 600;
      font-size: 0.85rem;
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

    .packages-list {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .packages-list h2 {
      margin-top: 0;
      margin-bottom: 1.5rem;
      color: hsl(var(--foreground));
    }

    .package-card {
      background: hsl(var(--background));
      border: 1px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.2s;
    }

    .package-card:hover {
      border-color: hsl(var(--primary));
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .package-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }

    .package-name {
      font-size: 1.1rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      margin: 0;
    }

    .package-price {
      font-size: 1.25rem;
      font-weight: 700;
      color: hsl(var(--primary));
    }

    .package-description {
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
      margin-bottom: 1rem;
      line-height: 1.5;
    }

    .package-items {
      background: hsl(var(--muted) / 0.5);
      border-radius: 0.5rem;
      padding: 0.75rem;
      margin-bottom: 1rem;
      font-size: 0.85rem;
      color: hsl(var(--muted-foreground));
    }

    .package-actions {
      display: flex;
      gap: 0.5rem;
    }

    .package-actions a,
    .package-actions button {
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
      .packages-container {
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
          <h1 class="page-title">Gerenciar <span class="gradient-text">Pacotes</span></h1>
          <p class="page-description">Crie pacotes combinando seus itens e serviços.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
          <div class="message <?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
        <?php endif; ?>

        <div class="packages-container">
          <!-- Form -->
          <div class="form-section">
            <h2><?php echo $pacote_edit ? 'Editar Pacote' : 'Criar Novo Pacote'; ?></h2>

            <form method="POST">
              <input type="hidden" name="action" value="<?php echo $pacote_edit ? 'edit' : 'add'; ?>">
              <?php if ($pacote_edit): ?>
                <input type="hidden" name="id_pacote" value="<?php echo $pacote_edit['id_pacote']; ?>">
              <?php endif; ?>

              <div class="form-group">
                <label for="nome_pacote">Nome do Pacote *</label>
                <input type="text" id="nome_pacote" name="nome_pacote" value="<?php echo htmlspecialchars($pacote_edit['nome_pacote'] ?? ''); ?>" required placeholder="Ex: Pacote Completo Premium">
              </div>

              <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" placeholder="Descreva o pacote..."><?php echo htmlspecialchars($pacote_edit['descricao'] ?? ''); ?></textarea>
              </div>

              <div class="form-group">
                <label for="valor_total">Valor Total do Pacote (R$) *</label>
                <input type="number" id="valor_total" name="valor_total" value="<?php echo htmlspecialchars($pacote_edit['valor_total'] ?? ''); ?>" required step="0.01" min="0" placeholder="0.00">
              </div>

              <div class="form-group">
                <label>Selecione os Itens/Serviços *</label>
                <div class="items-selection">
                  <?php if (empty($todos_itens)): ?>
                    <p style="color: hsl(var(--muted-foreground)); text-align: center; margin: 0;">Nenhum item cadastrado. <a href="items.php">Adicione itens primeiro</a>.</p>
                  <?php else: ?>
                    <?php foreach ($todos_itens as $item): ?>
                    <div class="item-checkbox">
                      <input type="checkbox" id="item_<?php echo $item['id_item']; ?>" name="itens[]" value="<?php echo $item['id_item']; ?>" <?php echo in_array($item['id_item'], $itens_pacote) ? 'checked' : ''; ?>>
                      <label for="item_<?php echo $item['id_item']; ?>">
                        <span><?php echo htmlspecialchars($item['nome_item']); ?></span>
                        <span class="item-price-small">R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></span>
                      </label>
                    </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>

              <div class="form-actions">
                <?php if ($pacote_edit): ?>
                  <a href="packages.php" class="btn-outline">Cancelar</a>
                <?php endif; ?>
                <button type="submit" class="btn-primary">
                  <?php echo $pacote_edit ? 'Atualizar Pacote' : 'Criar Pacote'; ?>
                </button>
              </div>
            </form>
          </div>

          <!-- Packages List -->
          <div class="packages-list">
            <h2>Seus Pacotes</h2>

            <?php if (empty($pacotes)): ?>
              <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                <h3>Nenhum pacote cadastrado</h3>
                <p>Crie pacotes combinando seus itens e serviços</p>
              </div>
            <?php else: ?>
              <?php foreach ($pacotes as $pacote): ?>
              <div class="package-card">
                <div class="package-header">
                  <h3 class="package-name"><?php echo htmlspecialchars($pacote['nome_pacote']); ?></h3>
                  <div class="package-price">R$ <?php echo number_format($pacote['valor_total'], 2, ',', '.'); ?></div>
                </div>

                <?php if (!empty($pacote['descricao'])): ?>
                  <p class="package-description"><?php echo htmlspecialchars($pacote['descricao']); ?></p>
                <?php endif; ?>

                <div class="package-items">
                  <strong><?php echo $pacote['quantidade_itens']; ?></strong> item(ns) incluído(s)
                </div>

                <div class="package-actions">
                  <a href="packages.php?edit=1&id=<?php echo $pacote['id_pacote']; ?>" class="btn-edit">Editar</a>
                  <a href="packages.php?delete=1&id=<?php echo $pacote['id_pacote']; ?>" class="btn-delete" onclick="return confirm('Tem certeza que deseja deletar este pacote?');">Deletar</a>
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
