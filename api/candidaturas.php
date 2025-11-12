<?php
session_start();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo_usuario'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    switch ($acao) {
        case 'criar':
            if ($tipo_usuario !== 'fornecedor') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas fornecedores podem criar candidaturas']);
                exit();
            }
            
            $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $fornecedor = $stmt->fetch();
            
            if (!$fornecedor) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Fornecedor não encontrado']);
                exit();
            }
            
            $id_fornecedor = $fornecedor['id_fornecedor'];
            $id_cerimonialista = (int)$_POST['id_cerimonialista'];
            $mensagem = trim($_POST['mensagem']);
            
            // Verificar se já existe candidatura
            $stmt = $pdo->prepare("
                SELECT id_candidatura FROM candidaturas_fornecedor 
                WHERE id_fornecedor = ? AND id_cerimonialista = ? AND status_candidatura = 'pendente'
            ");
            $stmt->execute([$id_fornecedor, $id_cerimonialista]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Você já tem uma candidatura pendente para este cerimonialista']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO candidaturas_fornecedor (
                    id_fornecedor, id_cerimonialista, mensagem, 
                    status_candidatura, data_candidatura
                ) VALUES (?, ?, ?, 'pendente', NOW())
            ");
            $stmt->execute([$id_fornecedor, $id_cerimonialista, $mensagem]);
            
            // Buscar id_usuario do cerimonialista para notificação
            $stmt = $pdo->prepare("SELECT id_usuario FROM cerimonialistas WHERE id_cerimonialista = ?");
            $stmt->execute([$id_cerimonialista]);
            $cerim = $stmt->fetch();
            
            // Criar notificação
            $stmt = $pdo->prepare("
                SELECT nome_fornecedor FROM fornecedores WHERE id_fornecedor = ?
            ");
            $stmt->execute([$id_fornecedor]);
            $fornec = $stmt->fetch();
            
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (id_usuario, tipo, conteudo, data_notificacao)
                VALUES (?, 'candidatura', ?, NOW())
            ");
            $stmt->execute([
                $cerim['id_usuario'], 
                "Nova candidatura de " . $fornec['nome_fornecedor']
            ]);
            
            // Registrar atividade
            $stmt = $pdo->prepare("
                INSERT INTO atividades_usuario (id_usuario, acao, descricao, data_atividade)
                VALUES (?, 'candidatura_criada', ?, NOW())
            ");
            $stmt->execute([$id_usuario, "Candidatou-se ao cerimonialista ID: $id_cerimonialista"]);
            
            header('Location: ../pages/candidaturas.php?success=criada');
            break;
            
        case 'responder':
            if ($tipo_usuario !== 'cerimonialista') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas cerimonialistas podem responder candidaturas']);
                exit();
            }
            
            $stmt = $pdo->prepare("SELECT id_cerimonialista FROM cerimonialistas WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $cerimonialista = $stmt->fetch();
            
            if (!$cerimonialista) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Cerimonialista não encontrado']);
                exit();
            }
            
            $id_candidatura = (int)$_POST['id_candidatura'];
            $status = $_POST['status'];
            
            if (!in_array($status, ['aprovada', 'rejeitada'])) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Status inválido']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                UPDATE candidaturas_fornecedor 
                SET status_candidatura = ?, data_resposta = NOW()
                WHERE id_candidatura = ? AND id_cerimonialista = ?
            ");
            $stmt->execute([$status, $id_candidatura, $cerimonialista['id_cerimonialista']]);
            
            // Buscar candidatura para notificar fornecedor
            $stmt = $pdo->prepare("
                SELECT f.id_usuario, f.nome_fornecedor 
                FROM candidaturas_fornecedor c
                JOIN fornecedores f ON f.id_fornecedor = c.id_fornecedor
                WHERE c.id_candidatura = ?
            ");
            $stmt->execute([$id_candidatura]);
            $candidatura = $stmt->fetch();
            
            // Criar notificação
            $mensagem_notif = $status === 'aprovada' 
                ? "Sua candidatura foi aprovada!" 
                : "Sua candidatura foi rejeitada.";
                
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (id_usuario, tipo, conteudo, data_notificacao)
                VALUES (?, 'candidatura_resposta', ?, NOW())
            ");
            $stmt->execute([$candidatura['id_usuario'], $mensagem_notif]);
            
            // Se aprovada, criar associação
            if ($status === 'aprovada') {
                $stmt = $pdo->prepare("
                    SELECT id_fornecedor FROM candidaturas_fornecedor WHERE id_candidatura = ?
                ");
                $stmt->execute([$id_candidatura]);
                $cand = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO cerimonialista_fornecedores (id_cerimonialista, id_fornecedor, data_associacao)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$cerimonialista['id_cerimonialista'], $cand['id_fornecedor']]);
            }
            
            // Registrar atividade
            $stmt = $pdo->prepare("
                INSERT INTO atividades_usuario (id_usuario, acao, descricao, data_atividade)
                VALUES (?, 'candidatura_respondida', ?, NOW())
            ");
            $stmt->execute([$id_usuario, "Respondeu candidatura ID: $id_candidatura com status: $status"]);
            
            header('Location: ../pages/candidaturas.php?success=respondida');
            break;
            
        case 'cancelar':
            if ($tipo_usuario !== 'fornecedor') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas fornecedores podem cancelar candidaturas']);
                exit();
            }
            
            $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $fornecedor = $stmt->fetch();
            
            $id_candidatura = (int)$_POST['id_candidatura'];
            
            $stmt = $pdo->prepare("
                DELETE FROM candidaturas_fornecedor 
                WHERE id_candidatura = ? AND id_fornecedor = ? AND status_candidatura = 'pendente'
            ");
            $stmt->execute([$id_candidatura, $fornecedor['id_fornecedor']]);
            
            header('Location: ../pages/candidaturas.php?success=cancelada');
            break;
            
        case 'listar':
            if ($tipo_usuario === 'fornecedor') {
                $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                $fornecedor = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    SELECT c.*, ce.nome_cerimonialista, ce.empresa
                    FROM candidaturas_fornecedor c
                    JOIN cerimonialistas ce ON ce.id_cerimonialista = c.id_cerimonialista
                    WHERE c.id_fornecedor = ?
                    ORDER BY c.data_candidatura DESC
                ");
                $stmt->execute([$fornecedor['id_fornecedor']]);
            } elseif ($tipo_usuario === 'cerimonialista') {
                $stmt = $pdo->prepare("SELECT id_cerimonialista FROM cerimonialistas WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                $cerimonialista = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    SELECT c.*, f.nome_fornecedor, f.tipo_servico
                    FROM candidaturas_fornecedor c
                    JOIN fornecedores f ON f.id_fornecedor = c.id_fornecedor
                    WHERE c.id_cerimonialista = ?
                    ORDER BY c.data_candidatura DESC
                ");
                $stmt->execute([$cerimonialista['id_cerimonialista']]);
            }
            
            $candidaturas = $stmt->fetchAll();
            echo json_encode(['sucesso' => true, 'candidaturas' => $candidaturas]);
            break;
            
        default:
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
