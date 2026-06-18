<?php
/**
 * Ajuste pontual da conta id_usuario=3 (dados corrompidos por chave antiga).
 * Executar UMA vez: php config/fix_user3.php
 */

include __DIR__ . '/../config.php';

if (!$conn->select_db('universidade')) {
    echo "ERRO ao selecionar banco 'universidade': " . $conn->error . "\n";
    exit(1);
}

if ($DB_ENCRYPT_KEY === null) {
    echo "ERRO: DB_ENCRYPT_KEY nao configurada.\n";
    exit(1);
}

$novoNome  = 'Lupum';
$novoLogin = 'lupum';
$novaSenha = 'Aa@12345678';
$novoEmail = 'lupum@hanafuda.moe';

$options   = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
$senhaHash = password_hash($novaSenha, PASSWORD_ARGON2ID, $options);

$nomeCifrado  = aes_encrypt($novoNome, $DB_ENCRYPT_KEY);
$emailCifrado = aes_encrypt($novoEmail, $DB_ENCRYPT_KEY);
$emailHash    = hash_hmac('sha256', strtolower($novoEmail), 'ongs-browser-email-index');

$stmt = $conn->prepare("UPDATE usuario SET nome_usuario = ?, usuario_login = ?, usuario_password = ?, email = ?, email_hash = ? WHERE id_usuario = 3");
$stmt->bind_param("sssss", $nomeCifrado, $novoLogin, $senhaHash, $emailCifrado, $emailHash);
$stmt->execute();
echo "usuario: {$stmt->affected_rows} linha(s) atualizada(s)\n";
$stmt->close();

$conn->close();
echo "Concluido.\n";
