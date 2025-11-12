<?php
$foto = $usuario['foto_perfil'] ?? 'default.png';
$tipos = ['cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'cerimonialista' => 'Cerimonialista'];
$tipo_label = $tipos[$usuario['tipo_usuario']] ?? $usuario['tipo_usuario'];
?>

<style>
    .card-usuario-linha {
        background: white;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        border: 1px solid #eee;
        transition: border-color 0.3s;
    }
    
    .card-usuario-linha:hover {
        border-color: #0084a0;
        box-shadow: 0 2px 8px rgba(0,132,160,0.1);
    }
    
    .card-usuario-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #ddd;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .card-usuario-info {
        flex: 1;
    }
    
    .card-usuario-nome {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    
    .card-usuario-tipo-info {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .card-usuario-tipo {
        font-size: 12px;
        background: #e8f4f8;
        color: #0084a0;
        padding: 3px 8px;
        border-radius: 12px;
    }
    
    .card-usuario-rating {
        font-size: 14px;
        color: #ffc107;
    }
    
    .card-usuario-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }
    
    .card-usuario-btn {
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: background 0.3s;
        white-space: nowrap;
    }
    
    .card-usuario-btn-primary {
        background: #0084a0;
        color: white;
    }
    
    .card-usuario-btn-primary:hover {
        background: #006a80;
    }
    
    .card-usuario-btn-secondary {
        background: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .card-usuario-btn-secondary:hover {
        background: #efefef;
    }
</style>

<div class="card-usuario-linha">
    <img src="../user/fotos/<?php echo $foto; ?>" 
         alt="<?php echo htmlspecialchars($usuario['nome']); ?>" 
         class="card-usuario-avatar">
    
    <div class="card-usuario-info">
        <div class="card-usuario-nome"><?php echo htmlspecialchars($usuario['nome']); ?></div>
        <div class="card-usuario-tipo-info">
            <span class="card-usuario-tipo"><?php echo $tipo_label; ?></span>
            <?php if ($usuario['avaliacao']): ?>
                <span class="card-usuario-rating">
                    <?php 
                    $nota_int = (int)$usuario['avaliacao'];
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $nota_int ? '★' : '☆';
                    }
                    ?> <?php echo number_format($usuario['avaliacao'], 1, ',', '.'); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-usuario-actions">
        <a href="../pages/mensagens.php?conversa_id=<?php echo $usuario['id_usuario']; ?>" 
           class="card-usuario-btn card-usuario-btn-primary">Mensagem</a>
        <a href="../pages/avaliacoes.php?id=<?php echo $usuario['id_usuario']; ?>" 
           class="card-usuario-btn card-usuario-btn-secondary">Avaliar</a>
    </div>
</div>
