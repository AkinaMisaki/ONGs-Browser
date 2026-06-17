<?php
function env_val(string $v): string {
    return substr($v, 0, 4) === 'b64:' ? base64_decode(substr($v, 4)) : $v;
}

function extrairSenha(string $caminhoLetra, array $posicoes): string {
    $letra = file_get_contents($caminhoLetra);
    $letra = preg_replace('/\x{FEFF}/u', '', $letra);

    $caracteres = '';
    foreach ($posicoes as $pos) {
        if ($pos < mb_strlen($letra)) {
            $caracteres .= mb_substr($letra, $pos, 1);
        }
    }
    return $caracteres;
}

function aes_encrypt(string $valor, string $chave): string {
    $iv = random_bytes(16);
    $cifrado = openssl_encrypt($valor, 'aes-256-cbc', $chave, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cifrado);
}

function aes_decrypt(string $encoded, string $chave): string {
    $data    = base64_decode($encoded);
    $iv      = substr($data, 0, 16);
    $cifrado = substr($data, 16);
    return openssl_decrypt($cifrado, 'aes-256-cbc', $chave, OPENSSL_RAW_DATA, $iv);
}

function db_decrypt(?string $valor, ?string $chave): string {
    if ($valor === null || $valor === '') return '';
    if ($chave === null) return $valor;
    $result = aes_decrypt($valor, $chave);
    return ($result === false || $result === '') ? $valor : $result;
}

// Posições só existem no código-fonte — não ficam no .env
$posicoes = [];

$envPath = dirname(__DIR__) . '/config/.env';
$hasEnv  = file_exists($envPath);

if ($hasEnv) {
    $env = (function(string $path): array {
        $out = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            if (strlen($val) >= 2 && $val[0] === '"' && $val[strlen($val)-1] === '"') {
                $val = substr($val, 1, -1);
            }
            $out[$key] = $val;
        }
        return $out;
    })($envPath);

    $salt  = hex2bin($env['CHAVE_SALT']);
    $senha = extrairSenha(dirname(__DIR__) . '/config/childrenofthecity.txt', $posicoes);
    $chave = hash_pbkdf2('sha256', $senha, $salt, 200000, 32, true);

    $db_host            = env_val($env['DB_HOST']);
    $db_user            = base64_decode(aes_decrypt($env['DB_USER'],            $chave));
    $db_name            = base64_decode(aes_decrypt($env['DB_NAME'],            $chave));
    $db_port            = (int) $env['DB_PORT'];
    $db_pass            = base64_decode(aes_decrypt($env['DB_PASS'],            $chave));
    $SMTP_PASSWORD      = base64_decode(aes_decrypt($env['SMTP_PASSWORD'],      $chave));
    $CAPTCHA_SITE       = env_val($env['RECAPTCHA_SITE']);
    $CAPTCHA_SECRETA    = base64_decode(aes_decrypt($env['RECAPTCHA_SECRET'],   $chave));
    $TELEGRAM_BOT_TOKEN = base64_decode(aes_decrypt($env['TELEGRAM_BOT_TOKEN'], $chave));
    $TEST_ENV           = aes_decrypt($env['TEST_ENV'], $chave);
    $DB_ENCRYPT_KEY     = isset($env['DB_ENCRYPT_KEY'])
                          ? base64_decode(aes_decrypt($env['DB_ENCRYPT_KEY'], $chave))
                          : null;
} else {
    // Default XAMPP settings (no .env present)
    $db_host            = 'localhost';
    $db_user            = 'root';
    $db_pass            = '';
    $db_name            = 'universidade';
    $db_port            = 3306;
    $SMTP_PASSWORD      = '';
    $CAPTCHA_SITE       = '';
    $CAPTCHA_SECRETA    = '';
    $TELEGRAM_BOT_TOKEN = '';
    $TEST_ENV           = '';
    $DB_ENCRYPT_KEY     = null;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    error_log($conn->connect_error);
    die("Database connection failed.");
}