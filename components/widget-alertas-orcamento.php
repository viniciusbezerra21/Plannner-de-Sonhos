<?php
// Widget para exibir alertas de orçamento
// Uso: include 'components/widget-alertas-orcamento.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    return;
}
?>

<style>
    .alertas-orcamento-widget {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .widget-titulo {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alerta-item {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .alerta-item.info {
        background: #e3f2fd;
        border-left: 4px solid #1976d2;
    }
    
    .alerta-item.warning {
        background: #fff3e0;
        border-left: 4px solid #f57c00;
    }
    
    .alerta-item.danger {
        background: #ffebee;
        border-left: 4px solid #d32f2f;
    }
    
    .alerta-item.critical {
        background: #fce4ec;
        border-left: 4px solid #c2185b;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
    
    .alerta-icone {
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .alerta-conteudo {
        flex: 1;
    }
    
    .alerta-mensagem {
        font-size: 14px;
        color: #333;
        font-weight: 600;
        margin-bottom: 3px;
    }
    
    .alerta-percentual {
        font-size: 12px;
        color: #666;
    }
    
    .sem-alertas {
        text-align: center;
        padding: 20px;
        color: #999;
        font-size: 14px;
    }
    
    .btn-configurar {
        width: 100%;
        padding: 10px;
        background: #0084a0;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        text-align: center;
        display: block;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.3s;
    }
    
    .btn-configurar:hover {
        background: #006d85;
    }
</style>

<div class="alertas-orcamento-widget" id="widgetAlertasOrcamento">
    <div class="widget-titulo">
        Alertas de Orçamento
    </div>
    <div id="listaAlertas"></div>
    <a href="../pages/configurar-orcamento.php" class="btn-configurar">Configurar Alertas</a>
</div>

<script>
(function() {
    const iconesAlerta = {
        'info': '📊',
        'warning': '⚠️',
        'danger': '🚨',
        'critical': '❌'
    };
    
    function verificarAlertas() {
        fetch('../api/alertas_orcamento.php?acao=verificar_alertas')
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    renderizarAlertas(data.alertas);
                    
                    data.alertas.forEach(alerta => {
                        criarNotificacao(alerta);
                    });
                }
            })
            .catch(err => console.error('[v0] Erro ao verificar alertas:', err));
    }
    
    function renderizarAlertas(alertas) {
        const lista = document.getElementById('listaAlertas');
        
        if (alertas.length === 0) {
            lista.innerHTML = '<div class="sem-alertas">Nenhum alerta no momento</div>';
            return;
        }
        
        lista.innerHTML = alertas.map(alerta => {
            const icone = iconesAlerta[alerta.tipo] || '📢';
            return `
                <div class="alerta-item ${alerta.tipo}">
                    <div class="alerta-icone">${icone}</div>
                    <div class="alerta-conteudo">
                        <div class="alerta-mensagem">${alerta.mensagem}</div>
                        <div class="alerta-percentual">
                            ${alerta.percentual.toFixed(1)}% do orçamento utilizado
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function criarNotificacao(alerta) {
        const formData = new FormData();
        formData.append('acao', 'criar_notificacao_alerta');
        formData.append('nivel', alerta.nivel);
        formData.append('mensagem', alerta.mensagem);
        formData.append('percentual', alerta.percentual);
        
        fetch('../api/alertas_orcamento.php', {
            method: 'POST',
            body: formData
        });
    }
    
    verificarAlertas();
    setInterval(verificarAlertas, 60000);
})();
</script>
