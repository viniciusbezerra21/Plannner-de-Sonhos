<?php
require_once '../user/auth_middleware.php';
verificarAcessoAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeddingEasy - Painel Desenvolvedor</title>
    <link rel="stylesheet" href="../Style/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../Style/assets/devicon.png" type="image/x-icon">
    <style>
        :root {
            --primary: 345 91% 58%;
            --primary-foreground: 345 100% 96%;
            --secondary: 345 60% 45%;
            --secondary-foreground: 345 100% 96%;
            --background: 0 0% 12%;
            --foreground: 345 30% 95%;
            --muted: 345 20% 25%;
            --muted-foreground: 345 20% 70%;
            --card: 345 20% 18%;
            --card-foreground: 345 30% 95%;
            --border: 345 20% 30%;
            --accent: 345 91% 58%;
            --accent-foreground: 345 100% 96%;
        }

        body {
            background: linear-gradient(135deg, rgb(194, 43, 81) 0%, rgb(0, 0, 0) 50%, rgb(100, 4, 20) 100%);
            color: white;
            font-family: "Roboto", sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }

        .dev-header {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }

        .dev-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .dev-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: hsl(var(--primary));
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .dev-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .action-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 1rem;
            color: hsl(var(--primary));
        }

        .btn-dev {
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-dev:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .logo-text {
            font-family: "Poppins", sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: white;
        }
    </style>
</head>
<body>
    <header class="dev-header">
        <div class="dev-container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <div class="heart-icon">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </div>
                    <span class="logo-text">WeddingEasy - DEV</span>
                </a>
                <nav style="display: flex; gap: 1rem; align-items: center;">
                    <span style="color: rgba(255,255,255,0.8);">Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                    <a href="../user/logout.php" class="btn-dev">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="dev-container">
        <div class="page-header" style="text-align: center; margin: 3rem 0;">
            <h1 style="font-size: 3rem; margin-bottom: 1rem; color: hsl(var(--primary));">Painel Desenvolvedor</h1>
            <p style="font-size: 1.2rem; color: rgba(255,255,255,0.8);">Gerencie o sistema WeddingEasy</p>
        </div>

        <div class="dev-stats">
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM usuario WHERE cargo = 'cliente'";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </div>
                <div class="stat-label">Usuários Clientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM eventos";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </div>
                <div class="stat-label">Eventos Criados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM mensagens_contato";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    echo $row['total'];
                    ?>
                </div>
                <div class="stat-label">Mensagens de Contato</div>
            </div>
        </div>

        <div class="dev-actions">
            <div class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <path d="M20 8v6M23 11h-6"/>
                    </svg>
                </div>
                <h3>Gerenciar Usuários</h3>
                <p>Visualizar, editar e gerenciar contas de usuários do sistema.</p>
                <button class="btn-dev" onclick="alert('Funcionalidade em desenvolvimento')">Acessar</button>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h3>Eventos do Sistema</h3>
                <p>Monitorar e gerenciar todos os eventos criados pelos usuários.</p>
                <button class="btn-dev" onclick="alert('Funcionalidade em desenvolvimento')">Acessar</button>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <h3>Mensagens de Contato</h3>
                <p>Visualizar e responder mensagens enviadas pelos usuários.</p>
                <button class="btn-dev" onclick="alert('Funcionalidade em desenvolvimento')">Acessar</button>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                </div>
                <h3>Configurações</h3>
                <p>Configurar parâmetros do sistema e manutenção.</p>
                <button class="btn-dev" onclick="alert('Funcionalidade em desenvolvimento')">Acessar</button>
            </div>
        </div>
    </main>
</body>
</html>
