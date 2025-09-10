<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'weddingeasy';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'eventos') {
            getEventos($pdo);
        }
        break;
    case 'POST':
        if ($action === 'criar_evento') {
            criarEvento($pdo);
        }
        break;
    case 'PUT':
        if ($action === 'atualizar_evento') {
            atualizarEvento($pdo);
        }
        break;
    case 'DELETE':
        if ($action === 'deletar_evento') {
            deletarEvento($pdo);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        break;
}

function getEventos($pdo)
{
    try {
        $stmt = $pdo->query("SELECT * FROM eventos ORDER BY data_evento, hora_evento");
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $eventosPorData = [];
        foreach ($eventos as $evento) {
            $data = $evento['data_evento'];
            if (!isset($eventosPorData[$data])) {
                $eventosPorData[$data] = [];
            }
            
            $eventosPorData[$data][] = [
                'id' => $evento['id'],
                'nome' => $evento['nome_evento'],
                'hora' => $evento['hora_evento'],
                'local' => $evento['local_evento'],
                'tipo' => $evento['descricao'] // Mapeando descricao para tipo conforme esperado pelo JS
            ];
        }
        
        echo json_encode($eventosPorData);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao buscar eventos: ' . $e->getMessage()]);
    }
}

function criarEvento($pdo)
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $nome = $input['nome'] ?? '';
        $data = $input['data'] ?? '';
        $hora = $input['hora'] ?? null;
        $local = $input['local'] ?? '';
        $tipo = $input['tipo'] ?? 'Evento';
        $usuario_id = $input['usuario_id'] ?? 1;

        if (empty($nome) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome e data são obrigatórios']);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO eventos (nome_evento, usuario_id, data_evento, hora_evento, local_evento, descricao, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$nome, $usuario_id, $data, $hora, $local, $tipo]);
        
        $eventoId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $eventoId,
            'message' => 'Evento criado com sucesso'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao criar evento: ' . $e->getMessage()]);
    }
}

function atualizarEvento($pdo)
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $id = $input['id'] ?? '';
        $nome = $input['nome'] ?? '';
        $data = $input['data'] ?? '';
        $hora = $input['hora'] ?? null;
        $local = $input['local'] ?? '';
        $tipo = $input['tipo'] ?? 'Evento';

        if (empty($id) || empty($nome) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID, nome e data são obrigatórios']);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE eventos 
            SET nome_evento = ?, data_evento = ?, hora_evento = ?, local_evento = ?, descricao = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$nome, $data, $hora, $local, $tipo, $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Evento atualizado com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Evento não encontrado']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar evento: ' . $e->getMessage()]);
    }
}


function deletarEvento($pdo)
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do evento é obrigatório']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM eventos WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Evento deletado com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Evento não encontrado']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao deletar evento: ' . $e->getMessage()]);
    }
}
?>
