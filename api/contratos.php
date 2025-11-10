<?php
header("Content-Type: application/json");
session_start();
require_once "../config/conexao.php";

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autenticado"]);
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];
$cargo = $_SESSION['cargo'] ?? 'cliente';
$metodo = $_SERVER["REQUEST_METHOD"];

// === LISTAR CONTRATOS ===
if ($metodo === "GET") {
    try {
        if ($cargo === 'cerimonialista') {
            // Cerimonialista vê contratos que ele criou
            $stmt = $pdo->prepare("
                SELECT c.*, u.nome as nome_cliente, u.email as email_cliente 
                FROM contratos c 
                JOIN usuarios u ON c.id_usuario = u.id_usuario 
                WHERE c.id_cerimonialista = ? 
                ORDER BY c.data_criacao DESC
            ");
            $stmt->execute([$idUsuario]);
        } else {
            // Cliente vê contratos enviados para ele
            $stmt = $pdo->prepare("
                SELECT c.*, u.nome as nome_cerimonialista 
                FROM contratos c 
                JOIN usuarios u ON c.id_cerimonialista = u.id_usuario 
                WHERE c.id_usuario = ? 
                ORDER BY c.data_criacao DESC
            ");
            $stmt->execute([$idUsuario]);
        }
        
        $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "sucesso", "dados" => $contratos]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao buscar contratos: " . $e->getMessage()]);
    }
}

// === CRIAR CONTRATO (apenas cerimonialista) ===
elseif ($metodo === "POST" && isset($_POST['action']) && $_POST['action'] === 'create') {
    if ($cargo !== 'cerimonialista') {
        http_response_code(403);
        echo json_encode(["erro" => "Apenas cerimonialistas podem criar contratos"]);
        exit;
    }

    try {
        $id_cliente = (int) $_POST['id_cliente'];
        $nome_contrato = trim($_POST['nome_contrato']);
        $descricao = trim($_POST['descricao'] ?? '');
        
        // Upload do arquivo
        $arquivo_contrato = '';
        if (isset($_FILES['arquivo_contrato']) && $_FILES['arquivo_contrato']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../contratos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['arquivo_contrato']['name'], PATHINFO_EXTENSION);
            $fileName = 'contrato_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['arquivo_contrato']['tmp_name'], $uploadPath)) {
                $arquivo_contrato = $fileName;
            } else {
                throw new Exception("Erro ao fazer upload do arquivo");
            }
        } else {
            throw new Exception("Arquivo de contrato é obrigatório");
        }

        $stmt = $pdo->prepare("
            INSERT INTO contratos (id_usuario, id_cerimonialista, nome_contrato, descricao, arquivo_contrato, status) 
            VALUES (?, ?, ?, ?, ?, 'pendente')
        ");
        $stmt->execute([$id_cliente, $idUsuario, $nome_contrato, $descricao, $arquivo_contrato]);
        
        echo json_encode([
            "status" => "sucesso", 
            "mensagem" => "Contrato criado com sucesso",
            "id_contrato" => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

// === ASSINAR CONTRATO (apenas cliente) ===
elseif ($metodo === "POST" && isset($_POST['action']) && $_POST['action'] === 'sign') {
    if ($cargo !== 'cliente') {
        http_response_code(403);
        echo json_encode(["erro" => "Apenas clientes podem assinar contratos"]);
        exit;
    }

    try {
        $id_contrato = (int) $_POST['id_contrato'];
        
        // Verificar se o contrato pertence ao usuário
        $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_contrato = ? AND id_usuario = ? AND status = 'pendente'");
        $stmt->execute([$id_contrato, $idUsuario]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contrato) {
            throw new Exception("Contrato não encontrado ou já foi processado");
        }
        
        // Upload do arquivo assinado
        $arquivo_assinado = '';
        if (isset($_FILES['arquivo_assinado']) && $_FILES['arquivo_assinado']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../contratos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['arquivo_assinado']['name'], PATHINFO_EXTENSION);
            $fileName = 'assinado_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['arquivo_assinado']['tmp_name'], $uploadPath)) {
                $arquivo_assinado = $fileName;
            } else {
                throw new Exception("Erro ao fazer upload do arquivo assinado");
            }
        } else {
            throw new Exception("Arquivo assinado é obrigatório");
        }

        $stmt = $pdo->prepare("
            UPDATE contratos 
            SET arquivo_assinado = ?, status = 'assinado', data_assinatura = NOW() 
            WHERE id_contrato = ?
        ");
        $stmt->execute([$arquivo_assinado, $id_contrato]);
        
        echo json_encode([
            "status" => "sucesso", 
            "mensagem" => "Contrato assinado com sucesso"
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

// === REJEITAR CONTRATO (apenas cliente) ===
elseif ($metodo === "POST" && isset($_POST['action']) && $_POST['action'] === 'reject') {
    if ($cargo !== 'cliente') {
        http_response_code(403);
        echo json_encode(["erro" => "Apenas clientes podem rejeitar contratos"]);
        exit;
    }

    try {
        $id_contrato = (int) $_POST['id_contrato'];
        
        $stmt = $pdo->prepare("
            UPDATE contratos 
            SET status = 'rejeitado' 
            WHERE id_contrato = ? AND id_usuario = ? AND status = 'pendente'
        ");
        $stmt->execute([$id_contrato, $idUsuario]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "status" => "sucesso", 
                "mensagem" => "Contrato rejeitado"
            ]);
        } else {
            throw new Exception("Contrato não encontrado ou já foi processado");
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

// === DELETAR CONTRATO ===
elseif ($metodo === "DELETE" || (isset($_POST['action']) && $_POST['action'] === 'delete')) {
    try {
        $id_contrato = 0;
        
        if ($metodo === "DELETE") {
            parse_str(file_get_contents("php://input"), $params);
            $id_contrato = (int) ($params['id_contrato'] ?? 0);
        } else {
            $id_contrato = (int) $_POST['id_contrato'];
        }
        
        // Verificar permissão
        if ($cargo === 'cerimonialista') {
            $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_contrato = ? AND id_cerimonialista = ?");
            $stmt->execute([$id_contrato, $idUsuario]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id_contrato = ? AND id_usuario = ?");
            $stmt->execute([$id_contrato, $idUsuario]);
        }
        
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contrato) {
            throw new Exception("Contrato não encontrado ou sem permissão");
        }
        
        // Deletar arquivos
        if ($contrato['arquivo_contrato']) {
            $filePath = '../contratos/' . $contrato['arquivo_contrato'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        if ($contrato['arquivo_assinado']) {
            $filePath = '../contratos/' . $contrato['arquivo_assinado'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Deletar registro
        $stmt = $pdo->prepare("DELETE FROM contratos WHERE id_contrato = ?");
        $stmt->execute([$id_contrato]);
        
        echo json_encode([
            "status" => "sucesso", 
            "mensagem" => "Contrato excluído com sucesso"
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido"]);
}
?>
