<?php
session_start();
require_once 'crypto.php';
$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "weddingeasy";
$conn = new mysqli($hostname, $user, $password, $database);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'login') {
        $email_ou_nome = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        if (empty($email_ou_nome) || empty($senha)) {
            $_SESSION['mensagem_erro'] = "Por favor, preencha todos os campos!";
            header("Location: login.php");
            exit;
        }
        $valor_criptografado = criptografar($email_ou_nome);
        $sql = "SELECT * FROM usuario WHERE nome = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $valor_criptografado, $valor_criptografado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($senha, $usuario['senha_hash'])) {
                $_SESSION['logado'] = true;
                $_SESSION['usuario_id'] = $usuario['id'];
                $nome_descriptografado = descriptografar($usuario['nome']);
                $email_descriptografado = descriptografar($usuario['email']);
                $_SESSION['nome'] = $nome_descriptografado;
                $_SESSION['email'] = $email_descriptografado;
                $_SESSION['usuario_logado'] = [
                    'id' => $usuario['id'],
                    'nome' => $nome_descriptografado,
                    'email' => $email_descriptografado,
                    'foto_perfil' => $usuario['foto_perfil'] ?? 'uploads/default.png'
                ];
                $_SESSION['mensagem_sucesso'] = "";
                header("Location: ../index.php");
                exit;
            } else {
                $_SESSION['mensagem_erro'] = "Senha incorreta!";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['mensagem_erro'] = "Usuário não encontrado!";
            header("Location: login.php");
            exit;
        }
    }
} else {
    header("Location: login.php");
    exit;
}
$conn->close();