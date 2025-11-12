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
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'cerimonialista') {
    header("Location: ../user/login-unified.php?type=cerimonialista");
    exit;
}

$id_cerimonialista = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['logout'])) {
    $nome_fornecedor = $_POST['nome_fornecedor'] ?? '';
    $tipo_servico = $_POST['tipo_servico'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $valor_servico = $_POST['valor_servico'] ?? '';

    if ($nome_fornecedor && $tipo_servico && $email) {
        try {
            // Create supplier account
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, cargo, tipo_usuario, data_criacao) VALUES (?, ?, ?, ?, ?, NOW())");
            
            $senha_padrao = password_hash('senha123', PASSWORD_DEFAULT);
            $stmt->execute([$nome_fornecedor, $email, $senha_padrao, 'for', 'fornecedor']);
            
            $id_fornecedor = $pdo->lastInsertId();

            // Create association between cerimonialista and fornecedor
            $stmt = $pdo->prepare("INSERT INTO cerimonialista_fornecedor (id_cerimonialista, id_fornecedor, tipo_servico, descricao, valor_servico, telefone, data_associacao) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([$id_cerimonialista, $id_fornecedor, $tipo_servico, $descricao, $valor_servico, $telefone]);

            $mensagem = 'Fornecedor cadastrado com sucesso!';
            $tipo_mensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao cadastrar fornecedor: ' . $e->getMessage();
            $tipo_mensagem = 'erro';
            error_log("Error creating supplier: " . $e->getMessage());
        }
    } else {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
        $tipo_mensagem = 'erro';
    }
}

// Get existing fornecedores
$fornecedores = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.id_fornecedor,
            u.nome,
            cf.tipo_servico,
            cf.descricao,
            cf.valor_servico,
            u.email,
            cf.telefone,
            cf.data_associacao
        FROM cerimonialista_fornecedor cf
        INNER JOIN usuarios u ON cf.id_fornecedor = u.id_usuario
        WHERE cf.id_cerimonialista = ?
        ORDER BY cf.data_associacao DESC
    ");
    $stmt->execute([$id_cerimonialista]);
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching fornecedores: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cadastrar Fornecedores - Cerimonialista | Planner de Sonhos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Style/styles.css" />
    <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon" />
    <style>
        .fornecedores-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (min-width: 1024px) {
            .fornecedores-container {
                grid-template-columns: 450px 1fr;
            }
        }

        .form-section {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 2rem;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: hsl(var(--foreground));
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
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
            font-size: 0.95rem;
            color: hsl(var(--foreground));
            background: hsl(var(--background));
            box-sizing: border-box;
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

        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: hsl(var(--primary) / 0.9);
            transform: translateY(-2px);
        }

        .mensagem {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .mensagem.sucesso {
            background: hsl(142 76% 36% / 0.1);
            color: hsl(142 76% 36%);
            border: 1px solid hsl(142 76% 36%);
        }

        .mensagem.erro {
            background: hsl(348 100% 61% / 0.1);
            color: hsl(348 100% 61%);
            border: 1px solid hsl(348 100% 61%);
        }

        .fornecedores-list {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 2rem;
        }

        .fornecedores-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: hsl(var(--foreground));
            margin-bottom: 1.5rem;
        }

        .fornecedor-item {
            background: hsl(var(--background));
            border: 1px solid hsl(var(--border));
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .fornecedor-item:hover {
            border-color: hsl(var(--primary));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .fornecedor-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .fornecedor-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: hsl(var(--foreground));
        }

        .fornecedor-tipo {
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .fornecedor-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            font-size: 0.9rem;
            color: hsl(var(--muted-foreground));
        }

        .fornecedor-detail {
            margin-bottom: 0.5rem;
        }

        .fornecedor-detail-label {
            font-weight: 600;
            color: hsl(var(--foreground));
            margin-right: 0.5rem;
        }

        @media (max-width: 1024px) {
            .fornecedores-container {
                grid-template-columns: 1fr;
            }

            .fornecedor-details {
                grid-template-columns: 1fr;
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

                <!-- Updated navigation to match other cerimonialista pages -->
                <nav class="nav">
                    <a href="cerimonialista-home.php" class="nav-link">Home</a>
                    <a href="cerimonialista-dashboard.php" class="nav-link">Dashboard</a>
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
                                    <button type="submit" name="logout" class="profile-dropdown-item logout" style="width: 100%; text-align: left; background: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem;">
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
        <section class="page-content" style="padding-top: 2rem; min-height: 80vh">
            <div class="container">
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem; font-weight: 700;">Gerenciar Fornecedores</h1>
                <p style="color: hsl(var(--muted-foreground)); margin-bottom: 2rem;">Cadastre e gerencie seus fornecedores</p>

                <?php if ($mensagem): ?>
                    <div class="mensagem <?php echo $tipo_mensagem; ?>">
                        <?php echo htmlspecialchars($mensagem); ?>
                    </div>
                <?php endif; ?>

                <div class="fornecedores-container">
                    <!-- Form Section -->
                    <div class="form-section">
                        <h2 class="form-title">Novo Fornecedor</h2>
                        <form method="POST">
                            <div class="form-group">
                                <label for="nome_fornecedor">Nome do Fornecedor *</label>
                                <input type="text" id="nome_fornecedor" name="nome_fornecedor" required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_servico">Tipo de Serviço *</label>
                                <select id="tipo_servico" name="tipo_servico" required>
                                    <option value="">Selecione um tipo</option>
                                    <option value="Fotografia">Fotografia</option>
                                    <option value="Catering">Catering</option>
                                    <option value="Decoração">Decoração</option>
                                    <option value="DJ">DJ / Música</option>
                                    <option value="Transporte">Transporte</option>
                                    <option value="Locação">Locação de Espaço</option>
                                    <option value="Confeitaria">Confeitaria</option>
                                    <option value="Convites">Convites</option>
                                    <option value="Flores">Flores e Buquês</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="descricao">Descrição</label>
                                <textarea id="descricao" name="descricao" placeholder="Descreva os serviços oferecidos..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" placeholder="(11) 99999-9999">
                            </div>

                            <div class="form-group">
                                <label for="valor_servico">Valor do Serviço (R$)</label>
                                <input type="number" id="valor_servico" name="valor_servico" step="0.01" min="0" placeholder="0,00">
                            </div>

                            <button type="submit" class="btn-submit">Cadastrar Fornecedor</button>
                        </form>
                    </div>

                    <!-- Fornecedores List -->
                    <div class="fornecedores-list">
                        <h2 class="fornecedores-title">Meus Fornecedores</h2>
                        <?php if (!empty($fornecedores)): ?>
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <div class="fornecedor-item">
                                        <div class="fornecedor-header">
                                            <div>
                                                <div class="fornecedor-name"><?php echo htmlspecialchars($fornecedor['nome']); ?></div>
                                            </div>
                                            <div class="fornecedor-tipo"><?php echo htmlspecialchars($fornecedor['tipo_servico']); ?></div>
                                        </div>
                                        <div class="fornecedor-details">
                                            <div class="fornecedor-detail">
                                                <span class="fornecedor-detail-label">Email:</span>
                                                <span><?php echo htmlspecialchars($fornecedor['email']); ?></span>
                                            </div>
                                            <div class="fornecedor-detail">
                                                <span class="fornecedor-detail-label">Telefone:</span>
                                                <span><?php echo htmlspecialchars($fornecedor['telefone'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="fornecedor-detail">
                                                <span class="fornecedor-detail-label">Valor:</span>
                                                <span>R$ <?php echo number_format($fornecedor['valor_servico'] ?? 0, 2, ',', '.'); ?></span>
                                            </div>
                                            <div class="fornecedor-detail">
                                                <span class="fornecedor-detail-label">Cadastrado:</span>
                                                <span><?php echo (new DateTime($fornecedor['data_associacao']))->format('d/m/Y'); ?></span>
                                            </div>
                                        </div>
                                        <?php if ($fornecedor['descricao']): ?>
                                            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: hsl(var(--muted-foreground));">
                                                <strong>Descrição:</strong> <?php echo htmlspecialchars($fornecedor['descricao']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 2rem;">
                                Você ainda não cadastrou fornecedores. Crie um novo usando o formulário ao lado.
                            </p>
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
