<?php
/**
 * S.3.2.a + S.3.2.b
 * Executar UMA vez no servidor: php config/generate_db_key.php
 * Gera a chave AES-256 para criptografia de registros do BD e a armazena
 * de forma segura no .env usando a mesma cadeia PBKDF2 do projeto.
 */

include __DIR__ . '/../config.php';

if (!$hasEnv) {
    echo "ERRO: .env nao encontrado. Gere o .env com encrypt_env.php primeiro.\n";
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// S.3.2.a — Geração da chave simétrica AES-256
// ─────────────────────────────────────────────────────────────────────────────
echo "\n";
echo "+----------------------------------------------------------+\n";
echo "|  S.3.2.a - Geracao de Chave AES-256 para BD             |\n";
echo "+----------------------------------------------------------+\n\n";

$dbKey    = random_bytes(32);
$dbKeyHex = bin2hex($dbKey);

echo "Algoritmo  : AES-256-CBC\n";
echo "Tamanho    : " . (strlen($dbKey) * 8) . " bits (" . strlen($dbKey) . " bytes)\n";
echo "Chave (hex): " . $dbKeyHex . "\n\n";

// ─────────────────────────────────────────────────────────────────────────────
// S.3.2.b — Proteger a chave com gestão de segredos
// A chave DB é cifrada com AES-256-CBC usando a mesma chave PBKDF2-SHA256
// (200.000 iterações + salt único) que já protege todos os segredos do .env.
// ─────────────────────────────────────────────────────────────────────────────
echo "+----------------------------------------------------------+\n";
echo "|  S.3.2.b - Armazenamento Seguro (Gestao de Segredos)    |\n";
echo "+----------------------------------------------------------+\n\n";

echo "Protegendo chave com PBKDF2-SHA256 (200.000 iter.) + AES-256-CBC...\n\n";

$dbKeyCifrada = aes_encrypt(base64_encode($dbKey), $chave);

echo "Chave protegida : " . $dbKeyCifrada . "\n\n";

$envPath  = dirname(__DIR__) . '/config/.env';
$conteudo = file_get_contents($envPath);
$conteudo = preg_replace('/^DB_ENCRYPT_KEY=.*\n?/m', '', $conteudo);
$conteudo = rtrim($conteudo) . "\nDB_ENCRYPT_KEY=" . $dbKeyCifrada . "\n";

file_put_contents($envPath, $conteudo);

$usuario  = trim(shell_exec('whoami') ?: get_current_user());
$hostname = gethostname();

echo "Destino   : {$envPath}\n";
echo "Variavel  : DB_ENCRYPT_KEY\n";
echo "Operador  : {$usuario}@{$hostname}\n";
echo "Timestamp : " . date('Y-m-d H:i:s') . "\n";
echo "Status    : [OK] Chave gravada no .env com sucesso.\n\n";

$conn->close();
