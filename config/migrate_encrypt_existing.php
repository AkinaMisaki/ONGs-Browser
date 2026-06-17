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

echo "\nConcluído: {$migrados} migrado(s), {$erros} erro(s).\n";

// ─────────────────────────────────────────────────────────────────────────────
// Migra cpf/rg/telefone em proprietario_ong (detecta texto puro pelo formato)
// ─────────────────────────────────────────────────────────────────────────────
echo "\nMigrando proprietario_ong (cpf, rg, telefone)...\n\n";

$regexCpf = '/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/';

$resultProp = $conn->query("SELECT id_prop, cpf, rg, telefone FROM proprietario_ong");
if (!$resultProp) {
    echo "Erro na consulta: " . $conn->error . "\n";
    exit(1);
}

$migradosProp = 0;
$errosProp    = 0;

$stmtProp = $conn->prepare("UPDATE proprietario_ong SET cpf = ?, rg = ?, telefone = ? WHERE id_prop = ?");

while ($row = $resultProp->fetch_assoc()) {
    if (!preg_match($regexCpf, $row['cpf'])) {
        continue; // já está cifrado
    }

    $cpfCifrado      = aes_encrypt($row['cpf'],      $DB_ENCRYPT_KEY);
    $rgCifrado       = aes_encrypt($row['rg'],       $DB_ENCRYPT_KEY);
    $telefoneCifrado = aes_encrypt($row['telefone'], $DB_ENCRYPT_KEY);

    $stmtProp->bind_param("sssi", $cpfCifrado, $rgCifrado, $telefoneCifrado, $row['id_prop']);

    if ($stmtProp->execute()) {
        echo "  [OK] id_prop={$row['id_prop']} -> cifrado\n";
        $migradosProp++;
    } else {
        echo "  [ERRO] id_prop={$row['id_prop']}: " . $stmtProp->error . "\n";
        $errosProp++;
    }
}

$stmtProp->close();

echo "\nConcluído: {$migradosProp} migrado(s), {$errosProp} erro(s).\n";

// ─────────────────────────────────────────────────────────────────────────────
// Migra telegram_id e codVerificador em usuario_verificacao
// ─────────────────────────────────────────────────────────────────────────────
echo "\nMigrando usuario_verificacao (telegram_id, codVerificador)...\n\n";

$regexNumerico = '/^-?\d+$/';
$regexBase32   = '/^[A-Z2-7]+$/';

$resultUv = $conn->query("SELECT id_verificacao, telegram_id, codVerificador FROM usuario_verificacao");
if (!$resultUv) {
    echo "Erro na consulta: " . $conn->error . "\n";
    exit(1);
}

$migradosUv = 0;
$errosUv    = 0;

$stmtUv = $conn->prepare(
    "UPDATE usuario_verificacao SET telegram_id = ?, telegram_id_hash = ?, codVerificador = ? WHERE id_verificacao = ?"
);

while ($row = $resultUv->fetch_assoc()) {
    $precisaMigrar = false;

    $telegramId = $row['telegram_id'];
    if ($telegramId !== null && preg_match($regexNumerico, $telegramId)) {
        $telegramIdCifrado = aes_encrypt($telegramId, $DB_ENCRYPT_KEY);
        $telegramIdHash     = hash_hmac('sha256', $telegramId, 'ongs-browser-telegram-index');
        $precisaMigrar = true;
    } else {
        $telegramIdCifrado = $telegramId;
        $telegramIdHash     = $telegramId !== null ? hash_hmac('sha256', db_decrypt($telegramId, $DB_ENCRYPT_KEY), 'ongs-browser-telegram-index') : null;
    }

    $codVerificador = $row['codVerificador'];
    if ($codVerificador !== null && preg_match($regexBase32, $codVerificador)) {
        $codVerificadorCifrado = aes_encrypt($codVerificador, $DB_ENCRYPT_KEY);
        $precisaMigrar = true;
    } else {
        $codVerificadorCifrado = $codVerificador;
    }

    if (!$precisaMigrar) {
        continue;
    }

    $stmtUv->bind_param("sssi", $telegramIdCifrado, $telegramIdHash, $codVerificadorCifrado, $row['id_verificacao']);

    if ($stmtUv->execute()) {
        echo "  [OK] id_verificacao={$row['id_verificacao']} -> cifrado\n";
        $migradosUv++;
    } else {
        echo "  [ERRO] id_verificacao={$row['id_verificacao']}: " . $stmtUv->error . "\n";
        $errosUv++;
    }
}

$stmtUv->close();
$conn->close();

echo "\nConcluído: {$migradosUv} migrado(s), {$errosUv} erro(s).\n";
