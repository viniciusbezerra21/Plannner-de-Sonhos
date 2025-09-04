<?php
session_start();
require_once 'crypto.php';

// Impede acesso sem login/cadastro
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "weddingeasy";

$conn = new mysqli($hostname, $user, $password, $database);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$id = $_SESSION['usuario_id'];
$sql = "SELECT nome, foto_perfil FROM usuario WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nomeCript, $foto_perfil);
$stmt->fetch();
$stmt->close();
$conn->close();

$nome = descriptografar($nomeCript);

// Define foto padrão se não houver no banco
$foto_final = (!empty($foto_perfil)) ? htmlspecialchars($foto_perfil) : 'uploads/default.png';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
            text-align: center;
            padding-top: 50px;
        }
        img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }
        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #444;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn:hover {
            background-color: #666;
        }
    </style>
</head>
<body>
    <h1>Meu Perfil</h1>
    <img src="<?php echo $foto_final; ?>" alt="Foto de perfil">
    <h2><?php echo htmlspecialchars($nome); ?></h2>
    <a href="editar_perfil.php" class="btn">Customizar Perfil</a>
</body>
</html>
