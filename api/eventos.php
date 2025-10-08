<?php
session_start();
require_once '../config/conexao.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Create new event
            $stmt = $pdo->prepare("
                INSERT INTO eventos (id_usuario, nome_evento, data_evento, horario, local, tags, descricao, prioridade, cor_tag, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $idUsuario,
                $input['nome'],
                $input['data'],
                $input['horario'],
                $input['local'],
                $input['descricao'],
                $input['descricao'],
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
            // Delete event
            $stmt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ? AND id_usuario = ?");
            $stmt->execute([$input['id'], $idUsuario]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'update_priority':
            // Update event priority
            $stmt = $pdo->prepare("UPDATE eventos SET prioridade = ? WHERE id_evento = ? AND id_usuario = ?");
            $stmt->execute([$input['prioridade'], $input['id'], $idUsuario]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
