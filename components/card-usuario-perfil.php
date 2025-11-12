<?php
$foto = $usuario['foto_perfil'] ?? 'default.png';
$tipos = ['cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'cerimonialista' => 'Cerimonialista'];
$tipo_label = $tipos[$usuario['tipo_usuario']] ?? $usuario['tipo_usuario'];
?>

<style>
    .card-perfil {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
        text-align: center;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .card-perfil:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .card-perfil-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #ddd;
        object-fit: cover;
        margin: 0 auto 15px;
        display: block;
        border: 3px solid #0084a0;
    }
    
    .card-perfil-nome {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 8px;
        color: #333;
    }
    
    .card-perfil-tipo {
        display: inline-block;
        padding: 4px 12px;
        background: #e8f4f8;
        color: #0084a0;
        border-radius: 20px;
        font-size: 12px;
        margin-bottom: 12px;
    }
    
    .card-perfil-rating {
        margin: 12px 0;
        font-size: 18px;
        color: #ffc107;
    }
    
    .card-perfil-rating-label {
        font-size: 12px;
        color: #999;
        margin-top: 4px;
    }
    
    .card-perfil-acoes {
        display: flex;
        gap: 8px;
        margin-top: 15px;
    }
    
    .btn-acao {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.3s;
    }
    
    .btn-mensagem {
        background: #0084a0;
        color: white;
    }
    
    .btn-mensagem:hover {
        background: #006a80;
    }
    
    .btn-avaliar {
        background: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .btn-avaliar:hover {
        background: #efefef;
    }
</style>

<div class="card-perfil">
    <img src="../user/fotos/<?php echo $foto; ?>" 
         alt="<?php echo htmlspecialchars($usuario['nome']); ?>" 
         class="card-perfil-avatar">
    
    <div class="card-perfil-nome"><?php echo htmlspecialchars($usuario['nome']); ?></div>
    
    <div class="card-perfil-tipo"><?php echo $tipo_label; ?></div>
    
    <?php if ($usuario['avaliacao']): ?>
        <div class="card-perfil-rating">
            <?php 
            $nota_int = (int)$usuario['avaliacao'];
            for ($i = 1; $i <= 5; $i++) {
                echo $i <= $nota_int ? '★' : '☆';
            }
            ?>
        </div>
        <div class="card-perfil-rating-label">
            <?php echo number_format($usuario['avaliacao'], 1, ',', '.'); ?>/5.0
        </div>
    <?php endif; ?>
    
    <div class="card-perfil-acoes">
        <a href="../pages/mensagens.php?conversa_id=<?php echo $usuario['id_usuario']; ?>" class="btn-acao btn-mensagem">Mensagem</a>
        <a href="../pages/avaliacoes.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn-acao btn-avaliar">Avaliar</a>
    </div>
</div>
