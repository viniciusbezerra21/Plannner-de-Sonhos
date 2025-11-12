<?php
session_start();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    switch ($acao) {
        case 'criar':
            $avaliado_id = (int)$_POST['avaliado_id'];
            $nota = (int)$_POST['nota'];
            $comentario = $_POST['comentario'] ?? '';
            $categoria_1 = (int)($_POST['categoria_1'] ?? 0);
            $categoria_2 = (int)($_POST['categoria_2'] ?? 0);
            $categoria_3 = (int)($_POST['categoria_3'] ?? 0);
            $categoria_4 = (int)($_POST['categoria_4'] ?? 0);
            $categoria_5 = (int)($_POST['categoria_5'] ?? 0);
            
            if ($nota < 1 || $nota > 5) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Nota inválida']);
                exit();
            }
            
            if ($avaliado_id == $id_usuario) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Você não pode avaliar a si mesmo']);
                exit();
            }
            
            // Verificar se já existe avaliação
            $stmt = $pdo->prepare("SELECT id_avaliacao FROM avaliacoes WHERE avaliador_id = ? AND avaliado_id = ?");
            $stmt->execute([$id_usuario, $avaliado_id]);
            $avaliacao_existente = $stmt->fetch();
            
            if ($avaliacao_existente) {
                // Atualizar avaliação existente
                $stmt = $pdo->prepare("
                    UPDATE avaliacoes SET 
                        nota = ?,
                        comentario = ?,
                        categoria_1 = ?,
                        categoria_2 = ?,
                        categoria_3 = ?,
                        categoria_4 = ?,
                        categoria_5 = ?,
                        data_avaliacao = NOW()
                    WHERE id_avaliacao = ?
                ");
                $stmt->execute([
                    $nota, $comentario, $categoria_1, $categoria_2, 
                    $categoria_3, $categoria_4, $categoria_5, 
                    $avaliacao_existente['id_avaliacao']
                ]);
                
                // Registrar atividade
                $stmt = $pdo->prepare("
                    INSERT INTO atividades_usuario (id_usuario, acao, descricao, data_atividade)
                    VALUES (?, 'avaliacao_atualizada', ?, NOW())
                ");
                $stmt->execute([$id_usuario, "Atualizou avaliação do usuário ID: $avaliado_id"]);
                
                header('Location: ../pages/avaliacoes.php?id=' . $avaliado_id . '&success=atualizada');
            } else {
                // Criar nova avaliação
                $stmt = $pdo->prepare("
                    INSERT INTO avaliacoes (
                        avaliador_id, avaliado_id, nota, comentario,
                        categoria_1, categoria_2, categoria_3, categoria_4, categoria_5,
                        data_avaliacao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $id_usuario, $avaliado_id, $nota, $comentario,
                    $categoria_1, $categoria_2, $categoria_3, $categoria_4, $categoria_5
                ]);
                
                // Criar notificação para o avaliado
                $stmt = $pdo->prepare("
                    INSERT INTO notificacoes (id_usuario, tipo, conteudo, data_notificacao)
                    VALUES (?, 'avaliacao', ?, NOW())
                ");
                $stmt->execute([$avaliado_id, "Você recebeu uma nova avaliação!"]);
                
                // Registrar atividade
                $stmt = $pdo->prepare("
                    INSERT INTO atividades_usuario (id_usuario, acao, descricao, data_atividade)
                    VALUES (?, 'avaliacao_criada', ?, NOW())
                ");
                $stmt->execute([$id_usuario, "Avaliou o usuário ID: $avaliado_id"]);
                
                header('Location: ../pages/avaliacoes.php?id=' . $avaliado_id . '&success=criada');
            }
            break;
            
        case 'excluir':
            $id_avaliacao = (int)$_POST['id_avaliacao'];
            
            $stmt = $pdo->prepare("DELETE FROM avaliacoes WHERE id_avaliacao = ? AND avaliador_id = ?");
            $stmt->execute([$id_avaliacao, $id_usuario]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Avaliação excluída']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Avaliação não encontrada']);
            }
            break;
            
        case 'listar':
            $avaliado_id = (int)($_GET['avaliado_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT a.*, u.nome, u.foto_perfil
                FROM avaliacoes a
                JOIN usuarios u ON u.id_usuario = a.avaliador_id
                WHERE a.avaliado_id = ?
                ORDER BY a.data_avaliacao DESC
            ");
            $stmt->execute([$avaliado_id]);
            $avaliacoes = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("
                SELECT AVG(nota) as media, COUNT(*) as total
                FROM avaliacoes
                WHERE avaliado_id = ?
            ");
            $stmt->execute([$avaliado_id]);
            $stats = $stmt->fetch();
            
            echo json_encode([
                'sucesso' => true,
                'avaliacoes' => $avaliacoes,
                'media' => round($stats['media'], 1),
                'total' => $stats['total']
            ]);
            break;
            
        case 'estatisticas':
            $usuario_id = (int)($_GET['usuario_id'] ?? $id_usuario);
            
            // Média geral
            $stmt = $pdo->prepare("
                SELECT 
                    AVG(nota) as media_geral,
                    AVG(categoria_1) as media_cat1,
                    AVG(categoria_2) as media_cat2,
                    AVG(categoria_3) as media_cat3,
                    AVG(categoria_4) as media_cat4,
                    AVG(categoria_5) as media_cat5,
                    COUNT(*) as total
                FROM avaliacoes
                WHERE avaliado_id = ?
            ");
            $stmt->execute([$usuario_id]);
            $stats = $stmt->fetch();
            
            // Distribuição de notas
            $stmt = $pdo->prepare("
                SELECT nota, COUNT(*) as quantidade
                FROM avaliacoes
                WHERE avaliado_id = ?
                GROUP BY nota
                ORDER BY nota DESC
            ");
            $stmt->execute([$usuario_id]);
            $distribuicao = $stmt->fetchAll();
            
            echo json_encode([
                'sucesso' => true,
                'estatisticas' => $stats,
                'distribuicao' => $distribuicao
            ]);
            break;
            
        default:
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
