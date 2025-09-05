<?php
session_start();
require_once 'crypto.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hostname = "127.0.0.1";
    $user = "root";
    $password = "root";
    $database = "weddingeasy";
    $conn = new mysqli($hostname, $user, $password, $database);
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }
    if (!isset($_POST['nome']) || !isset($_POST['nome_conj']) || !isset($_POST['email']) || !isset($_POST['senha'])) {
        $_SESSION['mensagem_erro'] = "Todos os campos são obrigatórios!";
        header("Location: ../index.php");
        exit;
    }
    $email_limpo = strtolower(trim($_POST['email']));
    $sqlTodosEmails = "SELECT email FROM usuario";
    $resultEmails = $conn->query($sqlTodosEmails);
    $emailJaExiste = false;
    if ($resultEmails && $resultEmails->num_rows > 0) {
        while ($row = $resultEmails->fetch_assoc()) {
            $emailDescriptografado = descriptografar($row['email']);
            if ($emailDescriptografado === $email_limpo) {
                $emailJaExiste = true;
                break;
            }
        }
    }
    if ($emailJaExiste) {
        $_SESSION['mensagem_erro'] = "Este e-mail já está cadastrado!";
        $conn->close();
        header("Location: ../index.php");
        exit;
    }
    $nome = criptografar($_POST['nome']);
    $nome_conj = criptografar($_POST['nome_conj']);
    $email = criptografar($email_limpo);
    $num_telefone = criptografar($_POST['num_telefone']);
    $genero = $_POST['genero'];
    $idade = $_POST['idade'];
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuario (nome, nome_conj, genero, idade, num_telefone, email, senha_hash, foto_perfil) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'uploads/default.png')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['mensagem_erro'] = "Erro na preparação da query: " . $conn->error;
        header("Location: ../index.php");
        exit;
    }
    $stmt->bind_param("sssisss", $nome, $nome_conj, $genero, $idade, $num_telefone, $email, $senha_hash);
    if ($stmt->execute()) {
        $usuario_id = $stmt->insert_id;
        $sql_user = "SELECT nome, nome_conj, email, genero, idade, num_telefone, foto_perfil FROM usuario WHERE id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("i", $usuario_id);
        $stmt_user->execute();
        $result = $stmt_user->get_result();
        $user_data = $result->fetch_assoc();
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['nome'] = $_POST['nome'];
        $_SESSION['email'] = $email_limpo;
        $_SESSION['usuario_logado'] = [
            'id' => $usuario_id,
            'nome' => $_POST['nome'],
            'nome_conj' => $_POST['nome_conj'],
            'email' => $email_limpo,
            'genero' => $_POST['genero'],
            'idade' => $_POST['idade'],
            'num_telefone' => $_POST['num_telefone'],
            'foto_perfil' => $user_data['foto_perfil'] ?? 'uploads/default.png'
        ];
        $_SESSION['mensagem_sucesso'] = "";
        header("Location: ../index.php");
        exit;
    } else {
        $_SESSION['mensagem_erro'] = "Erro no cadastro: " . $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: ../index.php");
        exit;
    }
}
