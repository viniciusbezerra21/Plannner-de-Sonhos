<?php
function criptografar($dados) {
    $chave = 'receba'; 
    $iv = substr(hash('sha256', 'iv_unico'), 0, 16);
    return openssl_encrypt($dados, 'AES-256-CBC', $chave, 0, $iv);
}

function descriptografar($dadosCriptografado) {
    $chave = 'receba';
    $iv = substr(hash('sha256', 'iv_unico'), 0, 16);
    return openssl_decrypt($dadosCriptografado, 'AES-256-CBC', $chave, 0, $iv);
}
?>
