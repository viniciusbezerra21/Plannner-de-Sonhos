<?php
session_start();
require_once 'crypto.php'; 

// Configuração da base de dados
$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "weddingeasy";

$conn = new mysqli($hostname, $user, $password, $database);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Verificar se é uma requisição POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';
    
    // LOGIN
    if ($acao === 'login') {
        $email_ou_nome = $_POST['email'] ?? ''; // Campo único que pode ser email ou nome
        $senha = $_POST['senha'] ?? '';
        
        // Validar se os campos não estão vazios
        if (empty($email_ou_nome) || empty($senha)) {
            $_SESSION['mensagem_erro'] = "Por favor, preencha todos os campos!";
            header("Location: login.php");
            exit;
        }
        
        // Criptografar o valor inserido
        $valor_criptografado = criptografar($email_ou_nome);
        
        // Buscar usuário que tenha o nome OU email correspondente
        $sql = "SELECT * FROM usuario WHERE nome = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $valor_criptografado, $valor_criptografado);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            
            if (password_verify($senha, $usuario['senha_hash'])) {
                // Login bem-sucedido
                $_SESSION['logado'] = true;
                $_SESSION['usuario_id'] = $usuario['id'];
                
                // Descriptografar os dados para sessão
                $nome_descriptografado = descriptografar($usuario['nome']);
                $email_descriptografado = descriptografar($usuario['email']);
                
                $_SESSION['nome'] = $nome_descriptografado;
                $_SESSION['email'] = $email_descriptografado;
                
                // Definir dados do usuário para exibição no index
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
        
        // Fechar statement
       
    }
    
    // Adicione aqui outras ações (cadastro, etc.) se necessário
    
} else {
    // Se não for POST, redirecionar para login
    header("Location: login.php");
    exit;
}

// Fechar conexão
$conn->close();
?>