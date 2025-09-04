<?php
session_start();
require_once 'crypto.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "weddingeasy";

$conn = new mysqli($hostname, $user, $password, $database);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$id = $_SESSION['usuario_id'];
$mensagem = "";

// PROCESSAR DADOS QUANDO O FORMULÁRIO FOR ENVIADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $senha = $_POST['senha'];

    if (empty($nome)) {
        $mensagem = "O nome não pode ficar vazio.";
    } else {
        try {
            $nomeCript = criptografar($nome);

            // Upload da foto
            $foto_perfil_path = null;
            if (!empty($_FILES['foto_perfil']['name'])) {
                if ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($_FILES['foto_perfil']['type'], $allowed_types)) {
                        $upload_dir = "uploads/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                        $foto_perfil_path = $upload_dir . uniqid() . "." . $ext;

                        if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $foto_perfil_path)) {
                            $mensagem = "Falha ao salvar a foto.";
                        }
                    } else {
                        $mensagem = "Tipo de arquivo não permitido. Use JPG, PNG ou GIF.";
                    }
                } else {
                    $mensagem = "Erro no upload da foto. Código: " . $_FILES['foto_perfil']['error'];
                }
            }

            if ($mensagem === "") {
                // Atualizar apenas o nome e telefone primeiro
                $sql = "UPDATE usuario SET nome = ?, num_telefone = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    throw new Exception("Erro no prepare: " . $conn->error);
                }
                
                $stmt->bind_param("ssi", $nomeCript, $telefone, $id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro na execução: " . $stmt->error);
                }
                
                $stmt->close();

                // Atualizar senha se fornecida
                if (!empty($senha)) {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $sql2 = "UPDATE usuario SET senha_hash = ? WHERE id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    
                    if (!$stmt2) {
                        throw new Exception("Erro no prepare senha: " . $conn->error);
                    }
                    
                    $stmt2->bind_param("si", $senha_hash, $id);
                    $stmt2->execute();
                    $stmt2->close();
                }

                // Atualizar foto se fornecida
                if ($foto_perfil_path) {
                    $sql3 = "UPDATE usuario SET foto_perfil = ? WHERE id = ?";
                    $stmt3 = $conn->prepare($sql3);
                    
                    if (!$stmt3) {
                        throw new Exception("Erro no prepare foto: " . $conn->error);
                    }
                    
                    $stmt3->bind_param("si", $foto_perfil_path, $id);
                    $stmt3->execute();
                    $stmt3->close();
                }

                // Redirecionar imediatamente para perfil.php após salvar
                header("Location: perfil.php");
                exit;
            }
        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
        }
    }
}

// BUSCAR DADOS ATUAIS DO USUÁRIO
try {
    $sql = "SELECT nome, num_telefone, foto_perfil, created_at FROM usuario WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro no prepare SELECT: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nomeCript = $row['nome'];
        $telefone = $row['num_telefone'] ?? '';
        $foto_perfil = $row['foto_perfil'];
        $data_cadastro = $row['created_at'] ?? null;
    } else {
        throw new Exception("Usuário não encontrado");
    }
    
    $stmt->close();
} catch (Exception $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

$conn->close();

$nome = descriptografar($nomeCript);
$telefone = !empty($telefone) ? descriptografar($telefone) : '';
$foto_final = (!empty($foto_perfil)) ? htmlspecialchars($foto_perfil) : 'uploads/default.png';

// Formatar data de cadastro
$data_cadastro_formatada = '';
if ($data_cadastro) {
    $data_obj = new DateTime($data_cadastro);
    $data_cadastro_formatada = $data_obj->format('d/m/Y H:i');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
            text-align: center;
            padding-top: 50px;
        }
        
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            transition: transform 0.5s ease;
        }
        
        .profile-container {
            display: inline-block;
            position: relative;
            margin-bottom: 20px;
            transition: all 0.5s ease;
        }
        
        .profile-container.moved-up {
            transform: translateY(-30px);
        }
        
        .edit-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: #444;
            border-radius: 50%;
            padding: 8px;
            cursor: pointer;
            border: 2px solid white;
            font-size: 14px;
        }
        
        .edit-icon:hover {
            background-color: #666;
        }
        
        .form-container {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.5s ease, opacity 0.5s ease;
        }
        
        .form-container.show {
            max-height: 800px;
            opacity: 1;
            margin-top: 20px;
        }
        
        form {
            display: inline-block;
            text-align: left;
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .field-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }
        
        .field-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .field-group input {
            width: 250px;
            padding: 8px;
            border-radius: 5px;
            border: none;
            background-color: #333;
            color: white;
        }
        
        .edit-field-icon {
            margin-left: 10px;
            cursor: pointer;
            color: #ccc;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .edit-field-icon:hover {
            color: #fff;
        }
        
        input[type="submit"] {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            background-color: #444;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }
        
        input[type="submit"]:hover {
            background-color: #666;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .message.success {
            background-color: #2d5a2d;
            color: #90ee90;
            border: 1px solid #90ee90;
        }
        
        .message.error {
            background-color: #5a2d2d;
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
        }
        
        .redirect-message {
            margin-top: 8px;
            color: #90ee90;
            font-size: 14px;
            opacity: 0.8;
        }
        
        #editBtn {
            background-color: #444;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        #editBtn:hover {
            background-color: #666;
        }
    </style>
