<?php
session_start();
require_once "../config/conexao.php";

// Verificar se é desenvolvedor
if (!isset($_SESSION['id_usuario']) || $_SESSION['cargo'] !== 'dev') {
  header("Location: ../user/login.php");
  exit;
}

if ($_POST) {
  $budgetId = (int) $_POST['budget_id'];
  $item = trim($_POST['item']);
  $fornecedor = trim($_POST['fornecedor']);
  $quantidade = (int) $_POST['quantidade'];
  $valor_unitario = (float) $_POST['valor_unitario'];
  
  if ($budgetId > 0 && $item && $quantidade > 0 && $valor_unitario > 0) {
    $stmt = $pdo->prepare("UPDATE orcamentos SET item = ?, fornecedor = ?, quantidade = ?, valor_unitario = ? WHERE id_orcamento = ?");
    $stmt->execute([$item, $fornecedor, $quantidade, $valor_unitario, $budgetId]);
  }
}

header("Location: ../dev.php");
exit;
?>
