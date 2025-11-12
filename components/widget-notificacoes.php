<?php
// Widget para exibir contador de notificações no header
// Uso: include 'components/widget-notificacoes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    return; // Não exibe se não estiver logado
}
?>

<style>
    .notificacoes-widget {
        position: relative;
        display: inline-block;
    }
    
    .notificacoes-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 8px 12px;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        transition: background 0.3s;
    }
    
    .notificacoes-link:hover {
        background: #f5f5f5;
    }
    
    .notificacoes-icone {
        font-size: 20px;
    }
    
    .notificacoes-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        background: #ff4757;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 600;
        min-width: 18px;
        text-align: center;
        display: none;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .notificacoes-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 350px;
        max-width: 400px;
        display: none;
        z-index: 1000;
        margin-top: 8px;
    }
    
    .notificacoes-dropdown.active {
        display: block;
    }
    
    .notificacoes-dropdown-header {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notificacoes-dropdown-header h3 {
        font-size: 16px;
        margin: 0;
        color: #333;
    }
    
    .notificacoes-dropdown-body {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notificacao-item-mini {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        gap: 10px;
    }
    
    .notificacao-item-mini:hover {
        background: #f9f9f9;
    }
    
    .notificacao-item-mini.nao-lida {
        background: #f0f0ff;
    }
    
    .notificacao-item-mini:last-child {
        border-bottom: none;
    }
    
    .notificacao-mini-icone {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    
    .notificacao-mini-icone.mensagem { background: #e3f2fd; }
    .notificacao-mini-icone.avaliacao { background: #fff3e0; }
    .notificacao-mini-icone.contrato { background: #e8f5e9; }
    
    .notificacao-mini-conteudo {
        flex: 1;
        min-width: 0;
    }
    
    .notificacao-mini-titulo {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .notificacao-mini-data {
        font-size: 11px;
        color: #999;
    }
    
    .notificacoes-dropdown-footer {
        padding: 12px 15px;
        border-top: 1px solid #e0e0e0;
        text-align: center;
    }
    
    .notificacoes-dropdown-footer a {
        color: #6c5ce7;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }
    
    .notificacoes-dropdown-footer a:hover {
        text-decoration: underline;
    }
    
    .sem-notificacoes-mini {
        padding: 30px 15px;
        text-align: center;
        color: #999;
        font-size: 13px;
    }
</style>

<div class="notificacoes-widget">
    <a href="#" class="notificacoes-link" id="notificacoesToggle">
        <span class="notificacoes-icone">🔔</span>
        <span class="notificacoes-badge" id="notificacoesBadge">0</span>
    </a>
    
    <div class="notificacoes-dropdown" id="notificacoesDropdown">
        <div class="notificacoes-dropdown-header">
            <h3>Notificações</h3>
            <a href="#" id="marcarTodasLidasWidget" style="font-size: 12px; color: #6c5ce7; text-decoration: none;">Marcar todas como lidas</a>
        </div>
        <div class="notificacoes-dropdown-body" id="notificacoesLista">
            <div class="sem-notificacoes-mini">Carregando...</div>
        </div>
        <div class="notificacoes-dropdown-footer">
            <a href="../pages/notificacoes.php">Ver todas as notificações</a>
        </div>
    </div>
</div>

<script>
(function() {
    const toggle = document.getElementById('notificacoesToggle');
    const dropdown = document.getElementById('notificacoesDropdown');
    const badge = document.getElementById('notificacoesBadge');
    const lista = document.getElementById('notificacoesLista');
    let notificacoesCache = [];
    
    const iconesNotif = {
        'mensagem': '✉️',
        'avaliacao': '⭐',
        'contrato': '📋',
        'candidatura': '👤',
        'sistema': '🔔',
        'default': '📢'
    };
    
    // Toggle dropdown
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.toggle('active');
        if (dropdown.classList.contains('active')) {
            carregarNotificacoes();
        }
    });
    
    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notificacoes-widget')) {
            dropdown.classList.remove('active');
        }
    });
    
    // Carregar notificações
    function carregarNotificacoes() {
        fetch('../api/notificacoes.php?acao=listar')
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    notificacoesCache = data.notificacoes;
                    renderizarNotificacoes();
                }
            })
            .catch(err => console.error('[v0] Erro ao carregar notificações:', err));
    }
    
    // Renderizar notificações (últimas 5)
    function renderizarNotificacoes() {
        const notificacoes = notificacoesCache.slice(0, 5);
        
        if (notificacoes.length === 0) {
            lista.innerHTML = '<div class="sem-notificacoes-mini">Nenhuma notificação recente</div>';
            return;
        }
        
        lista.innerHTML = notificacoes.map(notif => {
            const icone = iconesNotif[notif.tipo] || iconesNotif.default;
            const classeLida = notif.lida == 0 ? 'nao-lida' : '';
            const dataFormatada = formatarDataMini(notif.data_criacao);
            
            return `
                <div class="notificacao-item-mini ${classeLida}" onclick="abrirNotificacao(${notif.id_notificacao}, '${notif.referencia_tabela}', ${notif.referencia_id})">
                    <div class="notificacao-mini-icone ${notif.tipo}">${icone}</div>
                    <div class="notificacao-mini-conteudo">
                        <div class="notificacao-mini-titulo">${notif.titulo}</div>
                        <div class="notificacao-mini-data">${dataFormatada}</div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Abrir notificação
    window.abrirNotificacao = function(id, tabela, referenciaId) {
        // Marcar como lida
        const formData = new FormData();
        formData.append('acao', 'marcar_lida');
        formData.append('id_notificacao', id);
        
        fetch('../api/notificacoes.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            atualizarContador();
        });
        
        // Redirecionar
        let url = '../pages/notificacoes.php';
        if (tabela && referenciaId) {
            switch(tabela) {
                case 'mensagens':
                    url = `../pages/mensagens.php?conversa=${referenciaId}`;
                    break;
                case 'avaliacoes':
                    url = `../pages/avaliacoes.php#avaliacao-${referenciaId}`;
                    break;
                case 'contratos':
                    url = `../pages/contratos.php?id=${referenciaId}`;
                    break;
                case 'candidaturas_fornecedor':
                    url = `../pages/candidaturas.php?id=${referenciaId}`;
                    break;
            }
        }
        window.location.href = url;
    };
    
    // Marcar todas como lidas
    document.getElementById('marcarTodasLidasWidget').addEventListener('click', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('acao', 'marcar_todas_lidas');
        
        fetch('../api/notificacoes.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                carregarNotificacoes();
                atualizarContador();
            }
        });
    });
    
    // Atualizar contador
    function atualizarContador() {
        fetch('../api/notificacoes.php?acao=nao_lidas')
            .then(res => res.json())
            .then(data => {
                if (data.sucesso && data.total > 0) {
                    badge.textContent = data.total > 99 ? '99+' : data.total;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            });
    }
    
    // Formatar data mini
    function formatarDataMini(dataString) {
        const data = new Date(dataString);
        const agora = new Date();
        const diffMs = agora - data;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHoras = Math.floor(diffMs / 3600000);
        
        if (diffMins < 1) return 'Agora';
        if (diffMins < 60) return `${diffMins}min`;
        if (diffHoras < 24) return `${diffHoras}h`;
        return `${Math.floor(diffHoras / 24)}d`;
    }
    
    // Auto-refresh a cada 30 segundos
    setInterval(atualizarContador, 30000);
    
    // Carregar ao iniciar
    atualizarContador();
})();
</script>
