<?php
if (isset($_SESSION['id_usuario'])) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM mensagens 
        WHERE destinatario_id = ? AND lida = 0
    ");
    $stmt->execute([$_SESSION['id_usuario']]);
    $msg_nao_lidas = $stmt->fetch()['total'] ?? 0;
?>
<a href="/pages/mensagens.php" class="icon-link">
    <span class="icon-mensagens">✉</span>
    <?php if ($msg_nao_lidas > 0): ?>
        <span class="badge-counter"><?php echo $msg_nao_lidas > 99 ? '99+' : $msg_nao_lidas; ?></span>
    <?php endif; ?>
</a>

<style>
.icon-link {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    text-decoration: none;
    color: #333;
}

.icon-mensagens {
    font-size: 22px;
}

.badge-counter {
    position: absolute;
    top: 0;
    right: 0;
    background: #ff4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: 600;
    min-width: 18px;
    text-align: center;
}
</style>
<?php } ?>
