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
    // Atualizar status da mensagem para "respondida"
    $stmt = $pdo->prepare("UPDATE contatos SET status_resposta = 'respondida' WHERE id = ?");
    $stmt->execute([$mensagem_id]);

    // Aqui você pode adicionar lógica para enviar email
    // Por exemplo, usando PHPMailer ou mail()
    
    // Simulação de envio de email (remova isso e adicione sua lógica real)
    $emailEnviado = true; // Substitua por sua lógica de envio de email
    
    if ($emailEnviado) {
      echo json_encode([
        'success' => true, 
        'message' => 'Resposta enviada com sucesso!',
        'destinatario' => $destinatario_nome
      ]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Erro ao enviar email']);
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
