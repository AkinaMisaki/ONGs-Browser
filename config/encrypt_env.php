<?php
// ─── Mesmas funções do config.php ──────────────────────────
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

// ─── Suas configurações ────────────────────────────────────
$posicoes = []; // suas posições secretas

$salt = random_bytes(16); // gerado aqui, salvo no .env

$senha = extrairSenha(__DIR__ . '/../config/childrenofthecity.txt', $posicoes);
$chave = hash_pbkdf2('sha256', $senha, $salt, 200000, 32, true);

// ─── Valores em texto puro — edite aqui ────────────────────
$valores = [
    // Cifrar: true = o valor será cifrado e salvo no .env, false = salvo em texto puro
    'DB_HOST'         => ['valor' => 'localhost',                                                            'cifrar' => false],
    'DB_USER'         => ['valor' => 'usuario',                                             'cifrar' => true],
    'DB_NAME'         => ['valor' => 'banco',                                                     'cifrar' => true],
    'DB_PORT'         => ['valor' => '3306',                                                                 'cifrar' => false],
    'RECAPTCHA_SITE'  => ['valor' => 'token',                             'cifrar' => false],
    'DB_PASS'         => ['valor' => 'senha',                                             'cifrar' => true],
    'SMTP_PASSWORD'   => ['valor' => 'senha',                                                         'cifrar' => true],
    'RECAPTCHA_SECRET'=> ['valor' => 'segredo',             'cifrar' => true],
    'TELEGRAM_BOT_TOKEN' => ['valor' => 'token',  'cifrar' => true],
    'TEST_ENV'       => ['valor' => 'oiiee',                                                                 'cifrar' => true],
];

// ─── Gera o .env ───────────────────────────────────────────
$linhas = [];
$linhas[] = "CHAVE_SALT=" . bin2hex($salt);
$linhas[] = "";

foreach ($valores as $chave_nome => $config) {
    if ($config['cifrar']) {
        $linhas[] = $chave_nome . '="' . aes_encrypt($config['valor'], $chave) . '"';
    } else {
        $linhas[] = $chave_nome . '="' . $config['valor'] . '"';
    }
}

$conteudo = implode("\n", $linhas) . "\n";
file_put_contents(__DIR__ . '/../config/.env', $conteudo);

echo "✅ .env gerado com sucesso!\n";
echo "Salt usado: " . bin2hex($salt) . "\n";

// Apaga o próprio script depois (opcional mas recomendado)
unlink(__FILE__);