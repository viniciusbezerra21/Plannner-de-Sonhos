<?php
session_start();
require_once "../config/conexao.php";

if (isset($_POST['logout'])) {
    try {
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

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'cerimonialista') {
    header("Location: ../user/login-unified.php?type=cerimonialista");
    exit;
}

$id_cerimonialista = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';
$indisponibilidades = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['logout'])) {
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    if ($data_inicio && $data_fim && strtotime($data_inicio) <= strtotime($data_fim)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cerimonialista_indisponibilidade (id_cerimonialista, data_inicio, data_fim, motivo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_cerimonialista, $data_inicio, $data_fim, $motivo]);
            
            $mensagem = 'Período indisponível adicionado com sucesso!';
            $tipo_mensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao adicionar período: ' . $e->getMessage();
            $tipo_mensagem = 'erro';
            error_log("Error adding unavailability: " . $e->getMessage());
        }
    } else {
        $mensagem = 'Por favor, preencha as datas corretamente.';
        $tipo_mensagem = 'erro';
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM cerimonialista_indisponibilidade WHERE id_cerimonialista = ? ORDER BY data_inicio DESC");
    $stmt->execute([$id_cerimonialista]);
    $indisponibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching indisponibilidades: " . $e->getMessage());
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cerimonialista_indisponibilidade WHERE id_indisponibilidade = ? AND id_cerimonialista = ?");
        $stmt->execute([$_GET['delete'], $id_cerimonialista]);
        header("Location: cerimonialista-disponibilidade.php?sucesso=1");
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting indisponibilidade: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gerenciar Disponibilidade - Cerimonialista | Planner de Sonhos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Style/styles.css" />
    <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon" />
    <style>
        .disponibilidade-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (min-width: 1024px) {
            .disponibilidade-container {
                grid-template-columns: 450px 1fr;
            }
        }

        .form-section {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 2rem;
            height: fit-content;
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
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
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

        .form-group input:focus,
        .form-group textarea:focus {
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

        .indisponibilidades-list {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 2rem;
        }

        .list-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: hsl(var(--foreground));
            margin-bottom: 1.5rem;
        }

        .indisponibilidade-item {
            background: hsl(var(--background));
            border: 1px solid hsl(var(--border));
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .indisponibilidade-item:hover {
            border-color: hsl(var(--primary));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .indisponibilidade-info {
            flex: 1;
        }

        .indisponibilidade-periodo {
            font-size: 1.1rem;
            font-weight: 600;
            color: hsl(var(--foreground));
            margin-bottom: 0.5rem;
        }

        .indisponibilidade-motivo {
            color: hsl(var(--muted-foreground));
            font-size: 0.9rem;
        }

        .indisponibilidade-days {
            background: hsl(var(--primary) / 0.1);
            color: hsl(var(--primary));
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 1rem;
            display: inline-block;
        }

        .btn-delete {
            background: hsl(348 100% 61%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 0.85rem;
        }

        .btn-delete:hover {
            background: hsl(348 100% 61% / 0.9);
        }

        .calendar-info {
            background: hsl(var(--primary) / 0.1);
            border: 1px solid hsl(var(--primary) / 0.3);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: hsl(var(--foreground));
            font-size: 0.95rem;
        }

        .calendar-info strong {
            color: hsl(var(--primary));
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: hsl(var(--muted-foreground));
        }

        @media (max-width: 1024px) {
            .disponibilidade-container {
                grid-template-columns: 1fr;
            }

            .indisponibilidade-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .indisponibilidade-days {
                margin-right: 0;
                margin-bottom: 1rem;
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
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem; margin-top: 3rem; font-weight: 700;">Gerenciar Disponibilidade</h1>
                <p style="color: hsl(var(--muted-foreground)); margin-bottom: 2rem;">Marque os períodos em que você não está disponível. Você será considerado disponível em todos os outros dias.</p>

                <?php if ($mensagem): ?>
                    <div class="mensagem <?php echo $tipo_mensagem; ?>">
                        <?php echo htmlspecialchars($mensagem); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['sucesso'])): ?>
                    <div class="mensagem sucesso">
                        Período removido com sucesso!
                    </div>
                <?php endif; ?>

                <div class="disponibilidade-container">
                    <!-- Form Section -->
                    <div class="form-section">
                        <h2 class="form-title">Novo Período Indisponível</h2>
                        
                        <div class="calendar-info">
                            <strong>💡 Dica:</strong> Você estará disponível em TODOS os dias, exceto naqueles que você marcar aqui.
                        </div>

                        <form method="POST">
                            <div class="form-group">
                                <label for="data_inicio">Data de Início *</label>
                                <input type="date" id="data_inicio" name="data_inicio" required>
                            </div>

                            <div class="form-group">
                                <label for="data_fim">Data de Fim *</label>
                                <input type="date" id="data_fim" name="data_fim" required>
                            </div>

                            <div class="form-group">
                                <label for="motivo">Motivo (Opcional)</label>
                                <textarea id="motivo" name="motivo" placeholder="Ex: Férias, Evento pessoal, etc..."></textarea>
                            </div>

                            <button type="submit" class="btn-submit">Adicionar Período</button>
                        </form>
                    </div>

                    <!-- Indisponibilidades List -->
                    <div class="indisponibilidades-list">
                        <h2 class="list-title">Meus Períodos Indisponíveis</h2>
                        <?php if (!empty($indisponibilidades)): ?>
                            <div style="max-height: 700px; overflow-y: auto;">
                                <?php foreach ($indisponibilidades as $ind): 
                                    $data_inicio = new DateTime($ind['data_inicio']);
                                    $data_fim = new DateTime($ind['data_fim']);
                                    $dias = $data_fim->diff($data_inicio)->days + 1;
                                ?>
                                    <div class="indisponibilidade-item">
                                        <div class="indisponibilidade-info">
                                            <div class="indisponibilidade-periodo">
                                                <?php echo $data_inicio->format('d/m/Y'); ?> até <?php echo $data_fim->format('d/m/Y'); ?>
                                            </div>
                                            <span class="indisponibilidade-days"><?php echo $dias; ?> <?php echo $dias == 1 ? 'dia' : 'dias'; ?></span>
                                            <?php if ($ind['motivo']): ?>
                                                <div class="indisponibilidade-motivo">
                                                    <?php echo htmlspecialchars($ind['motivo']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="?delete=<?php echo $ind['id_indisponibilidade']; ?>" class="btn-delete" onclick="return confirm('Tem certeza?')">Remover</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p style="font-size: 1.1rem;">Nenhum período indisponível configurado.</p>
                                <p>Você está totalmente disponível!</p>
                            </div>
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

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_inicio').setAttribute('min', today);
        document.getElementById('data_fim').setAttribute('min', today);

        document.getElementById('data_inicio').addEventListener('change', function() {
            document.getElementById('data_fim').setAttribute('min', this.value);
        });
    </script>
</body>
</html>
