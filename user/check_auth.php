<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (isset($_SESSION['usuario_logado']) && $_SESSION['logado'] === true) {
    // Buscar cargo do usuário
    $sql = "SELECT cargo FROM usuario WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            'logado' => true,
            'cargo' => $user['cargo'],
            'usuario_id' => $_SESSION['usuario_id']
        ]);
    } else {
        echo json_encode(['logado' => false]);
    }
} else {
    echo json_encode(['logado' => false]);
}

$conn->close();
?>
