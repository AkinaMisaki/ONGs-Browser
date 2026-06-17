<?php
/**
 * Gera um .env local para testes — edite as variáveis abaixo antes de rodar.
 * Executar UMA vez: php config/setup_local.php
 */

// ── Edite aqui ──────────────────────────────────────────────────────────────
$local = [
    'DB_HOST'            => 'localhost',
    'DB_USER' => 'ongs',
    'DB_PASS' => 'senha123',     // senha do MySQL local
    'DB_NAME'            => 'universidade',
    'DB_PORT'            => '3306',
    'RECAPTCHA_SITE'   => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
    'RECAPTCHA_SECRET' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
    'SMTP_PASSWORD'      => 'local',
    'TELEGRAM_BOT_TOKEN' => 'local',
    'TEST_ENV'           => 'local',
];
// ─────────────────────────────────────────────────────────────────────────────

function aes_enc(string $valor, string $chave): string {
    $iv = random_bytes(16);
    $c  = openssl_encrypt($valor, 'aes-256-cbc', $chave, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $c);
}

$salt  = random_bytes(16);
$chave = hash_pbkdf2('sha256', '', $salt, 200000, 32, true);

$linhas   = [];
$linhas[] = "CHAVE_SALT=" . bin2hex($salt);
$linhas[] = "";
$linhas[] = "DB_HOST=" . $local['DB_HOST'];
$linhas[] = "DB_USER=" . aes_enc(base64_encode($local['DB_USER']),            $chave);
$linhas[] = "DB_NAME=" . aes_enc(base64_encode($local['DB_NAME']),            $chave);
$linhas[] = "DB_PORT=" . $local['DB_PORT'];
$linhas[] = "RECAPTCHA_SITE=" . $local['RECAPTCHA_SITE'];
$linhas[] = "DB_PASS=" . aes_enc(base64_encode($local['DB_PASS']),            $chave);
$linhas[] = "SMTP_PASSWORD=" . aes_enc(base64_encode($local['SMTP_PASSWORD']), $chave);
$linhas[] = "RECAPTCHA_SECRET=" . aes_enc(base64_encode($local['RECAPTCHA_SECRET']), $chave);
$linhas[] = "TELEGRAM_BOT_TOKEN=" . aes_enc(base64_encode($local['TELEGRAM_BOT_TOKEN']), $chave);
$linhas[] = "TEST_ENV=" . aes_enc($local['TEST_ENV'], $chave);

$envPath = dirname(__DIR__) . '/config/.env';
file_put_contents($envPath, implode("\n", $linhas) . "\n");

echo "✔ .env local gerado em: {$envPath}\n";
echo "  DB: {$local['DB_HOST']} / {$local['DB_NAME']} (user: {$local['DB_USER']})\n";
