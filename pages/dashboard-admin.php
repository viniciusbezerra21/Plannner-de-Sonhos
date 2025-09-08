<?php
session_start();
require_once '../user/auth_middleware.php';

// Verificar se é admin/dev
verificarPermissao(['dev']);

$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "weddingeasy";
$conn = new mysqli($hostname, $user, $password, $database);

// Estatísticas do sistema
$stats = [];

// Total de usuários
$result = $conn->query("SELECT COUNT(*) as total FROM usuario");
$stats['total_usuarios'] = $result->fetch_assoc()['total'];

// Usuários por cargo
$result = $conn->query("SELECT cargo, COUNT(*) as total FROM usuario GROUP BY cargo");
while($row = $result->fetch_assoc()) {
    $stats['usuarios_' . $row['cargo']] = $row['total'];
}

// Usuários cadastrados hoje
$result = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE DATE(data_cadastro) = CURDATE()");
$stats['usuarios_hoje'] = $result->fetch_assoc()['total'];

// Últimos usuários cadastrados
$result = $conn->query("SELECT nome, email, data_cadastro FROM usuario ORDER BY data_cadastro DESC LIMIT 10");
$ultimos_usuarios = [];
while($row = $result->fetch_assoc()) {
    $ultimos_usuarios[] = [
        'nome' => descriptografar($row['nome']),
        'email' => $row['email'], // Email já em texto plano
        'data_cadastro' => $row['data_cadastro']
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - WeddingEasy</title>
    <link rel="stylesheet" href="../Style/styles.css">
    <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
    <style>
        .admin-dashboard {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: hsl(var(--primary));
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .users-table {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .users-table h3 {
            padding: 1.5rem;
            margin: 0;
            background: hsl(var(--primary));
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .btn-back {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: hsl(var(--primary));
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <a href="dev.php" class="btn-back">← Voltar para Dev</a>
        
        <h1>Dashboard Administrativo</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['usuarios_cliente'] ?? 0; ?></div>
                <div class="stat-label">Clientes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['usuarios_dev'] ?? 0; ?></div>
                <div class="stat-label">Desenvolvedores</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['usuarios_hoje']; ?></div>
                <div class="stat-label">Cadastros Hoje</div>
            </div>
        </div>
        
        <div class="users-table">
            <h3>Últimos Usuários Cadastrados</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Data de Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ultimos_usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
