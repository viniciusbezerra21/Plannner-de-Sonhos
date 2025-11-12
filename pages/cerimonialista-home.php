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

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'cerimonialista') {
    header("Location: ../user/login-unified.php?type=cerimonialista");
    exit;
}

$id_cerimonialista = $_SESSION['usuario_id'];
$clientes_proximos = [];
$stats = ['total_clientes' => 0, 'eventos_proximos' => 0, 'total_rendimento' => 0];

try {
    // Get próximos clientes
    $stmt = $pdo->prepare("
        SELECT 
            cc.id_assoc,
            u.id_usuario,
            u.nome,
            u.nome_conjuge,
            cc.data_casamento,
            COUNT(o.id_orcamento) as total_itens,
            SUM(o.valor_total) as valor_total
        FROM cliente_cerimonialista cc
        INNER JOIN usuarios u ON cc.id_cliente = u.id_usuario
        LEFT JOIN orcamentos o ON u.id_usuario = o.id_usuario
        WHERE cc.id_cerimonialista = ? AND cc.status = 'ativo' AND cc.data_casamento >= CURDATE()
        GROUP BY cc.id_assoc, u.id_usuario, u.nome, u.nome_conjuge, cc.data_casamento
        ORDER BY cc.data_casamento ASC
        LIMIT 5
    ");
    $stmt->execute([$id_cerimonialista]);
    $clientes_proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT id_cliente) as total_clientes,
            COUNT(DISTINCT CASE WHEN data_casamento >= CURDATE() THEN id_assoc END) as eventos_proximos,
            SUM(CASE WHEN data_casamento >= CURDATE() THEN 1 ELSE 0 END) as orcamentos_pendentes
        FROM cliente_cerimonialista
        WHERE id_cerimonialista = ? AND status = 'ativo'
    ");
    $stmt->execute([$id_cerimonialista]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = $result;
    }
} catch (PDOException $e) {
    error_log("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Cerimonialista | Planner de Sonhos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Style/styles.css" />
    <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon" />
    <style>
        .cerimonialista-header {
            background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--secondary)) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 1rem;
        }

        .cerimonialista-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .cerimonialista-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-color: hsl(var(--primary));
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            background: hsl(var(--primary) / 0.1);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: hsl(var(--primary));
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: hsl(var(--foreground));
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: hsl(var(--muted-foreground));
            font-size: 0.95rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: hsl(var(--foreground));
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title svg {
            width: 1.5rem;
            height: 1.5rem;
            color: hsl(var(--primary));
        }

        .clientes-proximos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .cliente-preview {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .cliente-preview:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-color: hsl(var(--primary));
        }

        .cliente-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .cliente-preview-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: hsl(var(--foreground));
        }

        .cliente-preview-date {
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .cliente-preview-info {
            display: flex;
            gap: 2rem;
            padding: 1rem;
            background: hsl(var(--muted));
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .cliente-preview-info-item {
            flex: 1;
        }

        .cliente-preview-info-label {
            color: hsl(var(--muted-foreground));
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }

        .cliente-preview-info-value {
            color: hsl(var(--foreground));
            font-weight: 600;
        }

        .cliente-preview-action {
            width: 100%;
            padding: 0.75rem;
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .cliente-preview-action:hover {
            background: hsl(var(--primary) / 0.9);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: hsl(var(--muted));
            border-radius: 1rem;
            color: hsl(var(--muted-foreground));
        }

        .empty-state svg {
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .cerimonialista-header h1 {
                font-size: 1.75rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .clientes-proximos {
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

                <nav class="nav">
                    <a href="cerimonialista-home.php" class="nav-link">Home</a>
                    <a href="cerimonialista-dashboard.php" class="nav-link">Dashboard</a>
                    <a href="cerimonialista-fornecedores.php" class="nav-link">Fornecedores</a>

                    <!-- Fixed profile dropdown positioning and structure -->
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
        <!-- Added proper spacing from fixed header -->
        <section class="page-content" style="padding-top: 6rem; min-height: 80vh">
            <div class="container">
                <!-- Greeting Section -->
                <div class="cerimonialista-header">
                    <h1>Bem-vindo, <?php echo htmlspecialchars(explode(' ', $_SESSION['nome'])[0]); ?>! 👋</h1>
                    <p>Gerencie seus casamentos e clientes com facilidade</p>
                </div>

                <!-- Stats Section -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_clientes']; ?></div>
                        <div class="stat-label">Total de Clientes</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="stat-number"><?php echo $stats['eventos_proximos']; ?></div>
                        <div class="stat-label">Eventos Próximos</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div class="stat-number"><?php echo $stats['orcamentos_pendentes'] ?? 0; ?></div>
                        <div class="stat-label">Orçamentos Pendentes</div>
                    </div>
                </div>

                <!-- Próximos Clientes Section -->
                <div style="margin-top: 3rem">
                    <h2 class="section-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 1 2-2m-3 7a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"></path>
                        </svg>
                        Próximos Casamentos
                    </h2>

                    <?php if (!empty($clientes_proximos)): ?>
                        <div class="clientes-proximos">
                            <?php foreach ($clientes_proximos as $cliente): ?>
                                <div class="cliente-preview">
                                    <div class="cliente-preview-header">
                                        <div>
                                            <div class="cliente-preview-name"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                                            <div style="color: hsl(var(--muted-foreground)); font-size: 0.9rem;">& <?php echo htmlspecialchars($cliente['nome_conjuge'] ?? 'Cônjuge'); ?></div>
                                        </div>
                                        <div class="cliente-preview-date">
                                            <?php 
                                            $data = new DateTime($cliente['data_casamento']);
                                            echo $data->format('d/m');
                                            ?>
                                        </div>
                                    </div>

                                    <div class="cliente-preview-info">
                                        <div class="cliente-preview-info-item">
                                            <div class="cliente-preview-info-label">Itens</div>
                                            <div class="cliente-preview-info-value"><?php echo $cliente['total_itens']; ?></div>
                                        </div>
                                        <div class="cliente-preview-info-item">
                                            <div class="cliente-preview-info-label">Valor</div>
                                            <div class="cliente-preview-info-value">R$ <?php echo number_format($cliente['valor_total'] ?? 0, 2, ',', '.'); ?></div>
                                        </div>
                                    </div>

                                    <a href="cliente-detalhes.php?id=<?php echo $cliente['id_usuario']; ?>" class="cliente-preview-action">Ver Detalhes</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <h3 style="margin-top: 1rem; font-size: 1.1rem;">Nenhum casamento próximo</h3>
                            <p>Você não tem casamentos agendados nos próximos dias. Confira sua lista completa de clientes.</p>
                            <a href="cerimonialista-dashboard.php" style="display: inline-block; margin-top: 1rem; color: hsl(var(--primary)); text-decoration: none; font-weight: 600;">Ver Todos os Clientes →</a>
                        </div>
                    <?php endif; ?>
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
