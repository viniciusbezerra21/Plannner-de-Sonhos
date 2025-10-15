<?php
session_start();
require_once "../config/conexao.php";

function logAtividade($pdo, $usuario_id, $tipo, $descricao) {
    try {
        $sql = "INSERT INTO atividades_usuario (id_usuario, tipo_atividade, descricao) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $tipo, $descricao]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

// Example usage when called directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && isset($_POST['descricao'])) {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autenticado']);
        exit;
    }
    
    $resultado = logAtividade(
        $pdo, 
        (int)$_SESSION['usuario_id'], 
        $_POST['tipo'], 
        $_POST['descricao']
    );
    
    echo json_encode(['success' => $resultado]);
}
?>
