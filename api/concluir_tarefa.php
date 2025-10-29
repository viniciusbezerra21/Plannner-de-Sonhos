<?php
session_start();
require_once "../config/conexao.php";

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tarefa_id = (int) $_POST['tarefa_id'];
  $novo_status = trim($_POST['novo_status']);
  $observacoes = trim($_POST['observacoes'] ?? '');

  if (!$tarefa_id || !$novo_status) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
  }

  try {
    // Atualizar status da tarefa
    $stmt = $pdo->prepare("UPDATE tarefas SET status = ? WHERE id_tarefa = ?");
    $stmt->execute([$novo_status, $tarefa_id]);

    // Se houver observações, você pode salvá-las em uma tabela de logs
    if ($observacoes) {
      // Adicione sua lógica para salvar observações
    }

    echo json_encode([
      'success' => true, 
      'message' => 'Tarefa atualizada com sucesso!',
      'novo_status' => $novo_status
    ]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
