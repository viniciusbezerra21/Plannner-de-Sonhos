<?php
header('Content-Type: application/json');
require_once "../config/conexao.php";

$id_cerimonialista = (int)($_GET['id'] ?? 0);

if ($id_cerimonialista === 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT f.id_fornecedor, f.nome_fornecedor, f.categoria, f.avaliacao
        FROM fornecedores f
        INNER JOIN cerimonialista_fornecedores cf ON f.id_fornecedor = cf.id_fornecedor
        WHERE cf.id_cerimonialista = ? AND cf.status = 'ativo'
        ORDER BY f.avaliacao DESC
    ");
    $stmt->execute([$id_cerimonialista]);
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($fornecedores);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([]);
}
?>
