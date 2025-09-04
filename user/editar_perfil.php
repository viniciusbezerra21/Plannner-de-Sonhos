<?php
session_start();
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/session_helper.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $senha = $_POST['senha'];

    if (empty($nome)) {
        $mensagem = "O nome não pode ficar vazio.";
    } else {
        $nomeCript = criptografar($nome);

        // Upload da foto
        $foto_perfil_path = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            if ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($_FILES['foto_perfil']['type'], $allowed_types)) {
                    $upload_dir = "uploads/perfil/";

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
            // Verificar qual campo de telefone existe
            $telefone_field = 'num_telefone'; // padrão
            
            $check_query = "SHOW COLUMNS FROM usuario LIKE '%telefone%'";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                $telefone_row = $check_result->fetch_assoc();
                $telefone_field = $telefone_row['Field'];
            }
            
            // Criptografa o telefone antes de salvar
            $telefone_cript = criptografar($telefone);
            
            // Monta query dinâmica
            $query = "UPDATE usuario SET nome = ?, $telefone_field = ?";
            $params = [$nomeCript, $telefone_cript];
            $types = "ss";

            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $query .= ", senha_hash = ?";
                $params[] = $senha_hash;
                $types .= "s";
            }

            if ($foto_perfil_path) {
                $query .= ", foto_perfil = ?";
                $params[] = $foto_perfil_path;
                $types .= "s";
            }

            $query .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                die("Erro no prepare(): " . $conn->error . "<br>Query: " . $query);
            }

            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                die("Erro na execução: " . $stmt->error);
            }

            $stmt->close();
            
            // IMPORTANTE: Atualizar dados da sessão
            atualizarSessaoUsuario($id, $conn);
            
            // Redirecionar para index.php para ver as mudanças
            header("Location: ../index.php");
            exit;
        }
    }
}

// Busca dados atuais - primeiro descobrir o nome do campo telefone
$telefone_field = 'num_telefone'; // padrão

$check_query = "SHOW COLUMNS FROM usuario LIKE '%telefone%'";
$check_result = $conn->query($check_query);

if ($check_result && $check_result->num_rows > 0) {
    $telefone_row = $check_result->fetch_assoc();
    $telefone_field = $telefone_row['Field'];
}

$sql = "SELECT nome, $telefone_field as telefone_valor, foto_perfil, created_at FROM usuario WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro no prepare SELECT: " . $conn->error . "<br>SQL: " . $sql);
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    die("Erro na execução SELECT: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nomeCript = $row['nome'];
    $telefone = $row['telefone_valor'] ?? '';
    $foto_perfil = $row['foto_perfil'];
    $data_cadastro = $row['created_at'] ?? null;
} else {
    die("Usuário não encontrado");
}

$stmt->close();
$conn->close();

$nome = descriptografar($nomeCript);
$telefone = !empty($telefone) ? descriptografar($telefone) : '';
$foto_final = !empty($foto_perfil) ? htmlspecialchars($foto_perfil) : 'uploads/default.png';

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
<meta charset="UTF-8" />
<title>Editar Perfil</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #111;
        color: white;
        text-align: center;
        padding-top: 40px;
    }
    .profile-pic {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        margin-bottom: 10px;
        cursor: pointer;
    }
    form {
        display: inline-block;
        text-align: left;
        background-color: #222;
        padding: 20px;
        border-radius: 10px;
    }
    label {
        display: block;
        margin-top: 10px;
    }
    input[type="text"], input[type="tel"], input[type="password"], input[type="file"] {
        width: 250px;
        padding: 8px;
        border-radius: 5px;
        border: none;
        margin-top: 5px;
        background-color: #333;
        color: white;
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
    }
    input[type="submit"]:hover {
        background-color: #666;
    }
    input[type="file"] {
        display: none;
    }
    .edit-icon {
        display: inline-block;
        background-color: #444;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        margin-bottom: 10px;
    }
    .message {
        margin-bottom: 15px;
        font-weight: bold;
        padding: 10px;
        border-radius: 5px;
    }
    .message.success {
        color: #0f0;
        background-color: #003300;
        border: 1px solid #0f0;
    }
    .message.error {
        color: #f44;
        background-color: #330000;
        border: 1px solid #f44;
    }
    .back-btn {
        position: absolute;
        top: 20px;
        left: 20px;
        background-color: #444;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
    }
    .back-btn:hover {
        background-color: #666;
    }
</style>
</head>
<body>

<a href="../index.php" class="back-btn">← Voltar ao Início</a>

<h1>Editar Perfil</h1>

<?php if($mensagem): ?>
    <div class="message <?= strpos(strtolower($mensagem), 'sucesso') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($mensagem) ?>
    </div>
<?php endif; ?>

<label for="fotoInput" class="edit-icon">Alterar foto ✏️</label><br>
<img src="<?= $foto_final ?>" alt="Foto de perfil" id="profilePic" class="profile-pic" title="Clique para alterar foto">

<form action="" method="post" enctype="multipart/form-data" id="formPerfil">
    <input type="file" id="fotoInput" name="foto_perfil" accept="image/*">

    <label for="nome">Nome: ✏️</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

    <label for="telefone">Telefone: ✏️</label>
    <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone) ?>">

    <label for="senha">Senha (deixe vazio para não alterar): ✏️</label>
    <input type="password" id="senha" name="senha" placeholder="Nova senha">
    
    <?php if($data_cadastro_formatada): ?>
    <div style="margin-top: 15px; padding: 10px; background-color: #333; border-radius: 5px; color: #ccc;">
        <strong>Data de Cadastro:</strong> <?= $data_cadastro_formatada ?>
    </div>
    <?php endif; ?>

    <input type="submit" value="Salvar Alterações">
</form>

<script>
    const fotoInput = document.getElementById('fotoInput');
    const profilePic = document.getElementById('profilePic');
    const labelFoto = document.querySelector('.edit-icon');

    labelFoto.addEventListener('click', () => {
        fotoInput.click();
    });

    fotoInput.addEventListener('change', () => {
        const file = fotoInput.files[0];
        if (file) {
            profilePic.src = URL.createObjectURL(file);
        }
    });

    profilePic.addEventListener('click', () => {
        fotoInput.click();
    });
</script>

</body>
</html>