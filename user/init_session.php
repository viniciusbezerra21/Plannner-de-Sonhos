<?php
// Arquivo para inicializar e verificar a sessão do usuário

// Verificar se existe uma sessão ativa mas sem dados completos
if (isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario_logado'])) {
    require_once 'user/crypto.php';
    
    $hostname = "127.0.0.1";
    $user = "root";
    $password = "root";
    $database = "casamento";

    $conn = new mysqli($hostname, $user, $password, $database);
    
    if (!$conn->connect_error) {
        $usuario_id = $_SESSION['usuario_id'];
        
        $sql = "SELECT nome, email, foto_perfil FROM usuario WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            // Descriptografar dados
            $nome = descriptografar($user_data['nome']);
            $email = descriptografar($user_data['email']);
            
            // Recriar dados da sessão
            $_SESSION['usuario_logado'] = [
                'id' => $usuario_id,
                'nome' => $nome,
                'email' => $email,
                'foto_perfil' => $user_data['foto_perfil'] ?? 'uploads/default.png'
            ];
            
            $_SESSION['nome'] = $nome;
            $_SESSION['email'] = $email;
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>