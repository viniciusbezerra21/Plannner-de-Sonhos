<?php
session_start();
require_once "conexao.php";
require_once "crypto.php";

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Puxa os dados do banco
$sql = "SELECT nome, nome_conj, email, num_telefone, foto_perfil, senha_hash 
        FROM usuario 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Descriptografa os campos
$nome        = descriptografar($usuario['nome']);
$nomeConj    = descriptografar($usuario['nome_conj']);
$email       = descriptografar($usuario['email']);
$numTelefone = descriptografar($usuario['num_telefone']);
$fotoPerfil  = $usuario['foto_perfil'];

// Iniciais caso não tenha foto
$iniciais = strtoupper(substr($nome, 0, 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoNome      = $_POST['nome'];
    $novoNomeConj  = $_POST['nome_conj'];
    $novoEmail     = $_POST['email'];
    $novoTelefone  = $_POST['telefone'];
    $novaSenha     = $_POST['senha'];
    
    $novaFotoPerfil = $fotoPerfil;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $tipoArquivo = $_FILES['foto']['type'];
        
        if (in_array($tipoArquivo, $tiposPermitidos)) {
            // Cria o diretório se não existir
            if (!is_dir("uploads/perfil")) {
                mkdir("uploads/perfil", 0777, true);
            }
            
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $novoNomeFoto = "perfil_" . $usuario_id . "_" . time() . "." . $ext;
            $destino = "uploads/perfil/" . $novoNomeFoto;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                if (!empty($fotoPerfil) && file_exists("uploads/perfil/" . $fotoPerfil)) {
                    unlink("uploads/perfil/" . $fotoPerfil);
                }
                $novaFotoPerfil = $novoNomeFoto;
            } else {
                echo "<script>alert('Erro ao fazer upload da foto.');</script>";
            }
        } else {
            echo "<script>alert('Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.');</script>";
        }
    }

    // Criptografa valores
    $nomeCripto      = criptografar($novoNome);
    $nomeConjCripto  = criptografar($novoNomeConj);
    $emailCripto     = criptografar($novoEmail);
    $telefoneCripto  = criptografar($novoTelefone);

    if (!empty($novaSenha)) {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuario SET nome=?, nome_conj=?, email=?, num_telefone=?, foto_perfil=?, senha_hash=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nomeCripto, $nomeConjCripto, $emailCripto, $telefoneCripto, $novaFotoPerfil, $senhaHash, $usuario_id);
    } else {
        $stmt = $conn->prepare("UPDATE usuario SET nome=?, nome_conj=?, email=?, num_telefone=?, foto_perfil=? WHERE id=?");
        $stmt->bind_param("sssssi", $nomeCripto, $nomeConjCripto, $emailCripto, $telefoneCripto, $novaFotoPerfil, $usuario_id);
    }

    if ($stmt->execute()) {
        $_SESSION['usuario_logado']['nome'] = $novoNome;
        $_SESSION['usuario_logado']['nome_conj'] = $novoNomeConj;
        $_SESSION['usuario_logado']['email'] = $novoEmail;
        $_SESSION['usuario_logado']['num_telefone'] = $novoTelefone;
        $_SESSION['usuario_logado']['foto_perfil'] = $novaFotoPerfil;
        
        $_SESSION['mensagem_sucesso'] = 'Perfil atualizado com sucesso!';
        header("Location: perfil.php?atualizado=1");
        exit;
    } else {
        echo "<script>alert('Erro ao atualizar perfil: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Perfil - WeddingEasy</title>
<link rel="stylesheet" href="../Style/styles.css">
<style>
    .edit-profile-card { max-width: 600px; margin: 6rem auto 3rem; padding: 2.5rem; background: white; border: 1px solid hsl(var(--border)); border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.08); text-align: center; }
    .profile-photo img, .profile-placeholder { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; box-shadow: 0 6px 15px rgba(0,0,0,0.15); margin: 0 auto; display: block; }
    .profile-placeholder { display: flex; align-items: center; justify-content: center; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); font-size: 3rem; font-weight: bold; user-select: none; }
    .custom-file { margin-top: 1rem; }
    .custom-file input[type="file"] { display: none; }
    .custom-file label { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); padding: 0.6rem 1.2rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; display: inline-block; transition: all 0.3s; }
    .custom-file label:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.15); }
    .form-group { margin-bottom: 1rem; text-align: left; }
    .form-group label { font-weight: 600; margin-bottom: 0.5rem; display: block; color: hsl(var(--foreground)); }
    .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; transition: all 0.2s; }
    .form-group input:focus { outline: none; border-color: hsl(var(--primary)); box-shadow: 0 0 0 3px hsl(var(--primary)/0.2); }
    .btn-submit { display: block; width: 100%; margin-top: 1.5rem; padding: 0.75rem; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
</style>
</head>
<body>
<main class="container">
    <div class="edit-profile-card">
        <h2>Editar Perfil</h2>
        <div class="profile-photo">
            <?php if (!empty($fotoPerfil)): ?>
                <img id="fotoPreview" src="uploads/perfil/<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Foto de perfil">
            <?php else: ?>
                <div id="fotoPlaceholder" class="profile-placeholder"><?php echo $iniciais; ?></div>
            <?php endif; ?>
            <div class="custom-file">
                <input type="file" id="foto" name="foto" accept="image/*" onchange="previewFoto(event)">
                <label for="foto">Trocar Foto de Perfil</label>
            </div>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <!-- Removido campo duplicado, usando apenas um campo de arquivo -->
            
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            </div>
            <div class="form-group">
                <label for="nome_conj">Nome do Cônjuge</label>
                <input type="text" id="nome_conj" name="nome_conj" value="<?php echo htmlspecialchars($nomeConj); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($numTelefone); ?>" required>
            </div>
            <div class="form-group">
                <label for="senha">Nova Senha (opcional)</label>
                <input type="password" id="senha" name="senha" placeholder="Deixe em branco para manter a senha atual">
            </div>
            <button type="submit" class="btn-submit">Salvar Alterações</button>
        </form>
    </div>
</main>
<script>
function previewFoto(event) {
    const file = event.target.files[0];
    if (file) {
        const foto = document.getElementById('fotoPreview');
        const placeholder = document.getElementById('fotoPlaceholder');
        
        if (placeholder) placeholder.style.display = 'none';
        
        if (foto) {
            foto.src = URL.createObjectURL(file);
            foto.style.display = 'block';
        } else {
            const img = document.createElement('img');
            img.id = 'fotoPreview';
            img.src = URL.createObjectURL(file);
            img.style.width = "120px";
            img.style.height = "120px";
            img.style.borderRadius = "50%";
            img.style.objectFit = "cover";
            img.style.boxShadow = "0 6px 15px rgba(0,0,0,0.15)";
            document.querySelector('.profile-photo').prepend(img);
        }
    }
}
</script>
</body>
</html>
