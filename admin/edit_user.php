<?php
session_start();
require_once "../config/conexao.php";


if (!isset($_SESSION['id_usuario']) || $_SESSION['cargo'] !== 'dev') {
  header("Location: ../user/login.php");
  exit;
}

if ($_POST) {
  $userId = (int) $_POST['user_id'];
  $nome = trim($_POST['nome']);
  $email = trim($_POST['email']);
  $cargo = $_POST['cargo'];
  
  if ($userId > 0 && $nome && $email) {
    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, cargo = ? WHERE id_usuario = ?");
    $stmt->execute([$nome, $email, $cargo, $userId]);
  }
}

header("Location: ../dev.php");
exit;
?>
