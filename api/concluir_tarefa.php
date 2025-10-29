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

// Processar o formulário de atualização de tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tarefa_id = (int) $_POST['tarefa_id'];
  $novo_status = trim($_POST['novo_status']);
  $observacoes = trim($_POST['observacoes']);
  $usuario_id = $_SESSION['usuario_id'];

  // Validar dados
  if ($tarefa_id && $novo_status) {
    
    try {
      if (!empty($observacoes)) {
        // Se houver observações, atualizar também os campos de conclusão
        if (strtolower($novo_status) === 'concluída' || strtolower($novo_status) === 'concluido') {
          $stmt = $pdo->prepare("
            UPDATE tarefas 
            SET status = ?, 
                observacoes = ?, 
                data_conclusao = NOW(), 
                concluido_por = ? 
            WHERE id_tarefa = ?
          ");
          $stmt->execute([$novo_status, $observacoes, $usuario_id, $tarefa_id]);
        } else {
          $stmt = $pdo->prepare("
            UPDATE tarefas 
            SET status = ?, 
                observacoes = ? 
            WHERE id_tarefa = ?
          ");
          $stmt->execute([$novo_status, $observacoes, $tarefa_id]);
        }
      } else {
        // Sem observações, apenas atualizar o status
        if (strtolower($novo_status) === 'concluída' || strtolower($novo_status) === 'concluido') {
          $stmt = $pdo->prepare("
            UPDATE tarefas 
            SET status = ?, 
                data_conclusao = NOW(), 
                concluido_por = ? 
            WHERE id_tarefa = ?
          ");
          $stmt->execute([$novo_status, $usuario_id, $tarefa_id]);
        } else {
          $stmt = $pdo->prepare("UPDATE tarefas SET status = ? WHERE id_tarefa = ?");
          $stmt->execute([$novo_status, $tarefa_id]);
        }
      }

      $_SESSION['mensagem_sucesso'] = "Tarefa atualizada com sucesso!";

    } catch (PDOException $e) {
      $_SESSION['mensagem_erro'] = "Erro ao atualizar tarefa: " . $e->getMessage();
    }

  } else {
    $_SESSION['mensagem_erro'] = "Dados inválidos.";
  }
}

header("Location: dev.php");
exit;
?>
