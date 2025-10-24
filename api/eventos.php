<?php
session_start();
require_once '../config/conexao.php';

header('Content-Type: application/json');


if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];


$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $stmt = $pdo->prepare("
                INSERT INTO eventos (id_usuario, nome_evento, data_evento, horario, local, tags, descricao, prioridade, cor_tag, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $idUsuario,
                $input['nome'] ?? '',
                $input['data'] ?? '',
                $input['horario'] ?? '',
                $input['local'] ?? '',
                $input['tags'] ?? '',
                $input['descricao'] ?? '',
                $input['prioridade'] ?? 'media',
                $input['cor_tag'] ?? 'azul',
                $input['status'] ?? 'pendente'
            ]);
            
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'delete':
           
            $stmt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ? AND id_usuario = ?");
            $stmt->execute([$input['id'], $idUsuario]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'update_priority':
            $stmt = $pdo->prepare("UPDATE eventos SET prioridade = ?, status = ? WHERE id_evento = ? AND id_usuario = ?");
            $stmt->execute([
                $input['prioridade'], 
                $input['status'] ?? 'pendente',
                $input['id'], 
                $idUsuario
            ]);
            
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
} catch (PDOException $e) {
    error_log("Erro no eventos.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()]);
}
?>
