<?php
$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "weddingeasy";

$conn = new mysqli($hostname, $user, $password, $database);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
