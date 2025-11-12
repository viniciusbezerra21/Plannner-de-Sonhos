<?php
if (!isset($_SESSION['id_usuario'])) {
    echo '';
    exit();
}

$stmt = $pdo->prepare("
    SELECT * FROM notificacoes 
    WHERE usuario_id = ? 
    ORDER BY data_criacao DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['id_usuario']]);
$notificacoes = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM notificacoes 
    WHERE usuario_id = ? AND lida = 0
");
$stmt->execute([$_SESSION['id_usuario']]);
$nao_lidas = $stmt->fetch()['total'];
?>

<style>
    .notificacoes-widget {
        position: relative;
        display: inline-block;
    }
    
    .notificacoes-bell {
        cursor: pointer;
        font-size: 20px;
        position: relative;
    }
    
    .notificacoes-badge {
        position: absolute;
        top: -8px;
        right: -10px;
        background: #e74c3c;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    
    .notificacoes-dropdown {
        position: absolute;
        top: 40px;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
    }
    
    .notificacoes-dropdown.ativo {
        display: block;
    }
    
    .notificacao-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .notificacao-item:hover {
        background: #f9f9f9;
    }
    
    .notificacao-item.nao-lida {
        background: #f0f8ff;
        border-left: 4px solid #0084a0;
    }
    
    .notificacao-titulo {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .notificacao-descricao {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .notificacao-data {
        font-size: 12px;
        color: #999;
    }
    
    .notificacao-tipo {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        margin-top: 5px;
    }
    
    .tipo-mensagem {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .tipo-contrato {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .tipo-avaliacao {
        background: #fff3e0;
        color: #e65100;
    }
    
    .tipo-convite {
        background: #e8f5e9;
        color: #388e3c;
    }
    
    .tipo-evento {
        background: #fce4ec;
        color: #c2185b;
    }
    
    .notificacoes-header {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notificacoes-header h3 {
        margin: 0;
        font-size: 16px;
    }
    
    .marcar-todas-lidas {
        font-size: 12px;
        color: #0084a0;
        cursor: pointer;
        text-decoration: none;
    }
    
    .notificacoes-vazio {
        padding: 30px;
        text-align: center;
        color: #999;
    }
</style>

<div class="notificacoes-widget">
    <div class="notificacoes-bell" onclick="toggleNotificacoes()">
        🔔
        <?php if ($nao_lidas > 0): ?>
            <div class="notificacoes-badge"><?php echo $nao_lidas; ?></div>
        <?php endif; ?>
    </div>
    
    <div class="notificacoes-dropdown" id="notificacoes-dropdown">
        <div class="notificacoes-header">
            <h3>Notificações</h3>
            <?php if ($nao_lidas > 0): ?>
                <a class="marcar-todas-lidas" onclick="marcarTodasLidas()">Marcar como lida</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notificacoes)): ?>
            <div class="notificacoes-vazio">
                Nenhuma notificação
            </div>
        <?php else: ?>
            <?php foreach ($notificacoes as $notif): ?>
                <div class="notificacao-item <?php echo $notif['lida'] == 0 ? 'nao-lida' : ''; ?>" 
                     onclick="abrirNotificacao(<?php echo $notif['id_notificacao']; ?>, '<?php echo $notif['referencia_tabela']; ?>', <?php echo $notif['referencia_id']; ?>)">
                    <div class="notificacao-titulo"><?php echo htmlspecialchars($notif['titulo']); ?></div>
                    <?php if ($notif['descricao']): ?>
                        <div class="notificacao-descricao"><?php echo htmlspecialchars(substr($notif['descricao'], 0, 100)); ?></div>
                    <?php endif; ?>
                    <span class="notificacao-tipo tipo-<?php echo $notif['tipo']; ?>">
                        <?php 
                        $tipos = [
                            'mensagem' => 'Mensagem',
                            'contrato' => 'Contrato',
                            'avaliacao' => 'Avaliação',
                            'convite' => 'Convite',
                            'evento' => 'Evento'
                        ];
                        echo $tipos[$notif['tipo']] ?? $notif['tipo'];
                        ?>
                    </span>
                    <div class="notificacao-data">
                        <?php echo date('d/m H:i', strtotime($notif['data_criacao'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleNotificacoes() {
        const dropdown = document.getElementById('notificacoes-dropdown');
        dropdown.classList.toggle('ativo');
    }
    
    function marcarTodasLidas() {
        fetch('../api/notificacoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'acao=marcar_todas_lidas'
        }).then(() => {
            location.reload();
        });
    }
    
    function abrirNotificacao(id, tabela, referencia_id) {
        fetch('../api/notificacoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'acao=marcar_lida&id_notificacao=' + id
        }).then(() => {
            if (tabela === 'mensagens') {
                window.location.href = '../pages/mensagens.php?conversa_id=' + referencia_id;
            } else if (tabela === 'contratos') {
                window.location.href = '../pages/contratos.php?id=' + referencia_id;
            }
        });
    }
    
    document.addEventListener('click', function(e) {
        const widget = document.querySelector('.notificacoes-widget');
        if (!widget.contains(e.target)) {
            document.getElementById('notificacoes-dropdown').classList.remove('ativo');
        }
    });
</script>
