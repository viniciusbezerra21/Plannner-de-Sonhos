<?php
$id_avaliado = $id_avaliado ?? 0;

if ($id_avaliado > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            AVG(nota) as media,
            COUNT(*) as total,
            AVG(categoria_1) as cat1,
            AVG(categoria_2) as cat2,
            AVG(categoria_3) as cat3,
            AVG(categoria_4) as cat4,
            AVG(categoria_5) as cat5
        FROM avaliacoes
        WHERE avaliado_id = ?
    ");
    $stmt->execute([$id_avaliado]);
    $stats_aval = $stmt->fetch();
    
    $media_aval = $stats_aval['media'] ?? 0;
    $total_aval = $stats_aval['total'] ?? 0;
?>
<div class="widget-avaliacoes">
    <div class="widget-header">
        <h4>Avaliações</h4>
        <a href="avaliacoes.php?id=<?php echo $id_avaliado; ?>" class="ver-todas">Ver todas</a>
    </div>
    
    <div class="resumo-avaliacoes">
        <div class="nota-principal">
            <div class="numero-nota"><?php echo number_format($media_aval, 1, ',', '.'); ?></div>
            <div class="estrelas-widget">
                <?php 
                $nota_int = (int)$media_aval;
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $nota_int ? '★' : '☆';
                }
                ?>
            </div>
            <div class="total-avaliacoes"><?php echo $total_aval; ?> avaliações</div>
        </div>
        
        <?php if ($total_aval > 0): ?>
        <div class="categorias-resumo">
            <div class="cat-item">
                <span class="cat-label">Qualidade</span>
                <span class="cat-valor"><?php echo number_format($stats_aval['cat1'], 1); ?></span>
            </div>
            <div class="cat-item">
                <span class="cat-label">Pontualidade</span>
                <span class="cat-valor"><?php echo number_format($stats_aval['cat2'], 1); ?></span>
            </div>
            <div class="cat-item">
                <span class="cat-label">Atendimento</span>
                <span class="cat-valor"><?php echo number_format($stats_aval['cat3'], 1); ?></span>
            </div>
            <div class="cat-item">
                <span class="cat-label">Preço</span>
                <span class="cat-valor"><?php echo number_format($stats_aval['cat4'], 1); ?></span>
            </div>
            <div class="cat-item">
                <span class="cat-label">Recomendação</span>
                <span class="cat-valor"><?php echo number_format($stats_aval['cat5'], 1); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.widget-avaliacoes {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.widget-header h4 {
    margin: 0;
    font-size: 18px;
}

.ver-todas {
    color: #0084a0;
    text-decoration: none;
    font-size: 14px;
}

.resumo-avaliacoes {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
}

.nota-principal {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.numero-nota {
    font-size: 48px;
    font-weight: 700;
    color: #0084a0;
    line-height: 1;
}

.estrelas-widget {
    color: #ffc107;
    font-size: 20px;
    margin: 8px 0;
}

.total-avaliacoes {
    color: #666;
    font-size: 13px;
}

.categorias-resumo {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
}

.cat-label {
    font-size: 13px;
    color: #666;
}

.cat-valor {
    font-weight: 600;
    color: #0084a0;
    font-size: 14px;
}

@media (max-width: 768px) {
    .resumo-avaliacoes {
        grid-template-columns: 1fr;
    }
}
</style>
<?php } ?>
