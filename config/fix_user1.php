<?php
/**
 * Ajuste pontual da conta id_usuario=1 (dados corrompidos por chave antiga).
 * Executar UMA vez: php config/fix_user1.php
 */

include __DIR__ . '/../config.php';

if ($DB_ENCRYPT_KEY === null) {
    echo "ERRO: DB_ENCRYPT_KEY nao configurada.\n";
    exit(1);
}

$novoLogin  = 'max';
$novaSenha  = 'Aa@12345678';
$novoEmail  = 'akina@hanafuda.moe';
$novoStatus = 3;

$options   = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
$senhaHash = password_hash($novaSenha, PASSWORD_ARGON2ID, $options);

$emailCifrado = aes_encrypt($novoEmail, $DB_ENCRYPT_KEY);
$emailHash    = hash_hmac('sha256', strtolower($novoEmail), 'ongs-browser-email-index');

$stmt = $conn->prepare("UPDATE usuario SET usuario_login = ?, usuario_password = ?, email = ?, email_hash = ? WHERE id_usuario = 1");
$stmt->bind_param("ssss", $novoLogin, $senhaHash, $emailCifrado, $emailHash);
$stmt->execute();
echo "usuario: {$stmt->affected_rows} linha(s) atualizada(s)\n";
$stmt->close();

$stmt2 = $conn->prepare("UPDATE usuario_verificacao SET statusConta = ? WHERE fk_usuario = 1");
$stmt2->bind_param("i", $novoStatus);
$stmt2->execute();
echo "usuario_verificacao: {$stmt2->affected_rows} linha(s) atualizada(s)\n";
$stmt2->close();

$conn->close();
echo "Concluido.\n";
