<?php
session_start();
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../user/login-unified.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$conversa_id = (int)($_GET['conversa_id'] ?? 0);

$mensagens = [];
$usuario_conversa = null;

if ($conversa_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$conversa_id]);
    $usuario_conversa = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome as remetente_nome, u.foto_perfil as remetente_foto
        FROM mensagens m
        JOIN usuarios u ON u.id_usuario = m.remetente_id
        WHERE (m.remetente_id = ? AND m.destinatario_id = ?) 
           OR (m.remetente_id = ? AND m.destinatario_id = ?)
        ORDER BY m.data_envio ASC
    ");
    $stmt->execute([$id_usuario, $conversa_id, $conversa_id, $id_usuario]);
    $mensagens = $stmt->fetchAll();
    
    // Marcar como lidas
    $stmt = $pdo->prepare("UPDATE mensagens SET lida = 1 WHERE destinatario_id = ? AND remetente_id = ?");
    $stmt->execute([$id_usuario, $conversa_id]);
}

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - WeddingEasy</title>
    <link rel="stylesheet" href="../Style/style.css">
    <link rel="stylesheet" href="../Style/responsive.css">
    <style>
        .mensagens-container {
            display: flex;
            height: 85vh;
            gap: 0;
            background: #f5f5f5;
        }
        
        .lista-conversas {
            width: 30%;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            background: white;
        }
        
        .conversa-item {
            position: relative;
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .conversa-item:hover,
        .conversa-item.ativa {
            background: #f0f0f0;
        }
        
        .avatar-conversa {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #ddd;
            object-fit: cover;
        }
        
        .info-conversa {
            flex: 1;
            min-width: 0;
        }
        
        .nome-conversa {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .tipo-badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            background: #e8f4f8;
            color: #0084a0;
            margin-top: 4px;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mensagens-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .mensagem {
            display: flex;
            gap: 10px;
            max-width: 70%;
        }
        
        .mensagem.enviada {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .mensagem.recebida {
            align-self: flex-start;
        }
        
        .msg-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            word-break: break-word;
        }
        
        .mensagem.enviada .msg-bubble {
            background: #0084a0;
            color: white;
        }
        
        .mensagem.recebida .msg-bubble {
            background: #e8f4f8;
            color: #333;
        }
        
        .msg-time {
            font-size: 12px;
            color: #999;
            align-self: flex-end;
        }
        
        .chat-input-area {
            padding: 15px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }
        
        .chat-input-area textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
            font-family: Arial, sans-serif;
            max-height: 100px;
        }
        
        .chat-input-area button {
            padding: 10px 20px;
            background: #0084a0;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .vazio-mensagem {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            font-size: 18px;
        }
        
        .badge-nao-lidas {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .scroll-to-bottom {
            position: sticky;
            bottom: 80px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: #0084a0;
            color: white;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 10;
            align-self: flex-end;
        }
        
        .scroll-to-bottom.visible {
            display: flex;
        }
        
        @media (max-width: 768px) {
            .mensagens-container {
                height: auto;
            }
            
            .lista-conversas {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                max-height: 200px;
                overflow-x: auto;
            }
            
            .conversa-item {
                min-width: 200px;
            }
            
            .chat-area {
                width: 100%;
            }
            
            .mensagem {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <div class="container" style="margin-top: 40px;">
        <h1>Mensagens</h1>
        
        <div class="mensagens-container">
            <div class="lista-conversas">
                <?php if (empty($conversas)): ?>
                    <div style="padding: 20px; text-align: center; color: #999;">
                        Nenhuma conversa ainda
                    </div>
                <?php else: ?>
                    <?php foreach ($conversas as $conversa): ?>
                        <div class="conversa-item <?php echo $conversa_id === $conversa['outro_usuario_id'] ? 'ativa' : ''; ?>" 
                             onclick="window.location.href = '?conversa_id=<?php echo $conversa['outro_usuario_id']; ?>'">
                            <img src="../user/fotos/<?php echo $conversa['foto_perfil'] ?? 'default.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($conversa['nome']); ?>" 
                                 class="avatar-conversa">
                            <div class="info-conversa">
                                <div class="nome-conversa"><?php echo htmlspecialchars($conversa['nome']); ?></div>
                                <span class="tipo-badge">
                                    <?php 
                                    $tipos = ['cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'cerimonialista' => 'Cerimonialista'];
                                    echo $tipos[$conversa['tipo_usuario']] ?? $conversa['tipo_usuario'];
                                    ?>
                                </span>
                            </div>
                            <?php if ($conversa['nao_lidas'] > 0): ?>
                                <span class="badge-nao-lidas"><?php echo $conversa['nao_lidas']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-area">
                <?php if ($conversa_id === 0): ?>
                    <div class="vazio-mensagem">
                        Selecione uma conversa para começar
                    </div>
                <?php else: ?>
                    <div class="chat-header">
                        <h3><?php echo htmlspecialchars($usuario_conversa['nome'] ?? 'Conversa'); ?></h3>
                        <span class="tipo-badge">
                            <?php 
                            $tipos = ['cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'cerimonialista' => 'Cerimonialista'];
                            echo $tipos[$usuario_conversa['tipo_usuario']] ?? $usuario_conversa['tipo_usuario'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="mensagens-list" id="mensagens-list">
                        <?php foreach ($mensagens as $msg): ?>
                            <div class="mensagem <?php echo $msg['remetente_id'] === $id_usuario ? 'enviada' : 'recebida'; ?>">
                                <div class="msg-bubble">
                                    <?php if ($msg['assunto'] !== 'Mensagem'): ?>
                                        <strong><?php echo htmlspecialchars($msg['assunto']); ?></strong><br>
                                    <?php endif; ?>
                                    <?php echo nl2br(htmlspecialchars($msg['conteudo'])); ?>
                                </div>
                                <div class="msg-time">
                                    <?php 
                                    $data = strtotime($msg['data_envio']);
                                    $hoje = strtotime('today');
                                    if ($data >= $hoje) {
                                        echo date('H:i', $data);
                                    } else {
                                        echo date('d/m H:i', $data);
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <button class="scroll-to-bottom" onclick="scrollToBottom()">↓</button>
                    </div>
                    
                    <form method="POST" action="../api/mensagens.php" class="chat-input-area" onsubmit="return enviarMensagem(event)">
                        <input type="hidden" name="acao" value="enviar">
                        <input type="hidden" name="destinatario_id" value="<?php echo $conversa_id; ?>">
                        <input type="hidden" name="assunto" value="Mensagem">
                        <textarea id="input-mensagem" name="conteudo" placeholder="Digite sua mensagem..." required 
                                  onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();this.form.requestSubmit();}"></textarea>
                        <button type="submit">Enviar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
        const mensagensList = document.getElementById('mensagens-list');
        const scrollBtn = document.querySelector('.scroll-to-bottom');
        
        function scrollToBottom(smooth = false) {
            if (mensagensList) {
                mensagensList.scrollTo({
                    top: mensagensList.scrollHeight,
                    behavior: smooth ? 'smooth' : 'auto'
                });
            }
        }
        
        // Scroll inicial
        scrollToBottom();
        
        // Monitorar scroll para mostrar botão
        if (mensagensList) {
            mensagensList.addEventListener('scroll', function() {
                const isAtBottom = mensagensList.scrollHeight - mensagensList.scrollTop - mensagensList.clientHeight < 100;
                if (scrollBtn) {
                    scrollBtn.classList.toggle('visible', !isAtBottom);
                }
            });
        }
        
        function enviarMensagem(event) {
            const input = document.getElementById('input-mensagem');
            if (input.value.trim() === '') {
                event.preventDefault();
                return false;
            }
            
            // Limpar input após envio
            setTimeout(() => {
                input.value = '';
                scrollToBottom(true);
            }, 100);
            
            return true;
        }
        
        // Auto-refresh a cada 10 segundos
        setInterval(() => {
            const conversaId = <?php echo $conversa_id; ?>;
            if (conversaId > 0) {
                fetch(`../api/mensagens.php?acao=carregar_mensagens&outro_usuario_id=${conversaId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso && data.mensagens.length > <?php echo count($mensagens ?? []); ?>) {
                            location.reload();
                        }
                    });
            }
        }, 10000);
    </script>
</body>
</html>
