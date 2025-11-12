<?php
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo '';
    exit();
}

$tipo_usuario_atual = $_SESSION['tipo_usuario'];
$id_usuario = $_SESSION['id_usuario'];

$tipos_complementares = [];
if ($tipo_usuario_atual === 'cliente') {
    $tipos_complementares = ['fornecedor', 'cerimonialista'];
} elseif ($tipo_usuario_atual === 'fornecedor') {
    $tipos_complementares = ['cliente', 'cerimonialista'];
} elseif ($tipo_usuario_atual === 'cerimonialista') {
    $tipos_complementares = ['cliente', 'fornecedor'];
}

$placeholders = implode(',', array_fill(0, count($tipos_complementares), '?'));

$stmt = $pdo->prepare("
    SELECT u.* FROM usuarios u
    LEFT JOIN mensagens m ON (
        (m.remetente_id = ? AND m.destinatario_id = u.id_usuario) OR
        (m.remetente_id = u.id_usuario AND m.destinatario_id = ?)
    )
    WHERE u.id_usuario != ?
    AND u.tipo_usuario IN ($placeholders)
    AND m.id_mensagem IS NULL
    ORDER BY u.avaliacao DESC
    LIMIT 6
");

$params = [$id_usuario, $id_usuario, $id_usuario, ...$tipos_complementares];
$stmt->execute($params);
$sugestoes = $stmt->fetchAll();
?>

<style>
    .sugestoes-contatos {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .sugestoes-titulo {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
    }
    
    .sugestoes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
    }
    
    .sugestao-card {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        border: 1px solid #eee;
        transition: all 0.3s;
    }
    
    .sugestao-card:hover {
        border-color: #0084a0;
        box-shadow: 0 2px 8px rgba(0,132,160,0.1);
    }
    
    .sugestao-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #ddd;
        object-fit: cover;
        margin: 0 auto 10px;
        display: block;
    }
    
    .sugestao-nome {
        font-weight: 600;
        font-size: 13px;
        color: #333;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .sugestao-tipo {
        font-size: 11px;
        color: #666;
        background: #e8f4f8;
        color: #0084a0;
        padding: 2px 6px;
        border-radius: 10px;
        display: inline-block;
        margin-bottom: 8px;
    }
    
    .sugestao-rating {
        font-size: 12px;
        color: #ffc107;
        margin-bottom: 8px;
    }
    
    .sugestao-btn {
        width: 100%;
        padding: 6px;
        background: #0084a0;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 600;
    }
    
    .sugestao-btn:hover {
        background: #006a80;
    }
    
    .sugestoes-vazio {
        text-align: center;
        color: #999;
        padding: 20px;
    }
</style>

<div class="sugestoes-contatos">
    <div class="sugestoes-titulo">Sugestões de Contatos</div>
    
    <?php if (empty($sugestoes)): ?>
        <div class="sugestoes-vazio">
            Nenhuma sugestão disponível
        </div>
    <?php else: ?>
        <div class="sugestoes-grid">
            <?php foreach ($sugestoes as $sugestao): ?>
                <div class="sugestao-card">
                    <img src="../user/fotos/<?php echo $sugestao['foto_perfil'] ?? 'default.png'; ?>" 
                         alt="<?php echo htmlspecialchars($sugestao['nome']); ?>" 
                         class="sugestao-avatar">
                    
                    <div class="sugestao-nome" title="<?php echo htmlspecialchars($sugestao['nome']); ?>">
                        <?php echo htmlspecialchars($sugestao['nome']); ?>
                    </div>
                    
                    <div class="sugestao-tipo">
                        <?php 
                        $tipos = ['cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'cerimonialista' => 'Cerimonialista'];
                        echo $tipos[$sugestao['tipo_usuario']] ?? $sugestao['tipo_usuario'];
                        ?>
                    </div>
                    
                    <?php if ($sugestao['avaliacao']): ?>
                        <div class="sugestao-rating">
                            <?php 
                            $nota_int = (int)$sugestao['avaliacao'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $nota_int ? '★' : '☆';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="../pages/mensagens.php?conversa_id=<?php echo $sugestao['id_usuario']; ?>" 
                       class="sugestao-btn">Contatar</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
