<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../user/login-unified.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_avaliado = (int)($_GET['id'] ?? 0);

if ($id_avaliado <= 0) {
    header('Location: ../index.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_avaliado]);
$usuario_avaliado = $stmt->fetch();

if (!$usuario_avaliado) {
    header('Location: ../index.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT AVG(nota) as media, COUNT(*) as total 
    FROM avaliacoes 
    WHERE avaliado_id = ?
");
$stmt->execute([$id_avaliado]);
$stats = $stmt->fetch();
$media = $stats['media'] ?? 0;
$total_avaliacoes = $stats['total'] ?? 0;

$stmt = $pdo->prepare("
    SELECT a.*, u.nome, u.foto_perfil
    FROM avaliacoes a
    JOIN usuarios u ON u.id_usuario = a.avaliador_id
    WHERE a.avaliado_id = ?
    ORDER BY a.data_avaliacao DESC
");
$stmt->execute([$id_avaliado]);
$avaliacoes = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT * FROM avaliacoes 
    WHERE avaliador_id = ? AND avaliado_id = ?
");
$stmt->execute([$id_usuario, $id_avaliado]);
$minha_avaliacao = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - <?php echo htmlspecialchars($usuario_avaliado['nome']); ?></title>
    <link rel="stylesheet" href="../Style/style.css">
    <link rel="stylesheet" href="../Style/responsive.css">
    <style>
        .avaliacoes-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-top: 40px;
        }
        
        .card-usuario {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .avatar-grande {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ddd;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
        }
        
        .nome-usuario {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .tipo-badge-grande {
            display: inline-block;
            padding: 8px 16px;
            background: #e8f4f8;
            color: #0084a0;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .stats-avaliacoes {
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item {
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #0084a0;
        }
        
        .estrelas {
            font-size: 24px;
            color: #ffc107;
            letter-spacing: 2px;
        }
        
        .formulario-avaliacao {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .formulario-avaliacao h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .stars-input {
            display: flex;
            gap: 10px;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .stars-input span {
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s;
        }
        
        .stars-input span:hover,
        .stars-input span.ativo {
            color: #ffc107;
        }
        
        .categorias-input {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .categoria-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .categoria-item label {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .categoria-item input[type="range"] {
            width: 100%;
        }
        
        .categoria-valor {
            text-align: center;
            color: #0084a0;
            font-weight: 600;
            font-size: 12px;
        }
        
        textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            resize: vertical;
        }
        
        .btn-enviar {
            background: #0084a0;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-enviar:hover {
            background: #006a80;
        }
        
        .lista-avaliacoes {
            margin-top: 30px;
        }
        
        .lista-avaliacoes h3 {
            margin-bottom: 20px;
        }
        
        .avaliacao-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        .avaliacao-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }
        
        .avatar-pequeno {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #ddd;
            object-fit: cover;
        }
        
        .avaliacao-info {
            flex: 1;
        }
        
        .avaliacao-nome {
            font-weight: 600;
            color: #333;
        }
        
        .avaliacao-data {
            font-size: 13px;
            color: #999;
        }
        
        .avaliacao-nota {
            font-size: 18px;
            color: #ffc107;
        }
        
        .avaliacao-texto {
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        
        .avaliacao-categorias {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            font-size: 12px;
            margin-top: 12px;
        }
        
        .categoria-badge {
            background: #f5f5f5;
            padding: 8px;
            border-radius: 6px;
            text-align: center;
        }
        
        .categoria-badge-label {
            color: #999;
            font-size: 11px;
        }
        
        .categoria-badge-valor {
            font-weight: 600;
            color: #0084a0;
        }
        
        .vazio {
            text-align: center;
            color: #999;
            padding: 30px;
        }
        
        @media (max-width: 768px) {
            .avaliacoes-container {
                grid-template-columns: 1fr;
            }
            
            .categorias-input {
                grid-template-columns: 1fr;
            }
            
            .avaliacao-categorias {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="container">
        <div class="avaliacoes-container">
            <div class="card-usuario">
                <img src="../user/fotos/<?php echo $usuario_avaliado['foto_perfil'] ?? 'default.png'; ?>" 
                     alt="<?php echo htmlspecialchars($usuario_avaliado['nome']); ?>" 
                     class="avatar-grande">
                <div class="nome-usuario"><?php echo htmlspecialchars($usuario_avaliado['nome']); ?></div>
                <div class="tipo-badge-grande">
                    <?php 
                    $tipos = ['cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'cerimonialista' => 'Cerimonialista'];
                    echo $tipos[$usuario_avaliado['tipo_usuario']] ?? $usuario_avaliado['tipo_usuario'];
                    ?>
                </div>
                
                <div class="stats-avaliacoes">
                    <div class="stat-item">
                        <div class="stat-label">Avaliação Média</div>
                        <div class="stat-value"><?php echo number_format($media, 1, ',', '.'); ?></div>
                        <div class="estrelas">
                            <?php 
                            $nota_int = (int)$media;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $nota_int ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total de Avaliações</div>
                        <div class="stat-value"><?php echo $total_avaliacoes; ?></div>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="formulario-avaliacao">
                    <h3><?php echo $minha_avaliacao ? 'Editar Minha Avaliação' : 'Avaliar ' . htmlspecialchars($usuario_avaliado['nome']); ?></h3>
                    
                    <form id="form-avaliacao" method="POST" action="../api/avaliacoes.php">
                        <input type="hidden" name="acao" value="criar">
                        <input type="hidden" name="avaliado_id" value="<?php echo $id_avaliado; ?>">
                        
                        <div class="form-group">
                            <label>Sua Avaliação (Nota de 1 a 5)</label>
                            <div class="stars-input" id="stars-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span onclick="selecionarEstrela(<?php echo $i; ?>)" 
                                          class="<?php echo $minha_avaliacao && $minha_avaliacao['nota'] >= $i ? 'ativo' : ''; ?>"
                                          data-valor="<?php echo $i; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="nota" name="nota" value="<?php echo $minha_avaliacao['nota'] ?? 0; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Avalie por Categoria (opcional)</label>
                            <div class="categorias-input">
                                <div class="categoria-item">
                                    <label>Qualidade do Serviço</label>
                                    <input type="range" name="categoria_1" min="0" max="5" value="<?php echo $minha_avaliacao['categoria_1'] ?? 0; ?>" onchange="atualizarValor(this)">
                                    <div class="categoria-valor"><span class="valor">0</span>/5</div>
                                </div>
                                <div class="categoria-item">
                                    <label>Pontualidade</label>
                                    <input type="range" name="categoria_2" min="0" max="5" value="<?php echo $minha_avaliacao['categoria_2'] ?? 0; ?>" onchange="atualizarValor(this)">
                                    <div class="categoria-valor"><span class="valor">0</span>/5</div>
                                </div>
                                <div class="categoria-item">
                                    <label>Atendimento</label>
                                    <input type="range" name="categoria_3" min="0" max="5" value="<?php echo $minha_avaliacao['categoria_3'] ?? 0; ?>" onchange="atualizarValor(this)">
                                    <div class="categoria-valor"><span class="valor">0</span>/5</div>
                                </div>
                                <div class="categoria-item">
                                    <label>Preço</label>
                                    <input type="range" name="categoria_4" min="0" max="5" value="<?php echo $minha_avaliacao['categoria_4'] ?? 0; ?>" onchange="atualizarValor(this)">
                                    <div class="categoria-valor"><span class="valor">0</span>/5</div>
                                </div>
                                <div class="categoria-item">
                                    <label>Recomendação</label>
                                    <input type="range" name="categoria_5" min="0" max="5" value="<?php echo $minha_avaliacao['categoria_5'] ?? 0; ?>" onchange="atualizarValor(this)">
                                    <div class="categoria-valor"><span class="valor">0</span>/5</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Comentário (opcional)</label>
                            <textarea name="comentario" placeholder="Compartilhe sua experiência..."><?php echo $minha_avaliacao ? htmlspecialchars($minha_avaliacao['comentario']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-enviar">Enviar Avaliação</button>
                    </form>
                </div>
                
                <div class="lista-avaliacoes">
                    <h3>Avaliações de Clientes (<?php echo count($avaliacoes); ?>)</h3>
                    
                    <?php if (empty($avaliacoes)): ?>
                        <div class="vazio">
                            Nenhuma avaliação ainda
                        </div>
                    <?php else: ?>
                        <?php foreach ($avaliacoes as $avaliacao): ?>
                            <div class="avaliacao-item">
                                <div class="avaliacao-header">
                                    <img src="../user/fotos/<?php echo $avaliacao['foto_perfil'] ?? 'default.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($avaliacao['nome']); ?>" 
                                         class="avatar-pequeno">
                                    <div class="avaliacao-info">
                                        <div class="avaliacao-nome"><?php echo htmlspecialchars($avaliacao['nome']); ?></div>
                                        <div class="avaliacao-data"><?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></div>
                                    </div>
                                    <div class="avaliacao-nota">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $avaliacao['nota'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <?php if ($avaliacao['comentario']): ?>
                                    <div class="avaliacao-texto"><?php echo nl2br(htmlspecialchars($avaliacao['comentario'])); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($avaliacao['categoria_1'] || $avaliacao['categoria_2'] || $avaliacao['categoria_3'] || $avaliacao['categoria_4'] || $avaliacao['categoria_5']): ?>
                                    <div class="avaliacao-categorias">
                                        <div class="categoria-badge">
                                            <div class="categoria-badge-label">Qualidade</div>
                                            <div class="categoria-badge-valor"><?php echo $avaliacao['categoria_1']; ?></div>
                                        </div>
                                        <div class="categoria-badge">
                                            <div class="categoria-badge-label">Pontualidade</div>
                                            <div class="categoria-badge-valor"><?php echo $avaliacao['categoria_2']; ?></div>
                                        </div>
                                        <div class="categoria-badge">
                                            <div class="categoria-badge-label">Atendimento</div>
                                            <div class="categoria-badge-valor"><?php echo $avaliacao['categoria_3']; ?></div>
                                        </div>
                                        <div class="categoria-badge">
                                            <div class="categoria-badge-label">Preço</div>
                                            <div class="categoria-badge-valor"><?php echo $avaliacao['categoria_4']; ?></div>
                                        </div>
                                        <div class="categoria-badge">
                                            <div class="categoria-badge-label">Recomendação</div>
                                            <div class="categoria-badge-valor"><?php echo $avaliacao['categoria_5']; ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        function selecionarEstrela(valor) {
            document.getElementById('nota').value = valor;
            const stars = document.querySelectorAll('#stars-input span');
            stars.forEach((star, index) => {
                star.classList.toggle('ativo', index < valor);
            });
        }
        
        function atualizarValor(input) {
            const valor = input.value;
            input.parentElement.querySelector('.valor').textContent = valor;
        }
        
        document.getElementById('form-avaliacao').addEventListener('submit', function(e) {
            e.preventDefault();
            const nota = document.getElementById('nota').value;
            if (!nota || nota == 0) {
                alert('Por favor, selecione uma nota');
                return;
            }
            this.submit();
        });
    </script>
</body>
</html>
