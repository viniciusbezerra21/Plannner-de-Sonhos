<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../user/login-unified.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo_usuario'];

$candidaturas = [];

if ($tipo_usuario === 'fornecedor') {
    // Fornecedor vê suas candidaturas
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $fornecedor = $stmt->fetch();
    
    if ($fornecedor) {
        $stmt = $pdo->prepare("
            SELECT c.*, ce.nome_cerimonialista, ce.empresa, u.nome as nome_usuario_cerimonialista
            FROM candidaturas_fornecedor c
            JOIN cerimonialistas ce ON ce.id_cerimonialista = c.id_cerimonialista
            JOIN usuarios u ON u.id_usuario = ce.id_usuario
            WHERE c.id_fornecedor = ?
            ORDER BY c.data_candidatura DESC
        ");
        $stmt->execute([$fornecedor['id_fornecedor']]);
        $candidaturas = $stmt->fetchAll();
    }
} elseif ($tipo_usuario === 'cerimonialista') {
    // Cerimonialista vê candidaturas recebidas
    $stmt = $pdo->prepare("SELECT * FROM cerimonialistas WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $cerimonialista = $stmt->fetch();
    
    if ($cerimonialista) {
        $stmt = $pdo->prepare("
            SELECT c.*, f.nome_fornecedor, f.tipo_servico, f.localizacao, u.nome as nome_usuario_fornecedor, u.foto_perfil
            FROM candidaturas_fornecedor c
            JOIN fornecedores f ON f.id_fornecedor = c.id_fornecedor
            JOIN usuarios u ON u.id_usuario = f.id_usuario
            WHERE c.id_cerimonialista = ?
            ORDER BY c.data_candidatura DESC
        ");
        $stmt->execute([$cerimonialista['id_cerimonialista']]);
        $candidaturas = $stmt->fetchAll();
    }
}

// Buscar cerimonialistas disponíveis (para fornecedores)
$cerimonialistas = [];
if ($tipo_usuario === 'fornecedor') {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nome, u.foto_perfil,
        (SELECT AVG(nota) FROM avaliacoes WHERE avaliado_id = u.id_usuario) as media_avaliacoes
        FROM cerimonialistas c
        JOIN usuarios u ON u.id_usuario = c.id_usuario
        ORDER BY media_avaliacoes DESC
        LIMIT 20
    ");
    $stmt->execute();
    $cerimonialistas = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidaturas - WeddingEasy</title>
    <link rel="stylesheet" href="../Style/style.css">
    <link rel="stylesheet" href="../Style/responsive.css">
    <style>
        .candidaturas-container {
            max-width: 1200px;
            margin: 40px auto;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.ativa {
            color: #0084a0;
            border-bottom-color: #0084a0;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.ativo {
            display: block;
        }
        
        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .card-candidatura {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .card-candidatura:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ddd;
            object-fit: cover;
        }
        
        .card-info h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        
        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-status.pendente {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-status.aprovada {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-status.rejeitada {
            background: #f8d7da;
            color: #721c24;
        }
        
        .card-body {
            margin: 15px 0;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            color: #666;
            font-size: 14px;
        }
        
        .info-valor {
            font-weight: 600;
            color: #333;
        }
        
        .card-mensagem {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            color: #666;
            font-style: italic;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-aprovar {
            background: #28a745;
            color: white;
        }
        
        .btn-rejeitar {
            background: #dc3545;
            color: white;
        }
        
        .btn-ver {
            background: #0084a0;
            color: white;
        }
        
        .btn-cancelar {
            background: #6c757d;
            color: white;
        }
        
        .vazio {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .vazio h3 {
            margin-bottom: 10px;
        }
        
        .estrelas {
            color: #ffc107;
            font-size: 14px;
        }
        
        .btn-candidatar {
            width: 100%;
            padding: 12px;
            background: #0084a0;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.ativo {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-fechar {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            min-height: 100px;
        }
        
        @media (max-width: 768px) {
            .grid-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="container candidaturas-container">
        <h1>Candidaturas</h1>
        
        <?php if ($tipo_usuario === 'fornecedor'): ?>
            <div class="tabs">
                <button class="tab ativa" onclick="trocarTab(0)">Minhas Candidaturas</button>
                <button class="tab" onclick="trocarTab(1)">Buscar Cerimonialistas</button>
            </div>
            
            <div class="tab-content ativo" id="tab-0">
                <h2>Minhas Candidaturas</h2>
                
                <?php if (empty($candidaturas)): ?>
                    <div class="vazio">
                        <h3>Nenhuma candidatura ainda</h3>
                        <p>Navegue pela aba "Buscar Cerimonialistas" para se candidatar</p>
                    </div>
                <?php else: ?>
                    <div class="grid-cards">
                        <?php foreach ($candidaturas as $cand): ?>
                            <div class="card-candidatura">
                                <div class="card-header">
                                    <div class="card-info">
                                        <h3><?php echo htmlspecialchars($cand['nome_cerimonialista']); ?></h3>
                                        <span class="badge-status <?php echo $cand['status_candidatura']; ?>">
                                            <?php 
                                            $status_map = ['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'rejeitada' => 'Rejeitada'];
                                            echo $status_map[$cand['status_candidatura']] ?? $cand['status_candidatura'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="info-item">
                                        <span class="info-label">Empresa</span>
                                        <span class="info-valor"><?php echo htmlspecialchars($cand['empresa'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Data</span>
                                        <span class="info-valor"><?php echo date('d/m/Y', strtotime($cand['data_candidatura'])); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($cand['mensagem']): ?>
                                    <div class="card-mensagem">
                                        <?php echo nl2br(htmlspecialchars($cand['mensagem'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-actions">
                                    <?php if ($cand['status_candidatura'] === 'pendente'): ?>
                                        <button class="btn btn-cancelar" onclick="cancelarCandidatura(<?php echo $cand['id_candidatura']; ?>)">Cancelar</button>
                                    <?php endif; ?>
                                    <a href="mensagens.php?conversa_id=<?php echo $cand['nome_usuario_cerimonialista']; ?>" class="btn btn-ver">Mensagem</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="tab-1">
                <h2>Buscar Cerimonialistas</h2>
                
                <div class="grid-cards">
                    <?php foreach ($cerimonialistas as $cerim): ?>
                        <div class="card-candidatura">
                            <div class="card-header">
                                <img src="../user/fotos/<?php echo $cerim['foto_perfil'] ?? 'default.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($cerim['nome']); ?>" 
                                     class="avatar">
                                <div class="card-info">
                                    <h3><?php echo htmlspecialchars($cerim['nome']); ?></h3>
                                    <div class="estrelas">
                                        <?php 
                                        $media = $cerim['media_avaliacoes'] ?? 0;
                                        $nota_int = (int)$media;
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $nota_int ? '★' : '☆';
                                        }
                                        echo ' ' . number_format($media, 1);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">Empresa</span>
                                    <span class="info-valor"><?php echo htmlspecialchars($cerim['empresa'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Localização</span>
                                    <span class="info-valor"><?php echo htmlspecialchars($cerim['localizacao'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($cerim['descricao']): ?>
                                <div class="card-mensagem">
                                    <?php echo nl2br(htmlspecialchars(substr($cerim['descricao'], 0, 150))); ?>...
                                </div>
                            <?php endif; ?>
                            
                            <button class="btn-candidatar" onclick="abrirModalCandidatura(<?php echo $cerim['id_cerimonialista']; ?>, '<?php echo htmlspecialchars($cerim['nome']); ?>')">
                                Candidatar-se
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php elseif ($tipo_usuario === 'cerimonialista'): ?>
            <h2>Candidaturas Recebidas</h2>
            
            <?php if (empty($candidaturas)): ?>
                <div class="vazio">
                    <h3>Nenhuma candidatura recebida</h3>
                    <p>Fornecedores podem se candidatar para trabalhar com você</p>
                </div>
            <?php else: ?>
                <div class="grid-cards">
                    <?php foreach ($candidaturas as $cand): ?>
                        <div class="card-candidatura">
                            <div class="card-header">
                                <img src="../user/fotos/<?php echo $cand['foto_perfil'] ?? 'default.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($cand['nome_fornecedor']); ?>" 
                                     class="avatar">
                                <div class="card-info">
                                    <h3><?php echo htmlspecialchars($cand['nome_fornecedor']); ?></h3>
                                    <span class="badge-status <?php echo $cand['status_candidatura']; ?>">
                                        <?php 
                                        $status_map = ['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'rejeitada' => 'Rejeitada'];
                                        echo $status_map[$cand['status_candidatura']] ?? $cand['status_candidatura'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">Serviço</span>
                                    <span class="info-valor"><?php echo htmlspecialchars($cand['tipo_servico'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Localização</span>
                                    <span class="info-valor"><?php echo htmlspecialchars($cand['localizacao'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Data</span>
                                    <span class="info-valor"><?php echo date('d/m/Y', strtotime($cand['data_candidatura'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($cand['mensagem']): ?>
                                <div class="card-mensagem">
                                    <?php echo nl2br(htmlspecialchars($cand['mensagem'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-actions">
                                <?php if ($cand['status_candidatura'] === 'pendente'): ?>
                                    <button class="btn btn-aprovar" onclick="responderCandidatura(<?php echo $cand['id_candidatura']; ?>, 'aprovada')">Aprovar</button>
                                    <button class="btn btn-rejeitar" onclick="responderCandidatura(<?php echo $cand['id_candidatura']; ?>, 'rejeitada')">Rejeitar</button>
                                <?php else: ?>
                                    <a href="avaliacoes.php?id=<?php echo $cand['id_fornecedor']; ?>" class="btn btn-ver">Ver Perfil</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal Candidatura -->
    <div class="modal" id="modal-candidatura">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Candidatar-se</h3>
                <button class="btn-fechar" onclick="fecharModal()">×</button>
            </div>
            
            <form method="POST" action="../api/candidaturas.php">
                <input type="hidden" name="acao" value="criar">
                <input type="hidden" name="id_cerimonialista" id="id_cerimonialista">
                
                <p>Candidatar-se para trabalhar com <strong id="nome-cerimonialista"></strong></p>
                
                <div class="form-group">
                    <label>Mensagem de Apresentação</label>
                    <textarea name="mensagem" placeholder="Conte um pouco sobre você e por que gostaria de trabalhar com este cerimonialista..." required></textarea>
                </div>
                
                <div class="card-actions">
                    <button type="button" class="btn btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-aprovar">Enviar Candidatura</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        function trocarTab(index) {
            document.querySelectorAll('.tab').forEach((tab, i) => {
                tab.classList.toggle('ativa', i === index);
            });
            document.querySelectorAll('.tab-content').forEach((content, i) => {
                content.classList.toggle('ativo', i === index);
            });
        }
        
        function abrirModalCandidatura(idCerimonialista, nome) {
            document.getElementById('id_cerimonialista').value = idCerimonialista;
            document.getElementById('nome-cerimonialista').textContent = nome;
            document.getElementById('modal-candidatura').classList.add('ativo');
        }
        
        function fecharModal() {
            document.getElementById('modal-candidatura').classList.remove('ativo');
        }
        
        function cancelarCandidatura(idCandidatura) {
            if (!confirm('Deseja realmente cancelar esta candidatura?')) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../api/candidaturas.php';
            
            const acaoInput = document.createElement('input');
            acaoInput.type = 'hidden';
            acaoInput.name = 'acao';
            acaoInput.value = 'cancelar';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id_candidatura';
            idInput.value = idCandidatura;
            
            form.appendChild(acaoInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        function responderCandidatura(idCandidatura, status) {
            const mensagem = status === 'aprovada' ? 'Deseja aprovar esta candidatura?' : 'Deseja rejeitar esta candidatura?';
            if (!confirm(mensagem)) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../api/candidaturas.php';
            
            const acaoInput = document.createElement('input');
            acaoInput.type = 'hidden';
            acaoInput.name = 'acao';
            acaoInput.value = 'responder';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id_candidatura';
            idInput.value = idCandidatura;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            
            form.appendChild(acaoInput);
            form.appendChild(idInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('modal-candidatura').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>
</body>
</html>
