<?php
session_start();
require_once '../config/conexao.php';

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
    <title>Configurar Orçamento - Wedding Easy</title>
    <link rel="stylesheet" href="../Style/style.css">
    <style>
        .config-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .config-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .config-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .config-header p {
            color: #666;
            font-size: 14px;
        }
        
        .config-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
        }
        
        .config-section {
            margin-bottom: 30px;
        }
        
        .config-section:last-child {
            margin-bottom: 0;
        }
        
        .config-section h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-group input[type="number"]:focus {
            outline: none;
            border-color: #0084a0;
        }
        
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .checkbox-item:hover {
            background: #f0f0f0;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-label {
            flex: 1;
            cursor: pointer;
        }
        
        .checkbox-label strong {
            display: block;
            color: #333;
            margin-bottom: 3px;
        }
        
        .checkbox-label span {
            display: block;
            font-size: 13px;
            color: #666;
        }
        
        .alerta-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .alerta-badge.info { background: #e3f2fd; color: #1976d2; }
        .alerta-badge.warning { background: #fff3e0; color: #f57c00; }
        .alerta-badge.danger { background: #ffebee; color: #d32f2f; }
        .alerta-badge.critical { background: #fce4ec; color: #c2185b; }
        
        .btn-salvar {
            width: 100%;
            padding: 15px;
            background: #0084a0;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-salvar:hover {
            background: #006d85;
        }
        
        .btn-salvar:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .mensagem-sucesso {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .resumo-atual {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .resumo-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .resumo-item:last-child {
            border-bottom: none;
        }
        
        .resumo-label {
            color: #666;
            font-size: 14px;
        }
        
        .resumo-valor {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .resumo-valor.positivo {
            color: #4caf50;
        }
        
        .resumo-valor.negativo {
            color: #f44336;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s, background 0.3s;
        }
        
        .progress-fill.safe { background: #4caf50; }
        .progress-fill.warning { background: #ff9800; }
        .progress-fill.danger { background: #f44336; }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="config-container">
        <div class="config-header">
            <h1>Configurar Alertas de Orçamento</h1>
            <p>Defina seu orçamento total e configure alertas para quando atingir determinados percentuais de gasto</p>
        </div>
        
        <div class="mensagem-sucesso" id="mensagemSucesso">
            Configuração salva com sucesso!
        </div>
        
        <form id="formConfiguracao">
            <div class="config-card">
                <div class="config-section">
                    <h2>Orçamento Total</h2>
                    <div class="form-group">
                        <label for="orcamento_total">Valor Total do Orçamento (R$)</label>
                        <input type="number" 
                               id="orcamento_total" 
                               name="orcamento_total" 
                               step="0.01" 
                               min="0" 
                               placeholder="Ex: 50000.00"
                               required>
                    </div>
                </div>
                
                <div class="config-section">
                    <h2>Alertas Automáticos</h2>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                        Selecione em quais percentuais você deseja receber notificações
                    </p>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" id="alerta_50" name="alerta_50" checked>
                            <div class="checkbox-label">
                                <strong>50% do orçamento <span class="alerta-badge info">Informativo</span></strong>
                                <span>Você será notificado quando atingir metade do orçamento</span>
                            </div>
                        </label>
                        
                        <label class="checkbox-item">
                            <input type="checkbox" id="alerta_75" name="alerta_75" checked>
                            <div class="checkbox-label">
                                <strong>75% do orçamento <span class="alerta-badge warning">Atenção</span></strong>
                                <span>Alerta quando começar a se aproximar do limite</span>
                            </div>
                        </label>
                        
                        <label class="checkbox-item">
                            <input type="checkbox" id="alerta_90" name="alerta_90" checked>
                            <div class="checkbox-label">
                                <strong>90% do orçamento <span class="alerta-badge danger">Cuidado</span></strong>
                                <span>Aviso importante quando estiver próximo do limite</span>
                            </div>
                        </label>
                        
                        <label class="checkbox-item">
                            <input type="checkbox" id="alerta_100" name="alerta_100" checked>
                            <div class="checkbox-label">
                                <strong>100% do orçamento <span class="alerta-badge critical">Crítico</span></strong>
                                <span>Alerta crítico quando atingir ou ultrapassar o orçamento</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn-salvar" id="btnSalvar">
                    Salvar Configuração
                </button>
            </div>
        </form>
        
        <div class="config-card">
            <div class="config-section">
                <h2>Resumo Atual</h2>
                <div class="resumo-atual" id="resumoAtual">
                    <div class="resumo-item">
                        <span class="resumo-label">Orçamento Total</span>
                        <span class="resumo-valor" id="resumo-total">R$ 0,00</span>
                    </div>
                    <div class="resumo-item">
                        <span class="resumo-label">Total Gasto</span>
                        <span class="resumo-valor" id="resumo-gasto">R$ 0,00</span>
                    </div>
                    <div class="resumo-item">
                        <span class="resumo-label">Disponível</span>
                        <span class="resumo-valor positivo" id="resumo-disponivel">R$ 0,00</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill safe" id="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        // Carregar configuração atual
        function carregarConfiguracao() {
            fetch('../api/alertas_orcamento.php?acao=obter_configuracao')
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        const config = data.configuracao;
                        document.getElementById('orcamento_total').value = config.orcamento_total || '';
                        document.getElementById('alerta_50').checked = config.alerta_50 == 1;
                        document.getElementById('alerta_75').checked = config.alerta_75 == 1;
                        document.getElementById('alerta_90').checked = config.alerta_90 == 1;
                        document.getElementById('alerta_100').checked = config.alerta_100 == 1;
                        
                        atualizarResumo();
                    }
                })
                .catch(err => console.error('[v0] Erro ao carregar configuração:', err));
        }
        
        // Atualizar resumo
        function atualizarResumo() {
            fetch('../api/alertas_orcamento.php?acao=verificar_alertas')
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        const total = data.orcamento_total || 0;
                        const gasto = data.total_gasto || 0;
                        const disponivel = total - gasto;
                        const percentual = data.percentual_gasto || 0;
                        
                        document.getElementById('resumo-total').textContent = 
                            'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        document.getElementById('resumo-gasto').textContent = 
                            'R$ ' + gasto.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        
                        const disponivelEl = document.getElementById('resumo-disponivel');
                        disponivelEl.textContent = 
                            'R$ ' + disponivel.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        
                        disponivelEl.classList.remove('positivo', 'negativo');
                        if (disponivel >= 0) {
                            disponivelEl.classList.add('positivo');
                        } else {
                            disponivelEl.classList.add('negativo');
                        }
                        
                        const progressFill = document.getElementById('progress-fill');
                        progressFill.style.width = Math.min(100, percentual) + '%';
                        
                        progressFill.classList.remove('safe', 'warning', 'danger');
                        if (percentual < 50) {
                            progressFill.classList.add('safe');
                        } else if (percentual < 90) {
                            progressFill.classList.add('warning');
                        } else {
                            progressFill.classList.add('danger');
                        }
                    }
                })
                .catch(err => console.error('[v0] Erro ao atualizar resumo:', err));
        }
        
        // Salvar configuração
        document.getElementById('formConfiguracao').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'salvar_configuracao');
            
            const btnSalvar = document.getElementById('btnSalvar');
            btnSalvar.disabled = true;
            btnSalvar.textContent = 'Salvando...';
            
            fetch('../api/alertas_orcamento.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    const mensagem = document.getElementById('mensagemSucesso');
                    mensagem.style.display = 'block';
                    
                    setTimeout(() => {
                        mensagem.style.display = 'none';
                    }, 3000);
                    
                    atualizarResumo();
                }
            })
            .catch(err => console.error('[v0] Erro ao salvar:', err))
            .finally(() => {
                btnSalvar.disabled = false;
                btnSalvar.textContent = 'Salvar Configuração';
            });
        });
        
        // Atualizar resumo ao mudar valor do orçamento
        document.getElementById('orcamento_total').addEventListener('input', function() {
            setTimeout(atualizarResumo, 500);
        });
        
        // Carregar ao iniciar
        carregarConfiguracao();
    </script>
</body>
</html>
