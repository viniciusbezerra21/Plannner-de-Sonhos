<?php
session_start();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    switch ($acao) {
        case 'enviar':
            $destinatario_id = (int)$_POST['destinatario_id'];
            $assunto = $_POST['assunto'] ?? 'Mensagem';
            $conteudo = trim($_POST['conteudo']);
            
            if (empty($conteudo)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Conteúdo vazio']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO mensagens (remetente_id, destinatario_id, assunto, conteudo, lida, tipo_mensagem, data_envio)
                VALUES (?, ?, ?, ?, 0, 'privada', NOW())
            ");
            
            if ($stmt->execute([$id_usuario, $destinatario_id, $assunto, $conteudo])) {
                // Criar notificação
                $stmt = $pdo->prepare("
                    SELECT nome FROM usuarios WHERE id_usuario = ?
                ");
                $stmt->execute([$id_usuario]);
                $remetente = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    INSERT INTO notificacoes (id_usuario, tipo, conteudo, data_notificacao)
                    VALUES (?, 'mensagem', ?, NOW())
                ");
                $stmt->execute([
                    $destinatario_id, 
                    "Nova mensagem de " . $remetente['nome']
                ]);
                
                // Registrar atividade
                $stmt = $pdo->prepare("
                    INSERT INTO atividades_usuario (id_usuario, acao, descricao, data_atividade)
                    VALUES (?, 'mensagem_enviada', ?, NOW())
                ");
                $stmt->execute([$id_usuario, "Enviou mensagem para usuário ID: $destinatario_id"]);
                
                if (isset($_POST['ajax'])) {
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem enviada']);
                } else {
                    header('Location: ../pages/mensagens.php?conversa_id=' . $destinatario_id);
                }
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao enviar']);
            }
            break;
            
        case 'listar_conversas':
            $stmt = $pdo->prepare("
                SELECT 
                    CASE 
                        WHEN m.remetente_id = ? THEN m.destinatario_id 
                        ELSE m.remetente_id 
                    END as outro_usuario_id,
                    u.nome, 
                    u.foto_perfil, 
                    u.tipo_usuario,
                    MAX(m.data_envio) as ultima_mensagem,
                    COUNT(CASE WHEN m.destinatario_id = ? AND m.lida = 0 THEN 1 END) as nao_lidas
                FROM mensagens m
                JOIN usuarios u ON u.id_usuario = CASE 
                    WHEN m.remetente_id = ? THEN m.destinatario_id 
                    ELSE m.remetente_id 
                END
                WHERE m.remetente_id = ? OR m.destinatario_id = ?
                GROUP BY outro_usuario_id, u.nome, u.foto_perfil, u.tipo_usuario
                ORDER BY ultima_mensagem DESC
            ");
            $stmt->execute([$id_usuario, $id_usuario, $id_usuario, $id_usuario, $id_usuario]);
            $conversas = $stmt->fetchAll();
            
            echo json_encode(['sucesso' => true, 'conversas' => $conversas]);
            break;
            
        case 'carregar_mensagens':
            $outro_usuario_id = (int)($_GET['outro_usuario_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT m.*, u.nome as remetente_nome, u.foto_perfil as remetente_foto
                FROM mensagens m
                JOIN usuarios u ON u.id_usuario = m.remetente_id
                WHERE (m.remetente_id = ? AND m.destinatario_id = ?) 
                   OR (m.remetente_id = ? AND m.destinatario_id = ?)
                ORDER BY m.data_envio ASC
            ");
            $stmt->execute([$id_usuario, $outro_usuario_id, $outro_usuario_id, $id_usuario]);
            $mensagens = $stmt->fetchAll();
            
            // Marcar como lidas
            $stmt = $pdo->prepare("
                UPDATE mensagens 
                SET lida = 1 
                WHERE destinatario_id = ? AND remetente_id = ? AND lida = 0
            ");
            $stmt->execute([$id_usuario, $outro_usuario_id]);
            
            echo json_encode(['sucesso' => true, 'mensagens' => $mensagens]);
            break;
            
        case 'marcar_lida':
            $mensagem_id = (int)($_POST['mensagem_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                UPDATE mensagens 
                SET lida = 1 
                WHERE id_mensagem = ? AND destinatario_id = ?
            ");
            $stmt->execute([$mensagem_id, $id_usuario]);
            
            echo json_encode(['sucesso' => true]);
            break;
            
        case 'contar_nao_lidas':
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM mensagens 
                WHERE destinatario_id = ? AND lida = 0
            ");
            $stmt->execute([$id_usuario]);
            $result = $stmt->fetch();
            
            echo json_encode(['sucesso' => true, 'total' => $result['total']]);
            break;
            
        case 'excluir':
            $mensagem_id = (int)($_POST['mensagem_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                DELETE FROM mensagens 
                WHERE id_mensagem = ? AND (remetente_id = ? OR destinatario_id = ?)
            ");
            $stmt->execute([$mensagem_id, $id_usuario, $id_usuario]);
            
            echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem excluída']);
            break;
            
        case 'pesquisar':
            $termo = $_GET['termo'] ?? '';
            
            $stmt = $pdo->prepare("
                SELECT m.*, u.nome as outro_usuario_nome
                FROM mensagens m
                JOIN usuarios u ON u.id_usuario = CASE 
                    WHEN m.remetente_id = ? THEN m.destinatario_id 
                    ELSE m.remetente_id 
                END
                WHERE (m.remetente_id = ? OR m.destinatario_id = ?)
                  AND (m.conteudo LIKE ? OR m.assunto LIKE ? OR u.nome LIKE ?)
                ORDER BY m.data_envio DESC
                LIMIT 50
            ");
            $termo_busca = "%$termo%";
            $stmt->execute([
                $id_usuario, $id_usuario, $id_usuario,
                $termo_busca, $termo_busca, $termo_busca
            ]);
            $resultados = $stmt->fetchAll();
            
            echo json_encode(['sucesso' => true, 'resultados' => $resultados]);
            break;
            
        default:
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
