<?php
/**
 * Migra registros antigos (plaintext) para criptografia AES-256.
 * Identifica registros antigos pelo email_hash = NULL.
 * Executar UMA vez: php config/migrate_encrypt_existing.php
 */

include __DIR__ . '/../config.php';

if ($DB_ENCRYPT_KEY === null) {
    echo "ERRO: DB_ENCRYPT_KEY não configurada. Execute generate_db_key.php primeiro.\n";
    exit(1);
}

$result = $conn->query("SELECT id_usuario, nome_usuario, email FROM usuario WHERE email_hash IS NULL");

if (!$result) {
    echo "Erro na consulta: " . $conn->error . "\n";
    exit(1);
}

$total    = $result->num_rows;
$migrados = 0;
$erros    = 0;

echo "Registros a migrar: {$total}\n\n";

$stmt = $conn->prepare(
    "UPDATE usuario SET nome_usuario = ?, email = ?, email_hash = ? WHERE id_usuario = ?"
);

while ($row = $result->fetch_assoc()) {
    $nomeCifrado  = aes_encrypt($row['nome_usuario'], $DB_ENCRYPT_KEY);
    $emailCifrado = aes_encrypt($row['email'],        $DB_ENCRYPT_KEY);
    $emailHash    = hash_hmac('sha256', strtolower($row['email']), 'ongs-browser-email-index');

    $stmt->bind_param("sssi", $nomeCifrado, $emailCifrado, $emailHash, $row['id_usuario']);

    if ($stmt->execute()) {
        echo "  [OK] id={$row['id_usuario']} | {$row['nome_usuario']} -> cifrado\n";
        $migrados++;
    } else {
        echo "  [ERRO] id={$row['id_usuario']}: " . $stmt->error . "\n";
        $erros++;
    }
}

$stmt->close();
$conn->close();

echo "\nConcluído: {$migrados} migrado(s), {$erros} erro(s).\n";
