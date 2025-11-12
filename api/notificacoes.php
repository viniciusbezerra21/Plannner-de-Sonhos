<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';
    
    if ($acao === 'listar') {
        $stmt = $pdo->prepare("
            SELECT * FROM notificacoes 
            WHERE usuario_id = ? 
            ORDER BY data_criacao DESC 
            LIMIT 50
        ");
        $stmt->execute([$id_usuario]);
        $notificacoes = $stmt->fetchAll();
        echo json_encode(['sucesso' => true, 'notificacoes' => $notificacoes]);
    }
    
    elseif ($acao === 'nao_lidas') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM notificacoes 
            WHERE usuario_id = ? AND lida = 0
        ");
        $stmt->execute([$id_usuario]);
        $resultado = $stmt->fetch();
        echo json_encode(['sucesso' => true, 'total' => $resultado['total']]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'marcar_lida') {
        $id_notificacao = (int)$_POST['id_notificacao'];
        
        $stmt = $pdo->prepare("
            UPDATE notificacoes 
            SET lida = 1 
            WHERE id_notificacao = ? AND usuario_id = ?
        ");
        
        if ($stmt->execute([$id_notificacao, $id_usuario])) {
            echo json_encode(['sucesso' => true]);
        }
    }
    
    elseif ($acao === 'marcar_todas_lidas') {
        $stmt = $pdo->prepare("
            UPDATE notificacoes 
            SET lida = 1 
            WHERE usuario_id = ? AND lida = 0
        ");
        
        if ($stmt->execute([$id_usuario])) {
            echo json_encode(['sucesso' => true]);
        }
    }
    
    elseif ($acao === 'criar') {
        $usuario_alvo = (int)$_POST['usuario_id'];
        $tipo = $_POST['tipo'] ?? 'mensagem';
        $titulo = $_POST['titulo'];
        $descricao = $_POST['descricao'] ?? null;
        $referencia_id = $_POST['referencia_id'] ?? null;
        $referencia_tabela = $_POST['referencia_tabela'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (usuario_id, tipo, titulo, descricao, referencia_id, referencia_tabela, lida)
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        
        if ($stmt->execute([$usuario_alvo, $tipo, $titulo, $descricao, $referencia_id, $referencia_tabela])) {
            echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);
        }
    }
    
    elseif ($acao === 'deletar') {
        $id_notificacao = (int)$_POST['id_notificacao'];
        
        $stmt = $pdo->prepare("
            DELETE FROM notificacoes 
            WHERE id_notificacao = ? AND usuario_id = ?
        ");
        
        if ($stmt->execute([$id_notificacao, $id_usuario])) {
            echo json_encode(['sucesso' => true]);
        }
    }
    
    elseif ($acao === 'limpar_antigas') {
        $stmt = $pdo->prepare("
            DELETE FROM notificacoes 
            WHERE usuario_id = ? AND lida = 1 AND data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        if ($stmt->execute([$id_usuario])) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Notificações antigas removidas']);
        }
    }
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida']);
?>
