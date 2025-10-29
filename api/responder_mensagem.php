<?php
session_start();
require_once "../config/conexao.php";

// Verificar se o usuário está logado e é dev
if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../user/login.php");
  exit;
}

$stmt = $pdo->prepare("SELECT cargo FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['cargo'] !== 'dev') {
  header("Location: ../index.php");
  exit;
}

// Processar o formulário de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mensagem_id = (int) $_POST['mensagem_id'];
  $destinatario_email = trim($_POST['destinatario_email']);
  $destinatario_nome = trim($_POST['destinatario_nome']);
  $assunto = trim($_POST['assunto']);
  $resposta = trim($_POST['resposta']);
  $remetente_id = $_SESSION['usuario_id'];

  // Validar dados
  if ($mensagem_id && $destinatario_email && $assunto && $resposta) {
    
    try {
      $stmt = $pdo->prepare("
        UPDATE contatos 
        SET status_resposta = 'respondida', 
            resposta = ?, 
            data_resposta = NOW(), 
            respondido_por = ? 
        WHERE id = ?
      ");
      $stmt->execute([$resposta, $remetente_id, $mensagem_id]);

      // Enviar email (usando a função mail do PHP)
      $headers = "From: noreply@weddingeasy.com\r\n";
      $headers .= "Reply-To: noreply@weddingeasy.com\r\n";
      $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

      $corpo_email = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
          <h2 style='color: #c22b51;'>Resposta à sua mensagem</h2>
          <p>Olá, {$destinatario_nome}!</p>
          <p>Recebemos sua mensagem e estamos respondendo:</p>
          <div style='background: #f5f5f5; padding: 15px; border-left: 4px solid #c22b51; margin: 20px 0;'>
            {$resposta}
          </div>
          <p>Atenciosamente,<br>Equipe WeddingEasy</p>
        </body>
        </html>
      ";

      // Tentar enviar o email
      $email_enviado = mail($destinatario_email, $assunto, $corpo_email, $headers);

      if ($email_enviado) {
        $_SESSION['mensagem_sucesso'] = "Resposta enviada com sucesso!";
      } else {
        $_SESSION['mensagem_aviso'] = "Resposta salva, mas houve um problema ao enviar o email.";
      }

    } catch (PDOException $e) {
      $_SESSION['mensagem_erro'] = "Erro ao salvar resposta: " . $e->getMessage();
    }

  } else {
    $_SESSION['mensagem_erro'] = "Todos os campos são obrigatórios.";
  }
}

header("Location: dev.php");
exit;
?>
