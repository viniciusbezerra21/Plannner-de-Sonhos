<?php
session_start();
require_once "../config/conexao.php";

header('Content-Type: application/json');

$data_casamento = $_GET['data'] ?? '';

if (empty($data_casamento)) {
    echo json_encode(['erro' => 'Data não fornecida']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id_usuario, u.nome, u.avaliacao
        FROM usuarios u
        WHERE u.tipo_usuario = 'cerimonialista'
        AND u.id_usuario NOT IN (
            SELECT DISTINCT id_cerimonialista
            FROM cerimonialista_datas_bloqueadas
            WHERE DATE(data_bloqueada) = ?
        )
        ORDER BY u.avaliacao DESC
    ");
    $stmt->execute([$data_casamento]);
    $cerimonialistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($cerimonialistas);
} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro ao buscar cerimonialistas: ' . $e->getMessage()]);
}
?>
