<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';
    
    if ($acao === 'obter_configuracao') {
        $stmt = $pdo->prepare("
            SELECT * FROM configuracoes_orcamento 
            WHERE id_usuario = ?
        ");
        $stmt->execute([$id_usuario]);
        $config = $stmt->fetch();
        
        if (!$config) {
            $config = [
                'orcamento_total' => 0,
                'alerta_50' => 1,
                'alerta_75' => 1,
                'alerta_90' => 1,
                'alerta_100' => 1
            ];
        }
        
        echo json_encode(['sucesso' => true, 'configuracao' => $config]);
    }
    
    elseif ($acao === 'verificar_alertas') {
        $stmt = $pdo->prepare("
            SELECT co.*, 
                   (SELECT COALESCE(SUM(quantidade * valor_unitario), 0) 
                    FROM orcamentos 
                    WHERE id_usuario = ?) as total_gasto
            FROM configuracoes_orcamento co
            WHERE co.id_usuario = ?
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $config = $stmt->fetch();
        
        if (!$config || $config['orcamento_total'] <= 0) {
            echo json_encode(['sucesso' => true, 'alertas' => []]);
            exit();
        }
        
        $percentual_gasto = ($config['total_gasto'] / $config['orcamento_total']) * 100;
        $alertas = [];
        
        if ($config['alerta_50'] == 1 && $percentual_gasto >= 50 && $percentual_gasto < 75) {
            $alertas[] = [
                'nivel' => '50',
                'mensagem' => 'Atenção: Você já utilizou 50% do seu orçamento!',
                'percentual' => $percentual_gasto,
                'tipo' => 'info'
            ];
        }
        
        if ($config['alerta_75'] == 1 && $percentual_gasto >= 75 && $percentual_gasto < 90) {
            $alertas[] = [
                'nivel' => '75',
                'mensagem' => 'Cuidado: Você já utilizou 75% do seu orçamento!',
                'percentual' => $percentual_gasto,
                'tipo' => 'warning'
            ];
        }
        
        if ($config['alerta_90'] == 1 && $percentual_gasto >= 90 && $percentual_gasto < 100) {
            $alertas[] = [
                'nivel' => '90',
                'mensagem' => 'Alerta: Você já utilizou 90% do seu orçamento!',
                'percentual' => $percentual_gasto,
                'tipo' => 'danger'
            ];
        }
        
        if ($config['alerta_100'] == 1 && $percentual_gasto >= 100) {
            $alertas[] = [
                'nivel' => '100',
                'mensagem' => 'ATENÇÃO: Você excedeu seu orçamento!',
                'percentual' => $percentual_gasto,
                'tipo' => 'critical'
            ];
        }
        
        echo json_encode([
            'sucesso' => true, 
            'alertas' => $alertas,
            'orcamento_total' => $config['orcamento_total'],
            'total_gasto' => $config['total_gasto'],
            'percentual_gasto' => $percentual_gasto
        ]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'salvar_configuracao') {
        $orcamento_total = (float)str_replace(',', '.', $_POST['orcamento_total'] ?? 0);
        $alerta_50 = isset($_POST['alerta_50']) ? 1 : 0;
        $alerta_75 = isset($_POST['alerta_75']) ? 1 : 0;
        $alerta_90 = isset($_POST['alerta_90']) ? 1 : 0;
        $alerta_100 = isset($_POST['alerta_100']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            SELECT id_configuracao FROM configuracoes_orcamento 
            WHERE id_usuario = ?
        ");
        $stmt->execute([$id_usuario]);
        $existe = $stmt->fetch();
        
        if ($existe) {
            $stmt = $pdo->prepare("
                UPDATE configuracoes_orcamento 
                SET orcamento_total = ?, alerta_50 = ?, alerta_75 = ?, alerta_90 = ?, alerta_100 = ?
                WHERE id_usuario = ?
            ");
            $stmt->execute([$orcamento_total, $alerta_50, $alerta_75, $alerta_90, $alerta_100, $id_usuario]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes_orcamento 
                (id_usuario, orcamento_total, alerta_50, alerta_75, alerta_90, alerta_100)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_usuario, $orcamento_total, $alerta_50, $alerta_75, $alerta_90, $alerta_100]);
        }
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Configuração salva com sucesso']);
    }
    
    elseif ($acao === 'criar_notificacao_alerta') {
        $nivel = $_POST['nivel'];
        $mensagem = $_POST['mensagem'];
        $percentual = (float)$_POST['percentual'];
        
        $stmt = $pdo->prepare("
            SELECT id_notificacao FROM notificacoes
            WHERE usuario_id = ? 
            AND tipo = 'sistema'
            AND titulo LIKE '%orçamento%'
            AND data_criacao > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$id_usuario]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes 
                (usuario_id, tipo, titulo, descricao, referencia_tabela, lida)
                VALUES (?, 'sistema', ?, ?, 'configuracoes_orcamento', 0)
            ");
            $titulo = "Alerta de Orçamento";
            $descricao = $mensagem . " (" . number_format($percentual, 1) . "% utilizado)";
            $stmt->execute([$id_usuario, $titulo, $descricao]);
        }
        
        echo json_encode(['sucesso' => true]);
    }
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida']);
?>
