<?php
session_start();
require_once "../config/conexao.php";

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mensagem_id = (int) $_POST['mensagem_id'];
  $assunto = trim($_POST['assunto']);
  $resposta = trim($_POST['resposta']);
  $destinatario_email = trim($_POST['destinatario_email']);
  $destinatario_nome = trim($_POST['destinatario_nome']);

  if (!$mensagem_id || !$assunto || !$resposta || !$destinatario_email) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
  }

  try {
    $stmt = $pdo->prepare("
      INSERT INTO respostas_mensagens (mensagem_id, remetente_id, assunto, resposta, data_resposta)
      VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$mensagem_id, $_SESSION['usuario_id'], $assunto, $resposta]);

    $stmt = $pdo->prepare("
      UPDATE contatos 
      SET status_resposta = 'respondida', resposta = ?, data_resposta = NOW(), respondido_por = ?
      WHERE id = ?
    ");
    $stmt->execute([$resposta, $_SESSION['usuario_id'], $mensagem_id]);

    echo json_encode([
      'success' => true, 
      'message' => 'Resposta enviada com sucesso!',
      'destinatario' => $destinatario_nome
    ]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