</head>
<body>

    <h1>Meu Perfil</h1>
    
    <?php if($mensagem): ?>
        <div class="message <?= strpos(strtolower($mensagem), 'sucesso') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($mensagem) ?>
            <?php if(strpos(strtolower($mensagem), 'sucesso') !== false): ?>
                <div class="redirect-message">🔄 Redirecionando para o perfil em 2 segundos...</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-container" id="profileContainer">
        <img src="<?php echo $foto_final; ?>" alt="Foto de perfil" id="profilePic" class="profile-pic">
        <label for="fotoInput" class="edit-icon" title="Alterar foto">✏️</label>
    </div>

    <br>
    <button id="editBtn">Customizar Perfil</button>

    <div class="form-container" id="formContainer">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" id="fotoInput" name="foto_perfil" accept="image/*">
            
            <div class="field-group">
                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
                <span class="edit-field-icon" title="Editar nome">✏️</span>
            </div>
            
            <div class="field-group">
                <div>
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">
                </div>
                <span class="edit-field-icon" title="Editar telefone">✏️</span>
            </div>
            
            <div class="field-group">
                <div>
                    <label for="senha">Nova Senha:</label>
                    <input type="password" id="senha" name="senha" placeholder="Deixe vazio para não alterar">
                </div>
                <span class="edit-field-icon" title="Editar senha">✏️</span>
            </div>
            
            <?php if($data_cadastro_formatada): ?>
            <div style="margin-top: 15px; padding: 15px; background-color: #333; border-radius: 8px; color: #ccc; border-left: 4px solid #666;">
                <strong>📅 Data de Cadastro:</strong> <?= $data_cadastro_formatada ?>
            </div>
            <?php endif; ?>
            
            <input type="submit" value="Salvar Alterações">
        </form>
    </div>

    <script>
        const editBtn = document.getElementById('editBtn');
        const formContainer = document.getElementById('formContainer');
        const profileContainer = document.getElementById('profileContainer');
        const fotoInput = document.getElementById('fotoInput');
        const profilePic = document.getElementById('profilePic');

        // Toggle do formulário de edição
        editBtn.addEventListener('click', () => {
            formContainer.classList.toggle('show');
            profileContainer.classList.toggle('moved-up');
            
            if (formContainer.classList.contains('show')) {
                editBtn.textContent = 'Fechar Edição';
            } else {
                editBtn.textContent = 'Customizar Perfil';
            }
        });

        // Preview da foto selecionada
        fotoInput.addEventListener('change', () => {
            const file = fotoInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePic.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Clique no ícone de edição da foto
        document.querySelector('.edit-icon').addEventListener('click', () => {
            fotoInput.click();
        });
    </script>

</body>
</html>