<?php
// Função para atualizar os dados da sessão do usuário
function atualizarSessaoUsuario($usuario_id, $conn) {
    require_once 'crypto.php';
    
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
        
        // Atualizar sessões
        $_SESSION['nome'] = $nome;
        $_SESSION['email'] = $email;
        
        $_SESSION['usuario_logado'] = [
            'id' => $usuario_id,
            'nome' => $nome,
            'email' => $email,
            'foto_perfil' => $user_data['foto_perfil'] ?? 'uploads/default.png'
        ];
    }
    
    $stmt->close();
}
?>