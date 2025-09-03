<?php
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão se existir
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Definir mensagem de logout bem-sucedido
session_start();
$_SESSION['mensagem_sucesso'] = "Logout realizado com sucesso!";

// Redirecionar para a página inicial
header("Location: ../index.php");
exit;
?>