<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['fornecedor_id'])) {
    header('Location: ../user/login-unified.php');
    exit();
}

// Determine if user is a fornecedor or regular user
if (isset($_SESSION['fornecedor_id'])) {
    $id_fornecedor = $_SESSION['fornecedor_id'];
} elseif (isset($_SESSION['usuario_id'])) {
    // Check if this user is linked to a fornecedor account
    $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE email = (SELECT email FROM usuarios WHERE id_usuario = ?)");
    $stmt->execute([$_SESSION['usuario_id']]);
    $fornecedor_link = $stmt->fetch();
    
    if ($fornecedor_link) {
        $id_fornecedor = $fornecedor_link['id_fornecedor'];
    } else {
        // User is not a fornecedor, show message or redirect
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Acesso Negado</title></head><body>";
        echo "<h1>Acesso restrito</h1>";
        echo "<p>Esta página é apenas para fornecedores.</p>";
        echo "<a href='../index.php'>Voltar para início</a>";
        echo "</body></html>";
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}

// Verify fornecedor exists
$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
$stmt->execute([$id_fornecedor]);
$fornecedor = $stmt->fetch();

if (!$fornecedor) {
    header('Location: ../index.php');
    exit();
}

// Buscar disponibilidades
$stmt = $pdo->prepare("SELECT * FROM disponibilidade_fornecedor WHERE id_fornecedor = ? ORDER BY data_disponivel ASC");
$stmt->execute([$id_fornecedor]);
$disponibilidades = $stmt->fetchAll();

// Organizar por mês
$por_mes = [];
foreach ($disponibilidades as $disp) {
    $mes = date('Y-m', strtotime($disp['data_disponivel']));
    $por_mes[$mes][] = $disp;
}

$mes_atual = $_GET['mes'] ?? date('Y-m');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidade - WeddingEasy</title>
    <link rel="stylesheet" href="../Style/style.css">
    <link rel="stylesheet" href="../Style/responsive.css">
    <style>
        .calendario-container {
            max-width: 1200px;
            margin: 40px auto;
        }
        
        .calendario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .nav-mes {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .nav-mes button {
            padding: 8px 16px;
            background: #0084a0;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .mes-titulo {
            font-size: 24px;
            font-weight: 700;
        }
        
        .btn-add-disponibilidade {
            padding: 12px 24px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .dia-semana {
            text-align: center;
            font-weight: 600;
            color: #666;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .dia-calendario {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            padding: 8px;
        }
        
        .dia-calendario:hover {
            border-color: #0084a0;
            transform: scale(1.05);
        }
        
        .dia-calendario.disponivel {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .dia-calendario.indisponivel {
            background: #f8d7da;
            border-color: #dc3545;
        }
        
        .dia-calendario.parcial {
            background: #fff3cd;
            border-color: #ffc107;
        }
        
        .dia-calendario.passado {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .dia-numero {
            font-size: 18px;
            font-weight: 600;
        }
        
        .dia-status {
            font-size: 11px;
            margin-top: 4px;
            text-align: center;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.ativo {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .btn-fechar {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-salvar {
            padding: 12px 24px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-cancelar {
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-excluir {
            padding: 12px 24px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .legenda {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .legenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .legenda-cor {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .calendario-grid {
                gap: 5px;
            }
            
            .dia-numero {
                font-size: 14px;
            }
            
            .dia-status {
                font-size: 9px;
            }
            
            .legenda {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="container calendario-container">
        <h1>Gerenciar Disponibilidade</h1>
        
        <div class="calendario-header">
            <div class="nav-mes">
                <button onclick="navegarMes(-1)">← Anterior</button>
                <span class="mes-titulo" id="mes-titulo"><?php echo ucfirst(strftime('%B %Y', strtotime($mes_atual . '-01'))); ?></span>
                <button onclick="navegarMes(1)">Próximo →</button>
            </div>
            <button class="btn-add-disponibilidade" onclick="abrirModalData()">+ Adicionar Data</button>
        </div>
        
        <div class="calendario-grid" id="calendario">
            <!-- Cabeçalho da semana -->
            <div class="dia-semana">Dom</div>
            <div class="dia-semana">Seg</div>
            <div class="dia-semana">Ter</div>
            <div class="dia-semana">Qua</div>
            <div class="dia-semana">Qui</div>
            <div class="dia-semana">Sex</div>
            <div class="dia-semana">Sáb</div>
            
            <!-- Dias serão inseridos via JavaScript -->
        </div>
        
        <div class="legenda">
            <div class="legenda-item">
                <div class="legenda-cor" style="background: #d4edda; border: 2px solid #28a745;"></div>
                <span>Disponível</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background: #f8d7da; border: 2px solid #dc3545;"></div>
                <span>Indisponível</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background: #fff3cd; border: 2px solid #ffc107;"></div>
                <span>Parcialmente</span>
            </div>
        </div>
    </div>
    
    <!-- Modal de Disponibilidade -->
    <div class="modal" id="modal-disponibilidade">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Definir Disponibilidade</h3>
                <button class="btn-fechar" onclick="fecharModal()">×</button>
            </div>
            
            <form id="form-disponibilidade" method="POST" action="../api/disponibilidade.php">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id_fornecedor" value="<?php echo $id_fornecedor; ?>">
                <input type="hidden" name="id_disponibilidade" id="id_disponibilidade">
                
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_disponivel" id="data_disponivel" required>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" required>
                        <option value="disponivel">Disponível</option>
                        <option value="indisponivel">Indisponível</option>
                        <option value="parcial">Parcialmente Disponível</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Horário Início (opcional)</label>
                    <input type="time" name="horario_inicio" id="horario_inicio">
                </div>
                
                <div class="form-group">
                    <label>Horário Fim (opcional)</label>
                    <input type="time" name="horario_fim" id="horario_fim">
                </div>
                
                <div class="form-group">
                    <label>Observações (opcional)</label>
                    <textarea name="observacoes" id="observacoes" rows="3" placeholder="Ex: Apenas pela manhã, evento confirmado, etc."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-excluir" id="btn-excluir" onclick="excluirDisponibilidade()" style="display: none;">Excluir</button>
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        const disponibilidades = <?php echo json_encode($disponibilidades); ?>;
        let mesAtual = '<?php echo $mes_atual; ?>';
        
        function renderizarCalendario() {
            const [ano, mes] = mesAtual.split('-').map(Number);
            const primeiroDia = new Date(ano, mes - 1, 1);
            const ultimoDia = new Date(ano, mes, 0);
            const diasNoMes = ultimoDia.getDate();
            const diaSemanaInicio = primeiroDia.getDay();
            
            const calendario = document.getElementById('calendario');
            
            // Manter cabeçalho da semana
            while (calendario.children.length > 7) {
                calendario.removeChild(calendario.lastChild);
            }
            
            // Dias vazios antes do início do mês
            for (let i = 0; i < diaSemanaInicio; i++) {
                const divVazio = document.createElement('div');
                calendario.appendChild(divVazio);
            }
            
            // Dias do mês
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            
            for (let dia = 1; dia <= diasNoMes; dia++) {
                const data = new Date(ano, mes - 1, dia);
                const dataStr = `${ano}-${String(mes).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                const passado = data < hoje;
                
                const disp = disponibilidades.find(d => d.data_disponivel === dataStr);
                
                const divDia = document.createElement('div');
                divDia.className = 'dia-calendario';
                if (passado) divDia.classList.add('passado');
                if (disp) divDia.classList.add(disp.status);
                
                divDia.innerHTML = `
                    <div class="dia-numero">${dia}</div>
                    ${disp ? `<div class="dia-status">${disp.status === 'disponivel' ? 'Disponível' : disp.status === 'indisponivel' ? 'Indisponível' : 'Parcial'}</div>` : ''}
                `;
                
                if (!passado) {
                    divDia.onclick = () => abrirModal(dataStr, disp);
                }
                
                calendario.appendChild(divDia);
            }
            
            // Atualizar título
            const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                          'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            document.getElementById('mes-titulo').textContent = `${meses[mes - 1]} ${ano}`;
        }
        
        function navegarMes(direcao) {
            const [ano, mes] = mesAtual.split('-').map(Number);
            const novaData = new Date(ano, mes - 1 + direcao, 1);
            mesAtual = `${novaData.getFullYear()}-${String(novaData.getMonth() + 1).padStart(2, '0')}`;
            window.location.href = `?mes=${mesAtual}`;
        }
        
        function abrirModalData() {
            abrirModal(null, null);
        }
        
        function abrirModal(data, disp) {
            const modal = document.getElementById('modal-disponibilidade');
            const form = document.getElementById('form-disponibilidade');
            
            form.reset();
            document.getElementById('id_disponibilidade').value = '';
            document.getElementById('btn-excluir').style.display = 'none';
            
            if (data) {
                document.getElementById('data_disponivel').value = data;
            }
            
            if (disp) {
                document.getElementById('id_disponibilidade').value = disp.id_disponibilidade;
                document.getElementById('data_disponivel').value = disp.data_disponivel;
                document.getElementById('status').value = disp.status;
                document.getElementById('horario_inicio').value = disp.horario_inicio || '';
                document.getElementById('horario_fim').value = disp.horario_fim || '';
                document.getElementById('observacoes').value = disp.observacoes || '';
                document.getElementById('btn-excluir').style.display = 'block';
            }
            
            modal.classList.add('ativo');
        }
        
        function fecharModal() {
            document.getElementById('modal-disponibilidade').classList.remove('ativo');
        }
        
        function excluirDisponibilidade() {
            if (!confirm('Deseja realmente excluir esta disponibilidade?')) return;
            
            const id = document.getElementById('id_disponibilidade').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../api/disponibilidade.php';
            
            const acaoInput = document.createElement('input');
            acaoInput.type = 'hidden';
            acaoInput.name = 'acao';
            acaoInput.value = 'excluir';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id_disponibilidade';
            idInput.value = id;
            
            form.appendChild(acaoInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Renderizar ao carregar
        renderizarCalendario();
        
        // Fechar modal ao clicar fora
        document.getElementById('modal-disponibilidade').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>
</body>
</html>
