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
        $limite = (int)($_GET['limite'] ?? 100);
        $tipo = $_GET['tipo'] ?? null;
        
        $sql = "
            SELECT hi.*, 
                   CASE 
                       WHEN hi.usuario_1_id = ? THEN u2.nome
                       ELSE u1.nome
                   END as outro_usuario_nome,
                   CASE 
                       WHEN hi.usuario_1_id = ? THEN u2.foto_perfil
                       ELSE u1.foto_perfil
                   END as outro_usuario_foto,
                   CASE 
                       WHEN hi.usuario_1_id = ? THEN hi.usuario_2_id
                       ELSE hi.usuario_1_id
                   END as outro_usuario_id
            FROM historico_interacoes hi
            LEFT JOIN usuarios u1 ON u1.id_usuario = hi.usuario_1_id
            LEFT JOIN usuarios u2 ON u2.id_usuario = hi.usuario_2_id
            WHERE hi.usuario_1_id = ? OR hi.usuario_2_id = ?
        ";
        
        if ($tipo) {
            $sql .= " AND hi.tipo_interacao = :tipo";
        }
        
        $sql .= " ORDER BY hi.data_interacao DESC LIMIT :limite";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute([$id_usuario, $id_usuario, $id_usuario, $id_usuario, $id_usuario]);
        
        if ($tipo) {
            $stmt->bindValue(':tipo', $tipo);
        }
        
        $interacoes = $stmt->fetchAll();
        echo json_encode(['sucesso' => true, 'interacoes' => $interacoes]);
    }
    
    elseif ($acao === 'estatisticas') {
        // Total de interações
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM historico_interacoes 
            WHERE usuario_1_id = ? OR usuario_2_id = ?
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $total = $stmt->fetch()['total'];
        
        // Por tipo
        $stmt = $pdo->prepare("
            SELECT tipo_interacao, COUNT(*) as total 
            FROM historico_interacoes 
            WHERE usuario_1_id = ? OR usuario_2_id = ?
            GROUP BY tipo_interacao
        ");
        $stmt->execute([$id_usuario, $id_usuario]);
        $porTipo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Usuários únicos
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT CASE 
                WHEN usuario_1_id = ? THEN usuario_2_id 
                ELSE usuario_1_id 
            END) as usuarios_unicos
            FROM historico_interacoes 
            WHERE usuario_1_id = ? OR usuario_2_id = ?
        ");
        $stmt->execute([$id_usuario, $id_usuario, $id_usuario]);
        $usuariosUnicos = $stmt->fetch()['usuarios_unicos'];
        
        echo json_encode([
            'sucesso' => true,
            'total' => $total,
            'por_tipo' => $porTipo,
            'usuarios_unicos' => $usuariosUnicos
        ]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'registrar') {
        $usuario_2_id = (int)$_POST['usuario_2_id'];
        $tipo_interacao = $_POST['tipo_interacao'];
        $descricao = $_POST['descricao'] ?? null;
        $referencia_id = $_POST['referencia_id'] ?? null;
        $referencia_tabela = $_POST['referencia_tabela'] ?? null;
        $detalhes = $_POST['detalhes'] ?? null;
        
        // Verificar se já existe uma interação recente (últimos 5 minutos) do mesmo tipo
        $stmt = $pdo->prepare("
            SELECT id_interacao FROM historico_interacoes
            WHERE ((usuario_1_id = ? AND usuario_2_id = ?) OR (usuario_1_id = ? AND usuario_2_id = ?))
            AND tipo_interacao = ?
            AND data_interacao > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$id_usuario, $usuario_2_id, $usuario_2_id, $id_usuario, $tipo_interacao]);
        
        if ($stmt->fetch()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Interação já registrada recentemente']);
            exit();
        }
        
        // Registrar nova interação
        $stmt = $pdo->prepare("
            INSERT INTO historico_interacoes 
            (usuario_1_id, usuario_2_id, tipo_interacao, descricao, referencia_id, referencia_tabela, detalhes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$id_usuario, $usuario_2_id, $tipo_interacao, $descricao, $referencia_id, $referencia_tabela, $detalhes])) {
            echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['erro' => 'Erro ao registrar interação']);
        }
    }
    
    elseif ($acao === 'deletar') {
        $id_interacao = (int)$_POST['id_interacao'];
        
        $stmt = $pdo->prepare("
            DELETE FROM historico_interacoes 
            WHERE id_interacao = ? AND (usuario_1_id = ? OR usuario_2_id = ?)
        ");
        
        if ($stmt->execute([$id_interacao, $id_usuario, $id_usuario])) {
            echo json_encode(['sucesso' => true]);
        }
    }
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida']);
?>
