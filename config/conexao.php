<?php
$host = "localhost";        // ou 127.0.0.1
$dbname = "weddingeasy";    // nome do banco
$username = "root";         // seu usuário do MySQL
$password = "root";         // sua senha do MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurações de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configura para fetch associativo por padrão
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>