<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['fornecedor_id'])) {
    header('Location: ../user/login-unified.php');
    exit();
}

$id_usuario = $_SESSION['usuario_id'] ?? null;
if ($id_usuario === null) {
    // Se for fornecedor, redireciona para sua dashboard
    if (isset($_SESSION['fornecedor_id'])) {
        header('Location: ../supplier/dashboard.php');
        exit();
    }
    header('Location: ../user/login-unified.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Interações - Wedding Easy</title>
    <link rel="stylesheet" href="../Style/styles.css">
    <style>
        .historico-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .historico-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            margin-top: 2rem;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .historico-header h1 {
            font-size: 28px;
            color: #333;
        }
        
        .filtros-historico {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
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
            background: hsl(var(--primary));
            color: white;
            border-color: hsl(var(--primary));
        }
        
        .busca-historico {
            margin-bottom: 25px;
        }
        
        .busca-historico input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, hsl(var(--primary)), #e0e0e0);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .timeline-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -28px;
            top: 20px;
            width: 16px;
            height: 16px;
            background: white;
            border: 3px solid hsl(var(--primary));
            border-radius: 50%;
            z-index: 1;
        }
        
        .timeline-badge {
            position: absolute;
            left: -40px;
            top: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 2;
        }
        
        .timeline-badge.mensagem { background: #e3f2fd; }
        .timeline-badge.contrato { background: #e8f5e9; }
        .timeline-badge.avaliacao { background: #fff3e0; }
        .timeline-badge.reuniao { background: #f3e5f5; }
        .timeline-badge.evento { background: #fce4ec; }
        .timeline-badge.candidatura { background: #e0f2f1; }
        
        .timeline-header {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .timeline-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e0e0e0;
            flex-shrink: 0;
        }
        
        .timeline-info {
            flex: 1;
            min-width: 0;
        }
        
        .timeline-usuario {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            margin-bottom: 3px;
        }
        
        .timeline-tipo {
            display: inline-block;
            padding: 3px 10px;
            background: #e8f4f8;
            color: hsl(var(--primary));
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-right: 8px;
        }
        
        .timeline-data {
            font-size: 13px;
            color: #999;
        }
        
        .timeline-descricao {
            color: #666;
            line-height: 1.6;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .timeline-detalhes {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #555;
        }
        
        .timeline-detalhes strong {
            color: #333;
        }
        
        .timeline-acao {
            margin-top: 12px;
        }
        
        .btn-ver-detalhes {
            background: hsl(var(--primary));
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-ver-detalhes:hover {
            background: #006d85;
        }
        
        .sem-resultados {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .sem-resultados-icone {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .estatisticas-historico {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .estatistica-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .estatistica-numero {
            font-size: 32px;
            font-weight: 700;
            color: hsl(var(--primary));
            margin-bottom: 5px;
        }
        
        .estatistica-label {
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="historico-container">
        <div class="historico-header">
            <h1>Histórico de Interações</h1>
        </div>
        
        <div class="estatisticas-historico" id="estatisticas">
            <div class="estatistica-card">
                <div class="estatistica-numero" id="total-interacoes">0</div>
                <div class="estatistica-label">Total de Interações</div>
            </div>
            <div class="estatistica-card">
                <div class="estatistica-numero" id="total-usuarios">0</div>
                <div class="estatistica-label">Usuários Conectados</div>
            </div>
            <div class="estatistica-card">
                <div class="estatistica-numero" id="total-mes">0</div>
                <div class="estatistica-label">Este Mês</div>
            </div>
        </div>
        
        <div class="busca-historico">
            <input type="text" id="buscaHistorico" placeholder="Buscar por usuário ou descrição...">
        </div>
        
        <div class="filtros-historico">
            <button class="filtro-btn active" data-filtro="todas">Todas</button>
            <button class="filtro-btn" data-filtro="mensagem">Mensagens</button>
            <button class="filtro-btn" data-filtro="contrato">Contratos</button>
            <button class="filtro-btn" data-filtro="avaliacao">Avaliações</button>
            <button class="filtro-btn" data-filtro="reuniao">Reuniões</button>
            <button class="filtro-btn" data-filtro="evento">Eventos</button>
            <button class="filtro-btn" data-filtro="candidatura">Candidaturas</button>
        </div>
        
        <div id="timeline" class="timeline loading">Carregando histórico...</div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        let todasInteracoes = [];
        let filtroAtivo = 'todas';
        
        const iconesInteracao = {
            'mensagem': '✉️',
            'contrato': '📋',
            'avaliacao': '⭐',
            'reuniao': '📅',
            'evento': '🎉',
            'candidatura': '👤',
            'default': '💼'
        };
        
        const tiposInteracao = {
            'mensagem': 'Mensagem',
            'contrato': 'Contrato',
            'avaliacao': 'Avaliação',
            'reuniao': 'Reunião',
            'evento': 'Evento',
            'candidatura': 'Candidatura'
        };
        
        // Carregar histórico
        function carregarHistorico() {
            fetch('../api/historico.php?acao=listar')
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        todasInteracoes = data.interacoes;
                        renderizarHistorico();
                        atualizarEstatisticas();
                    }
                })
                .catch(err => {
                    console.error('[v0] Erro ao carregar histórico:', err);
                    document.getElementById('timeline').innerHTML = 
                        '<div class="sem-resultados">Erro ao carregar histórico</div>';
                });
        }
        
        // Renderizar histórico
        function renderizarHistorico() {
            const timeline = document.getElementById('timeline');
            const busca = document.getElementById('buscaHistorico').value.toLowerCase();
            
            // Filtrar interações
            let interacoesFiltradas = todasInteracoes;
            
            // Filtro por tipo
            if (filtroAtivo !== 'todas') {
                interacoesFiltradas = interacoesFiltradas.filter(i => i.tipo_interacao === filtroAtivo);
            }
            
            // Filtro por busca
            if (busca) {
                interacoesFiltradas = interacoesFiltradas.filter(i => 
                    i.outro_usuario_nome?.toLowerCase().includes(busca) ||
                    i.descricao?.toLowerCase().includes(busca)
                );
            }
            
            if (interacoesFiltradas.length === 0) {
                timeline.innerHTML = `
                    <div class="sem-resultados">
                        <div class="sem-resultados-icone">📭</div>
                        <p>Nenhuma interação encontrada</p>
                    </div>
                `;
                return;
            }
            
            timeline.innerHTML = interacoesFiltradas.map(interacao => {
                const icone = iconesInteracao[interacao.tipo_interacao] || iconesInteracao.default;
                const tipo = tiposInteracao[interacao.tipo_interacao] || interacao.tipo_interacao;
                const dataFormatada = formatarData(interacao.data_interacao);
                const fotoUsuario = interacao.outro_usuario_foto || 'default.png';
                
                let linkDetalhes = '#';
                if (interacao.referencia_id && interacao.referencia_tabela) {
                    switch(interacao.referencia_tabela) {
                        case 'mensagens':
                            linkDetalhes = `mensagens.php?conversa=${interacao.referencia_id}`;
                            break;
                        case 'contratos':
                            linkDetalhes = `contratos.php?id=${interacao.referencia_id}`;
                            break;
                        case 'avaliacoes':
                            linkDetalhes = `avaliacoes.php#avaliacao-${interacao.referencia_id}`;
                            break;
                        case 'candidaturas_fornecedor':
                            linkDetalhes = `candidaturas.php?id=${interacao.referencia_id}`;
                            break;
                    }
                }
                
                return `
                    <div class="timeline-item">
                        <div class="timeline-badge ${interacao.tipo_interacao}">${icone}</div>
                        <div class="timeline-header">
                            <img src="../user/fotos/${fotoUsuario}" 
                                 alt="${interacao.outro_usuario_nome || 'Usuário'}" 
                                 class="timeline-avatar"
                                 onerror="this.src='../user/fotos/default.png'">
                            <div class="timeline-info">
                                <div class="timeline-usuario">${interacao.outro_usuario_nome || 'Usuário Desconhecido'}</div>
                                <div>
                                    <span class="timeline-tipo">${tipo}</span>
                                    <span class="timeline-data">${dataFormatada}</span>
                                </div>
                            </div>
                        </div>
                        
                        ${interacao.descricao ? `<div class="timeline-descricao">${interacao.descricao}</div>` : ''}
                        
                        ${interacao.detalhes ? `
                            <div class="timeline-detalhes">
                                ${formatarDetalhes(interacao.detalhes)}
                            </div>
                        ` : ''}
                        
                        ${linkDetalhes !== '#' ? `
                            <div class="timeline-acao">
                                <a href="${linkDetalhes}" class="btn-ver-detalhes">Ver Detalhes</a>
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            timeline.classList.remove('loading');
        }
        
        // Formatar detalhes JSON
        function formatarDetalhes(detalhesJson) {
            try {
                const detalhes = JSON.parse(detalhesJson);
                return Object.entries(detalhes)
                    .map(([chave, valor]) => `<strong>${chave}:</strong> ${valor}`)
                    .join(' | ');
            } catch {
                return detalhesJson;
            }
        }
        
        // Atualizar estatísticas
        function atualizarEstatisticas() {
            document.getElementById('total-interacoes').textContent = todasInteracoes.length;
            
            // Contar usuários únicos
            const usuariosUnicos = new Set(todasInteracoes.map(i => i.outro_usuario_id).filter(Boolean));
            document.getElementById('total-usuarios').textContent = usuariosUnicos.size;
            
            // Contar interações deste mês
            const mesAtual = new Date().getMonth();
            const anoAtual = new Date().getFullYear();
            const interacoesMes = todasInteracoes.filter(i => {
                const data = new Date(i.data_interacao);
                return data.getMonth() === mesAtual && data.getFullYear() === anoAtual;
            });
            document.getElementById('total-mes').textContent = interacoesMes.length;
        }
        
        // Formatar data
        function formatarData(dataString) {
            const data = new Date(dataString);
            const agora = new Date();
            const diffMs = agora - data;
            const diffDias = Math.floor(diffMs / 86400000);
            
            if (diffDias === 0) {
                return 'Hoje às ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            } else if (diffDias === 1) {
                return 'Ontem às ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            } else if (diffDias < 7) {
                return `${diffDias} dias atrás`;
            }
            
            return data.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Event listeners
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filtroAtivo = this.dataset.filtro;
                renderizarHistorico();
            });
        });
        
        document.getElementById('buscaHistorico').addEventListener('input', renderizarHistorico);
        
        // Carregar ao iniciar
        carregarHistorico();
    </script>
</body>
</html>
