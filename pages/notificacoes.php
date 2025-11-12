<?php
session_start();
require_once '../config/conexao.php';

// Verificar autenticação
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../user/login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = $_SESSION['nome'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Wedding Easy</title>
    <link rel="stylesheet" href="../Style/style.css">
    <style>
        .notificacoes-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .notificacoes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .notificacoes-header h1 {
            font-size: 28px;
            color: #333;
        }
        
        .btn-marcar-todas {
            background: #6c5ce7;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-marcar-todas:hover {
            background: #5f4dd4;
        }
        
        .btn-marcar-todas:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .filtros-notificacoes {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .filtro-btn {
            padding: 8px 16px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filtro-btn.active {
            background: #6c5ce7;
            color: white;
            border-color: #6c5ce7;
        }
        
        .notificacao-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            transition: all 0.3s;
            position: relative;
        }
        
        .notificacao-item.nao-lida {
            background: #f0f0ff;
            border-left: 4px solid #6c5ce7;
        }
        
        .notificacao-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .notificacao-icone {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .notificacao-icone.mensagem { background: #e3f2fd; color: #2196f3; }
        .notificacao-icone.avaliacao { background: #fff3e0; color: #ff9800; }
        .notificacao-icone.contrato { background: #e8f5e9; color: #4caf50; }
        .notificacao-icone.candidatura { background: #f3e5f5; color: #9c27b0; }
        .notificacao-icone.sistema { background: #fce4ec; color: #e91e63; }
        
        .notificacao-conteudo {
            flex: 1;
        }
        
        .notificacao-titulo {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .notificacao-descricao {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notificacao-data {
            font-size: 12px;
            color: #999;
        }
        
        .notificacao-acoes {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        
        .btn-notificacao {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-ver {
            background: #6c5ce7;
            color: white;
        }
        
        .btn-ver:hover {
            background: #5f4dd4;
        }
        
        .btn-deletar {
            background: #ff4757;
            color: white;
        }
        
        .btn-deletar:hover {
            background: #e63946;
        }
        
        .sem-notificacoes {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .sem-notificacoes-icone {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .badge-contador {
            background: #ff4757;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="notificacoes-container">
        <div class="notificacoes-header">
            <h1>Notificações <span id="badge-total" class="badge-contador" style="display: none;">0</span></h1>
            <button class="btn-marcar-todas" id="btnMarcarTodas" disabled>Marcar todas como lidas</button>
        </div>
        
        <div class="filtros-notificacoes">
            <button class="filtro-btn active" data-filtro="todas">Todas</button>
            <button class="filtro-btn" data-filtro="nao-lidas">Não lidas</button>
            <button class="filtro-btn" data-filtro="mensagem">Mensagens</button>
            <button class="filtro-btn" data-filtro="avaliacao">Avaliações</button>
            <button class="filtro-btn" data-filtro="contrato">Contratos</button>
            <button class="filtro-btn" data-filtro="candidatura">Candidaturas</button>
        </div>
        
        <div id="listaNotificacoes" class="loading">Carregando notificações...</div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        let notificacoes = [];
        let filtroAtivo = 'todas';
        
        // Ícones por tipo
        const iconesNotificacao = {
            'mensagem': '✉️',
            'avaliacao': '⭐',
            'contrato': '📋',
            'candidatura': '👤',
            'sistema': '🔔',
            'default': '📢'
        };
        
        // Carregar notificações
        function carregarNotificacoes() {
            fetch('../api/notificacoes.php?acao=listar')
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        notificacoes = data.notificacoes;
                        renderizarNotificacoes();
                        atualizarContadores();
                    }
                })
                .catch(err => {
                    console.error('[v0] Erro ao carregar notificações:', err);
                    document.getElementById('listaNotificacoes').innerHTML = 
                        '<div class="sem-notificacoes">Erro ao carregar notificações</div>';
                });
        }
        
        // Renderizar notificações
        function renderizarNotificacoes() {
            const container = document.getElementById('listaNotificacoes');
            
            // Filtrar notificações
            let notificacoesFiltradas = notificacoes;
            if (filtroAtivo === 'nao-lidas') {
                notificacoesFiltradas = notificacoes.filter(n => n.lida == 0);
            } else if (filtroAtivo !== 'todas') {
                notificacoesFiltradas = notificacoes.filter(n => n.tipo === filtroAtivo);
            }
            
            if (notificacoesFiltradas.length === 0) {
                container.innerHTML = `
                    <div class="sem-notificacoes">
                        <div class="sem-notificacoes-icone">🔔</div>
                        <p>Nenhuma notificação ${filtroAtivo === 'nao-lidas' ? 'não lida' : 'encontrada'}</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = notificacoesFiltradas.map(notif => {
                const icone = iconesNotificacao[notif.tipo] || iconesNotificacao.default;
                const dataFormatada = formatarData(notif.data_criacao);
                const classeLida = notif.lida == 0 ? 'nao-lida' : '';
                
                let linkVer = '#';
                if (notif.referencia_tabela && notif.referencia_id) {
                    switch(notif.referencia_tabela) {
                        case 'mensagens':
                            linkVer = `mensagens.php?conversa=${notif.referencia_id}`;
                            break;
                        case 'avaliacoes':
                            linkVer = `avaliacoes.php#avaliacao-${notif.referencia_id}`;
                            break;
                        case 'contratos':
                            linkVer = `contratos.php?id=${notif.referencia_id}`;
                            break;
                        case 'candidaturas_fornecedor':
                            linkVer = `candidaturas.php?id=${notif.referencia_id}`;
                            break;
                    }
                }
                
                return `
                    <div class="notificacao-item ${classeLida}" data-id="${notif.id_notificacao}">
                        <div class="notificacao-icone ${notif.tipo}">${icone}</div>
                        <div class="notificacao-conteudo">
                            <div class="notificacao-titulo">${notif.titulo}</div>
                            ${notif.descricao ? `<div class="notificacao-descricao">${notif.descricao}</div>` : ''}
                            <div class="notificacao-data">${dataFormatada}</div>
                        </div>
                        <div class="notificacao-acoes">
                            ${linkVer !== '#' ? `<a href="${linkVer}" class="btn-notificacao btn-ver" onclick="marcarLida(${notif.id_notificacao})">Ver</a>` : ''}
                            ${notif.lida == 0 ? `<button class="btn-notificacao btn-ver" onclick="marcarLida(${notif.id_notificacao})">Marcar lida</button>` : ''}
                            <button class="btn-notificacao btn-deletar" onclick="deletarNotificacao(${notif.id_notificacao})">Excluir</button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // Marcar como lida
        function marcarLida(idNotificacao) {
            const formData = new FormData();
            formData.append('acao', 'marcar_lida');
            formData.append('id_notificacao', idNotificacao);
            
            fetch('../api/notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    // Atualizar localmente
                    const notif = notificacoes.find(n => n.id_notificacao == idNotificacao);
                    if (notif) notif.lida = 1;
                    renderizarNotificacoes();
                    atualizarContadores();
                }
            });
        }
        
        // Marcar todas como lidas
        function marcarTodasLidas() {
            const formData = new FormData();
            formData.append('acao', 'marcar_todas_lidas');
            
            fetch('../api/notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    notificacoes.forEach(n => n.lida = 1);
                    renderizarNotificacoes();
                    atualizarContadores();
                }
            });
        }
        
        // Deletar notificação
        function deletarNotificacao(idNotificacao) {
            if (!confirm('Deseja realmente excluir esta notificação?')) return;
            
            const formData = new FormData();
            formData.append('acao', 'deletar');
            formData.append('id_notificacao', idNotificacao);
            
            fetch('../api/notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    notificacoes = notificacoes.filter(n => n.id_notificacao != idNotificacao);
                    renderizarNotificacoes();
                    atualizarContadores();
                }
            });
        }
        
        // Atualizar contadores
        function atualizarContadores() {
            const naoLidas = notificacoes.filter(n => n.lida == 0).length;
            const badgeTotal = document.getElementById('badge-total');
            const btnMarcarTodas = document.getElementById('btnMarcarTodas');
            
            if (naoLidas > 0) {
                badgeTotal.textContent = naoLidas;
                badgeTotal.style.display = 'inline-block';
                btnMarcarTodas.disabled = false;
            } else {
                badgeTotal.style.display = 'none';
                btnMarcarTodas.disabled = true;
            }
            
            // Atualizar badge no header (se existir)
            const badgeHeader = document.querySelector('.notificacoes-badge');
            if (badgeHeader) {
                if (naoLidas > 0) {
                    badgeHeader.textContent = naoLidas;
                    badgeHeader.style.display = 'block';
                } else {
                    badgeHeader.style.display = 'none';
                }
            }
        }
        
        // Formatar data
        function formatarData(dataString) {
            const data = new Date(dataString);
            const agora = new Date();
            const diffMs = agora - data;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHoras = Math.floor(diffMs / 3600000);
            const diffDias = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Agora';
            if (diffMins < 60) return `${diffMins}min atrás`;
            if (diffHoras < 24) return `${diffHoras}h atrás`;
            if (diffDias < 7) return `${diffDias}d atrás`;
            
            return data.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Event listeners
        document.getElementById('btnMarcarTodas').addEventListener('click', marcarTodasLidas);
        
        // Filtros
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filtroAtivo = this.dataset.filtro;
                renderizarNotificacoes();
            });
        });
        
        // Auto-refresh a cada 30 segundos
        setInterval(carregarNotificacoes, 30000);
        
        // Carregar ao iniciar
        carregarNotificacoes();
    </script>
</body>
</html>
