<?php
require_once '../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo '';
    exit();
}

$limite = $_GET['limite'] ?? 5;

$stmt = $pdo->prepare("
    SELECT hi.*, 
           CASE 
               WHEN hi.usuario_1_id = ? THEN u2.nome
               ELSE u1.nome
           END as outro_usuario_nome,
           CASE 
               WHEN hi.usuario_1_id = ? THEN u2.foto_perfil
               ELSE u1.foto_perfil
           END as outro_usuario_foto
    FROM historico_interacoes hi
    LEFT JOIN usuarios u1 ON u1.id_usuario = hi.usuario_1_id
    LEFT JOIN usuarios u2 ON u2.id_usuario = hi.usuario_2_id
    WHERE hi.usuario_1_id = ? OR hi.usuario_2_id = ?
    ORDER BY hi.data_interacao DESC
    LIMIT :limite
");
$stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
$stmt->execute([$_SESSION['id_usuario'], $_SESSION['id_usuario'], $_SESSION['id_usuario'], $_SESSION['id_usuario']]);
$interacoes = $stmt->fetchAll();
?>

<style>
    .historico-interacoes {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .historico-titulo {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .historico-titulo a {
        font-size: 13px;
        color: #0084a0;
        text-decoration: none;
        font-weight: 500;
    }
    
    .historico-titulo a:hover {
        text-decoration: underline;
    }
    
    .interacao-item {
        padding: 12px;
        border-left: 3px solid #0084a0;
        margin-bottom: 12px;
        background: #f9f9f9;
        border-radius: 6px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        transition: all 0.3s;
    }
    
    .interacao-item:hover {
        background: #f0f0f0;
        transform: translateX(3px);
    }
    
    .interacao-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ddd;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .interacao-info {
        flex: 1;
        min-width: 0;
    }
    
    .interacao-descricao {
        font-size: 14px;
        color: #333;
        margin-bottom: 4px;
        word-break: break-word;
    }
    
    .interacao-tipo {
        display: inline-block;
        padding: 2px 8px;
        background: #e8f4f8;
        color: #0084a0;
        border-radius: 12px;
        font-size: 11px;
        margin-right: 8px;
        font-weight: 600;
    }
    
    .interacao-data {
        font-size: 12px;
        color: #999;
    }
    
    .historico-vazio {
        text-align: center;
        color: #999;
        padding: 20px;
        font-size: 14px;
    }
</style>

<div class="historico-interacoes">
    <div class="historico-titulo">
        <span>Interações Recentes</span>
        <a href="../pages/historico.php">Ver todas</a>
    </div>
    
    <?php if (empty($interacoes)): ?>
        <div class="historico-vazio">
            Nenhuma interação ainda
        </div>
    <?php else: ?>
        <?php foreach ($interacoes as $interacao): ?>
            <div class="interacao-item">
                <img src="../user/fotos/<?php echo $interacao['outro_usuario_foto'] ?? 'default.png'; ?>" 
                     alt="<?php echo htmlspecialchars($interacao['outro_usuario_nome'] ?? 'Usuário'); ?>" 
                     class="interacao-avatar"
                     onerror="this.src='../user/fotos/default.png'">
                
                <div class="interacao-info">
                    <div class="interacao-descricao">
                        <strong><?php echo htmlspecialchars($interacao['outro_usuario_nome'] ?? 'Usuário'); ?></strong>
                        <?php if ($interacao['descricao']): ?>
                            - <?php echo htmlspecialchars($interacao['descricao']); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="interacao-tipo">
                            <?php 
                            $tipos_interacao = [
                                'mensagem' => 'Mensagem',
                                'contrato' => 'Contrato',
                                'avaliacao' => 'Avaliação',
                                'reuniao' => 'Reunião',
                                'evento' => 'Evento',
                                'candidatura' => 'Candidatura'
                            ];
                            echo $tipos_interacao[$interacao['tipo_interacao']] ?? $interacao['tipo_interacao'];
                            ?>
                        </span>
                        <span class="interacao-data">
                            <?php 
                            $data = new DateTime($interacao['data_interacao']);
                            $agora = new DateTime();
                            $diff = $agora->diff($data);
                            
                            if ($diff->days == 0) {
                                echo 'Hoje às ' . $data->format('H:i');
                            } elseif ($diff->days == 1) {
                                echo 'Ontem às ' . $data->format('H:i');
                            } else {
                                echo $data->format('d/m/Y H:i');
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
