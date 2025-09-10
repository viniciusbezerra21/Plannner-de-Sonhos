<?php
session_start();
require_once 'conexao.php';
require_once 'crypto.php';

function verificarAutenticacao() {
    return isset($_SESSION['usuario_logado']) && $_SESSION['logado'] === true;
}

function obterCargoUsuario($usuario_id, $conn) {
    $sql = "SELECT cargo FROM usuario WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['cargo'];
    }
    
    return 'cliente'; // padrão
}

function verificarPermissaoAdmin() {
    if (!verificarAutenticacao()) {
        return false;
    }
    
    global $conn;
    $cargo = obterCargoUsuario($_SESSION['usuario_id'], $conn);
    return $cargo === 'dev';
}

function redirecionarPorCargo($usuario_id, $conn) {
    $cargo = obterCargoUsuario($usuario_id, $conn);
    
    if ($cargo === 'dev') {
        header("Location: pages/dev.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

function verificarAcessoAdmin() {
    if (!verificarAutenticacao()) {
        $_SESSION['mensagem_erro'] = "Você precisa fazer login para acessar esta página.";
        $_SESSION['pagina_anterior'] = $_SERVER['REQUEST_URI'];
        header("Location: user/login.php");
        exit;
    }
    
    global $conn;
    if (!verificarPermissaoAdmin()) {
        // Se não é dev, redireciona para página anterior ou index
        $pagina_anterior = $_SESSION['pagina_anterior'] ?? 'index.php';
        unset($_SESSION['pagina_anterior']);
        header("Location: " . $pagina_anterior);
        exit;
    }
}
?>
