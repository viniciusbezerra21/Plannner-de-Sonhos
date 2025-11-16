<?php
session_start();
require_once "../config/conexao.php";

// Check if user is logged in and is a ceremonialista
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'cerimonialista') {
    header("Location: ../user/login-unified.php?type=cerimonialista");
    exit;
}

$id_cerimonialista = $_SESSION['usuario_id'];
$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

// Fetch blocked dates
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(data_bloqueada, '%d') as dia, motivo
    FROM cerimonialista_datas_bloqueadas
    WHERE id_cerimonialista = ?
    AND MONTH(data_bloqueada) = ?
    AND YEAR(data_bloqueada) = ?
");
$stmt->execute([$id_cerimonialista, $mes, $ano]);
$datas_bloqueadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to array for easier checking
$dias_bloqueados = [];
foreach ($datas_bloqueadas as $data) {
    $dias_bloqueados[$data['dia']] = $data['motivo'];
}

// Handle block/unblock date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $data = $_POST['data'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    
    if ($acao === 'bloquear') {
        $stmt = $pdo->prepare("
            INSERT INTO cerimonialista_datas_bloqueadas (id_cerimonialista, data_bloqueada, motivo)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE motivo = ?
        ");
        $stmt->execute([$id_cerimonialista, $data, $motivo, $motivo]);
    } elseif ($acao === 'desbloquear') {
        $stmt = $pdo->prepare("
            DELETE FROM cerimonialista_datas_bloqueadas
            WHERE id_cerimonialista = ? AND data_bloqueada = ?
        ");
        $stmt->execute([$id_cerimonialista, $data]);
    }
    
    header("Location: ?mes=$mes&ano=$ano");
    exit;
}

// Fetch upcoming events for this ceremonialista
$stmt = $pdo->prepare("
    SELECT cc.data_casamento, cc.local_casamento, cc.tipo_cerimonia, cc.quantidade_convidados,
           u.nome as nome_cliente, u.email, u.telefone
    FROM cliente_cerimonialista cc
    INNER JOIN usuarios u ON cc.id_cliente = u.id_usuario
    WHERE cc.id_cerimonialista = ?
    AND cc.status = 'ativo'
    AND cc.data_casamento >= CURDATE()
    ORDER BY cc.data_casamento ASC
");
$stmt->execute([$id_cerimonialista]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Calendário de Disponibilidade - Planner de Sonhos</title>
    <link rel="stylesheet" href="../Style/styles.css" />
    <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
    <style>
        .calendario-container {
            max-width: 1200px;
            margin: auto;
            padding: 2rem 0;
        }

        .calendario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .calendario-header h1 {
            font-size: 1.75rem;
            color: hsl(var(--foreground));
            margin: 0;
        }

        .mes-seletor {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .mes-seletor select {
            padding: 0.5rem;
            border: 1px solid hsl(var(--border));
            border-radius: 0.5rem;
            background: hsl(var(--background));
            color: hsl(var(--foreground));
            cursor: pointer;
        }

        .mes-seletor button {
            padding: 0.5rem 1rem;
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
        }

        .calendario-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .calendario-grid {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .calendario-mes {
            font-size: 1.25rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            color: hsl(var(--foreground));
        }

        .dias-semana {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .dia-semana {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem;
            color: hsl(var(--muted-foreground));
        }

        .dias {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .dia {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid hsl(var(--border));
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            background: hsl(var(--background));
            color: hsl(var(--foreground));
            position: relative;
        }

        .dia:hover {
            background: hsl(var(--muted));
        }

        .dia.bloqueado {
            background: #fee2e2;
            border-color: #fca5a5;
            font-weight: 600;
        }

        .dia.bloqueado::after {
            content: '✕';
            position: absolute;
            top: -8px;
            right: -8px;
            background: #f87171;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .dia.vazio {
            background: transparent;
            border: none;
            cursor: default;
        }

        .eventos-list {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .eventos-list h3 {
            margin-top: 0;
            color: hsl(var(--foreground));
        }

        .evento-item {
            background: hsl(var(--muted));
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .evento-item:last-child {
            margin-bottom: 0;
        }

        .evento-data {
            font-weight: 600;
            color: hsl(var(--primary));
            margin-bottom: 0.5rem;
        }

        .evento-cliente {
            font-size: 0.9rem;
            color: hsl(var(--foreground));
            margin-bottom: 0.25rem;
        }

        .evento-detalhes {
            font-size: 0.85rem;
            color: hsl(var(--muted-foreground));
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: hsl(var(--card));
            border-radius: 1rem;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
        }

        .modal-close {
            float: right;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: hsl(var(--foreground));
        }

        .modal-content h3 {
            clear: both;
            margin-top: 0;
        }

        .input-group {
            margin-bottom: 1rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: hsl(var(--foreground));
        }

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid hsl(var(--border));
            border-radius: 0.5rem;
            box-sizing: border-box;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .modal-actions button {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-confirmar {
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
        }

        .btn-cancelar {
            background: hsl(var(--muted));
            color: hsl(var(--foreground));
        }

        @media (max-width: 768px) {
            .calendario-content {
                grid-template-columns: 1fr;
            }

            .calendario-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <div class="heart-icon">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                    </div>
                    <span class="logo-text">Planner de Sonhos</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="page-content" style="padding-top: 4rem">
            <div class="container">
                <div class="calendario-container">
                    <div class="calendario-header">
                        <h1>Meu Calendário de Disponibilidade</h1>
                        <div class="mes-seletor">
                            <select id="mesSeletor" onchange="atualizarMes()">
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $mes ? 'selected' : ''; ?>>
                                        <?php echo strftime('%B', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select id="anoSeletor" onchange="atualizarMes()">
                                <?php for($i = date('Y'); $i <= date('Y') + 2; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $ano ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="calendario-content">
                        <div class="calendario-grid">
                            <div class="calendario-mes">
                                <?php echo strftime('%B de %Y', mktime(0, 0, 0, $mes, 1, $ano)); ?>
                            </div>

                            <div class="dias-semana">
                                <div class="dia-semana">Dom</div>
                                <div class="dia-semana">Seg</div>
                                <div class="dia-semana">Ter</div>
                                <div class="dia-semana">Qua</div>
                                <div class="dia-semana">Qui</div>
                                <div class="dia-semana">Sex</div>
                                <div class="dia-semana">Sab</div>
                            </div>

                            <div class="dias">
                                <?php
                                $primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
                                $ultimo_dia = date('t', $primeiro_dia);
                                $dia_semana = date('w', $primeiro_dia);

                                // Dias vazios no início
                                for ($i = 0; $i < $dia_semana; $i++) {
                                    echo '<div class="dia vazio"></div>';
                                }

                                // Dias do mês
                                for ($dia = 1; $dia <= $ultimo_dia; $dia++) {
                                    $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                                    $bloqueado = isset($dias_bloqueados[str_pad($dia, 2, '0', STR_PAD_LEFT)]);
                                    $classes = 'dia ' . ($bloqueado ? 'bloqueado' : '');
                                    echo '<div class="' . $classes . '" onclick="abrirModal(\'' . $data . '\', ' . ($bloqueado ? 'true' : 'false') . ')">';
                                    echo $dia;
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="eventos-list">
                            <h3>Próximos Casamentos</h3>
                            <?php if (!empty($eventos)): ?>
                                <?php foreach ($eventos as $evento): ?>
                                    <div class="evento-item">
                                        <div class="evento-data">
                                            <?php echo date('d/m/Y', strtotime($evento['data_casamento'])); ?>
                                        </div>
                                        <div class="evento-cliente">
                                            <?php echo htmlspecialchars($evento['nome_cliente']); ?>
                                        </div>
                                        <div class="evento-detalhes">
                                            Local: <?php echo htmlspecialchars($evento['local_casamento']); ?><br>
                                            Tipo: <?php echo htmlspecialchars($evento['tipo_cerimonia']); ?><br>
                                            Convidados: <?php echo $evento['quantidade_convidados']; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: hsl(var(--muted-foreground)); text-align: center;">Nenhum casamento agendado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="modal" id="dataModal">
        <div class="modal-content">
            <button class="modal-close" onclick="fecharModal()">&times;</button>
            <h3 id="modalTitulo">Gerenciar Disponibilidade</h3>
            <form id="formData" method="POST">
                <input type="hidden" name="data" id="inputData">
                <input type="hidden" name="acao" id="inputAcao">

                <div class="input-group">
                    <label>Data</label>
                    <input type="text" id="displayData" readonly>
                </div>

                <div class="input-group" id="grupMotivo" style="display: none;">
                    <label>Motivo (opcional)</label>
                    <textarea name="motivo" id="inputMotivo" placeholder="Ex: Compromisso pessoal"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-confirmar" id="btnConfirmar">Bloquear</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let dataSelecionada = null;
        let jaEstaBloqueada = false;

        function abrirModal(data, bloqueado) {
            dataSelecionada = data;
            jaEstaBloqueada = bloqueado;

            const [ano, mes, dia] = data.split('-');
            const dataFormatada = dia + '/' + mes + '/' + ano;

            document.getElementById('inputData').value = data;
            document.getElementById('displayData').value = dataFormatada;
            document.getElementById('inputMotivo').value = '';

            const modal = document.getElementById('dataModal');
            const btnConfirmar = document.getElementById('btnConfirmar');
            const modalTitulo = document.getElementById('modalTitulo');
            const grupMotivo = document.getElementById('grupMotivo');

            if (bloqueado) {
                document.getElementById('inputAcao').value = 'desbloquear';
                btnConfirmar.textContent = 'Desbloquear';
                btnConfirmar.className = 'modal-actions button btn-confirmar';
                modalTitulo.textContent = 'Desbloquear Data';
                grupMotivo.style.display = 'none';
            } else {
                document.getElementById('inputAcao').value = 'bloquear';
                btnConfirmar.textContent = 'Bloquear';
                btnConfirmar.className = 'modal-actions button btn-confirmar';
                modalTitulo.textContent = 'Bloquear Data';
                grupMotivo.style.display = 'block';
            }

            modal.classList.add('active');
        }

        function fecharModal() {
            document.getElementById('dataModal').classList.remove('active');
        }

        function atualizarMes() {
            const mes = document.getElementById('mesSeletor').value;
            const ano = document.getElementById('anoSeletor').value;
            window.location.href = '?mes=' + mes + '&ano=' + ano;
        }

        document.getElementById('formData').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    </script>
</body>
</html>
