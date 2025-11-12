<?php
session_start();
require_once "../config/conexao.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'cliente') {
    header("Location: ../user/login-unified.php?type=cliente");
    exit;
}

$id_cliente = $_SESSION['usuario_id'];
$data_casamento = $_GET['data'] ?? null;
$cerimonialistas = [];

// Se tiver data no GET, buscar cerimonialistas disponíveis
if ($data_casamento) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.nome, u.avaliacao, u.bio, u.foto_perfil
            FROM usuarios u
            WHERE u.tipo_usuario = 'cerimonialista' AND u.plano = 'premium'
            AND u.id_usuario NOT IN (
                SELECT id_cerimonialista FROM cliente_cerimonialista 
                WHERE data_casamento = ? AND status = 'ativo'
            )
            ORDER BY u.avaliacao DESC
        ");
        $stmt->execute([$data_casamento]);
        $cerimonialistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching cerimonialistas: " . $e->getMessage());
    }
}

// If user selects a cerimonialista
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_cerimonialista'])) {
    $id_cerimonialista = (int)$_POST['id_cerimonialista'];
    $data_casamento = $_POST['data_casamento'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO cliente_cerimonialista (id_cliente, id_cerimonialista, data_casamento, status)
            VALUES (?, ?, ?, 'ativo')
        ");
        $stmt->execute([$id_cliente, $id_cerimonialista, $data_casamento]);

        $_SESSION['id_cerimonialista'] = $id_cerimonialista;
        $_SESSION['data_casamento'] = $data_casamento;

        header("Location: ../index.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error selecting cerimonialista: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Escolher Cerimonialista - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .escolher-container {
      max-width: 1000px;
      margin: auto;
      padding: 2rem 0;
    }

    .escolher-header {
      text-align: center;
      margin-bottom: 3rem;
    }

    .escolher-header h1 {
      font-size: 2rem;
      color: hsl(var(--foreground));
      margin-bottom: 0.5rem;
    }

    .date-picker {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .date-picker input {
      padding: 0.75rem 1rem;
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      font-size: 1rem;
    }

    .date-picker button {
      padding: 0.75rem 1.5rem;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      border: none;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
    }

    .cerimonialistas-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }

    .cerimonialista-card {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      overflow: hidden;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .cerimonialista-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border-color: hsl(var(--primary));
    }

    .cerimonialista-photo {
      width: 100%;
      height: 200px;
      object-fit: cover;
      background: hsl(var(--muted));
    }

    .cerimonialista-info {
      padding: 1.5rem;
    }

    .cerimonialista-name {
      font-size: 1.25rem;
      font-weight: 600;
      color: hsl(var(--foreground));
      margin-bottom: 0.5rem;
    }

    .cerimonialista-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }

    .stars {
      color: #fbbf24;
    }

    .cerimonialista-bio {
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
      line-height: 1.5;
      margin-bottom: 1rem;
    }

    .cerimonialista-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn-view-fornecedores {
      flex: 1;
      padding: 0.75rem;
      background: hsl(var(--muted));
      color: hsl(var(--foreground));
      border: none;
      border-radius: 0.5rem;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.2s;
    }

    .btn-view-fornecedores:hover {
      background: hsl(var(--primary) / 0.2);
    }

    .btn-select {
      flex: 1;
      padding: 0.75rem;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      border: none;
      border-radius: 0.5rem;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 600;
      transition: all 0.2s;
    }

    .btn-select:hover {
      transform: scale(1.05);
    }

    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: hsl(var(--card));
      border-radius: 1rem;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      padding: 2rem;
      position: relative;
    }

    .modal-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: none;
      border: none;
      font-size: 2rem;
      cursor: pointer;
      color: hsl(var(--foreground));
    }

    .fornecedores-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .fornecedor-item {
      display: flex;
      gap: 1rem;
      padding: 1rem;
      background: hsl(var(--muted));
      border-radius: 0.5rem;
      align-items: flex-start;
    }

    .fornecedor-item h4 {
      margin: 0 0 0.25rem 0;
      color: hsl(var(--foreground));
    }

    .fornecedor-item p {
      margin: 0;
      color: hsl(var(--muted-foreground));
      font-size: 0.9rem;
    }

    @media (max-width: 768px) {
      .cerimonialistas-grid {
        grid-template-columns: 1fr;
      }

      .date-picker {
        flex-direction: column;
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
      </div>
    </div>
  </header>

  <main>
    <section class="page-content" style="padding-top: 4rem">
      <div class="container">
        <div class="escolher-container">
          <div class="escolher-header">
            <h1>Escolha seu Cerimonialista</h1>
            <p style="color: hsl(var(--muted-foreground)); margin-bottom: 1.5rem;">Selecione a data do seu casamento para ver os cerimonialistas disponíveis</p>

            <div class="date-picker">
              <input type="date" id="dataPicker" value="<?php echo htmlspecialchars($data_casamento ?? ''); ?>" />
              <button onclick="buscarCerimonialistas()">Buscar</button>
            </div>
          </div>

          <?php if ($data_casamento && !empty($cerimonialistas)): ?>
            <div class="cerimonialistas-grid">
              <?php foreach ($cerimonialistas as $cerimo): ?>
                <div class="cerimonialista-card">
                  <img src="../user/fotos/<?php echo htmlspecialchars($cerimo['foto_perfil'] ?? 'default.png'); ?>" 
                       alt="<?php echo htmlspecialchars($cerimo['nome']); ?>" 
                       class="cerimonialista-photo">
                  
                  <div class="cerimonialista-info">
                    <div class="cerimonialista-name"><?php echo htmlspecialchars($cerimo['nome']); ?></div>
                    
                    <div class="cerimonialista-rating">
                      <span class="stars">★★★★★</span>
                      <span><?php echo number_format($cerimo['avaliacao'] ?? 0, 1); ?>/5</span>
                    </div>
                    
                    <div class="cerimonialista-bio">
                      <?php echo htmlspecialchars(substr($cerimo['bio'] ?? 'Cerimonialista profissional', 0, 100)); ?>...
                    </div>

                    <div class="cerimonialista-actions">
                      <button class="btn-view-fornecedores" onclick="abrirFornecedores(<?php echo $cerimo['id_usuario']; ?>)">
                        Ver Fornecedores
                      </button>
                      <form method="POST" style="flex: 1;">
                        <input type="hidden" name="id_cerimonialista" value="<?php echo $cerimo['id_usuario']; ?>">
                        <input type="hidden" name="data_casamento" value="<?php echo htmlspecialchars($data_casamento); ?>">
                        <button type="submit" class="btn-select">Escolher</button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php elseif ($data_casamento): ?>
            <div style="text-align: center; padding: 2rem; background: hsl(var(--muted)); border-radius: 1rem;">
              <p style="color: hsl(var(--muted-foreground));">Nenhum cerimonialista disponível para essa data.</p>
            </div>
          <?php else: ?>
            <div style="text-align: center; padding: 2rem; background: hsl(var(--muted)); border-radius: 1rem;">
              <p style="color: hsl(var(--muted-foreground));">Selecione uma data para ver os cerimonialistas disponíveis.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <div class="modal-overlay" id="fornecedoresModal">
    <div class="modal-content">
      <button class="modal-close" onclick="fecharFornecedores()">&times;</button>
      <h3 style="margin-top: 0;">Fornecedores</h3>
      <div class="fornecedores-list" id="fornecedoresList"></div>
    </div>
  </div>

  <script>
    function buscarCerimonialistas() {
      const data = document.getElementById('dataPicker').value;
      if (data) {
        window.location.href = '?data=' + data;
      }
    }

    function abrirFornecedores(idCerimonialista) {
      fetch('../api/get-fornecedores-cerimonialista.php?id=' + idCerimonialista)
        .then(response => response.json())
        .then(data => {
          const lista = document.getElementById('fornecedoresList');
          lista.innerHTML = '';
          
          if (data.length > 0) {
            data.forEach(fornecedor => {
              const item = document.createElement('div');
              item.className = 'fornecedor-item';
              item.innerHTML = `
                <div style="flex: 1;">
                  <h4>${fornecedor.nome_fornecedor}</h4>
                  <p>${fornecedor.categoria}</p>
                  <p style="font-size: 0.85rem; color: hsl(var(--primary));">★ ${parseFloat(fornecedor.avaliacao || 0).toFixed(1)}/5</p>
                </div>
              `;
              lista.appendChild(item);
            });
          } else {
            lista.innerHTML = '<p style="text-align: center; color: hsl(var(--muted-foreground));">Nenhum fornecedor disponível</p>';
          }
          
          document.getElementById('fornecedoresModal').classList.add('active');
        })
        .catch(error => console.error('Erro:', error));
    }

    function fecharFornecedores() {
      document.getElementById('fornecedoresModal').classList.remove('active');
    }
  </script>
</body>

</html>
