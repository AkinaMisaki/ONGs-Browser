<?php
ini_set('display_errors', '0');
$meurastro = [];
include __DIR__ . '/../config.php';
require_once __DIR__ . '/check_banned_ip.php';
checkBannedIp($conn);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado. Método incorreto."]);
    exit;
}

// --- Leitura do payload ---
$dadosRecebidos = json_decode(file_get_contents('php://input'), true);
if (!$dadosRecebidos) {
    echo json_encode(["sucesso" => false, "mensagem" => "Payload inválido ou vazio."]);
    exit;
}

$camposObrigatorios = ['msg_cifrada', 'keyAESCifrada', 'iv', 'captchaToken'];
foreach ($camposObrigatorios as $campo) {
    if (empty($dadosRecebidos[$campo])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Campo obrigatório ausente: $campo"]);
        exit;
    }
}

// --- Verificação reCAPTCHA ---
$captchaToken    = $dadosRecebidos['captchaToken'];
$respostaGoogle  = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret={$CAPTCHA_SECRETA}&response={$captchaToken}"
);
$resultadoCaptcha = json_decode($respostaGoogle, true);
if (!$resultadoCaptcha || !$resultadoCaptcha['success']) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha na verificação do reCAPTCHA. Tente novamente."]);
    exit;
}

$msgCifrada = base64_decode($dadosRecebidos['msg_cifrada'],  true);
$keyCifrada = base64_decode($dadosRecebidos['keyAESCifrada'], true);
$iv         = base64_decode($dadosRecebidos['iv'],            true);

if ($msgCifrada === false || $keyCifrada === false || $iv === false) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao decodificar base64."]);
    exit;
}

// --- Etapa 6: Decifrar chave AES com RSA privada ---
$pvkeyPath = '/var/www/config/private.pem';
if (!file_exists($pvkeyPath)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Chave privada não encontrada."]);
    exit;
}

$pvkey = openssl_pkey_get_private(file_get_contents($pvkeyPath));
if (!$pvkey) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha ao carregar chave privada RSA."]);
    exit;
}

$chaveAES = '';
if (!openssl_private_decrypt($keyCifrada, $chaveAES, $pvkey, OPENSSL_PKCS1_OAEP_PADDING)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha ao decifrar chave AES com RSA."]);
    exit;
}

// --- Etapa 7: Decifrar credenciais com AES-GCM ---
$tag    = substr($msgCifrada, -16);
$cipher = substr($msgCifrada,  0, -16);
$json   = openssl_decrypt($cipher, 'aes-256-gcm', $chaveAES, OPENSSL_RAW_DATA, $iv, $tag);

if ($json === false) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha ao decifrar mensagem com AES. Tag GCM inválida."]);
    exit;
}

$credenciais = json_decode($json, true);
if (!$credenciais || !isset($credenciais['campoUsuario'], $credenciais['campoSenha'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Credenciais mal formatadas após decifração."]);
    exit;
}

$login = trim($credenciais['campoUsuario']);
$senha = $credenciais['campoSenha'];

if (empty($login) || empty($senha)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Usuário e senha são obrigatórios."]);
    exit;
}

// --- Consulta ao banco ---
$sql = "SELECT u.id_usuario, u.usuario_login, u.usuario_password, uv.statusConta, uv.codVerificador
        FROM usuario u
        INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario
        WHERE u.usuario_login = ?
        LIMIT 1";

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao preparar consulta."]);
        exit;
    }
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result  = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro interno no banco de dados."]);
    exit;
}

// Usuário não encontrado ou senha incorreta
if (!$usuario || !password_verify($senha, $usuario['usuario_password'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Usuário ou senha incorretos."]);
    exit;
}

// Conta não ativada
if ((int) $usuario['statusConta'] <= 0) {
    echo json_encode(["sucesso" => false, "mensagem" => "Usuário inativo. Confirme seu email antes de continuar."]);
    exit;
}

session_start();

// 2FA configurado — exige verificação TOTP
if (!empty($usuario['codVerificador'])) {
    $_SESSION['2fa_pending_id']    = $usuario['id_usuario'];
    $_SESSION['2fa_pending_login'] = $usuario['usuario_login'];
    $_SESSION['statusConta']       = $usuario['statusConta'];
    echo json_encode(["sucesso" => true, "acao" => "2fa_required"]);
    exit;
}

// Login direto
$_SESSION['usuario_id']    = $usuario['id_usuario'];
$_SESSION['usuario_login'] = $usuario['usuario_login'];
$_SESSION['statusConta']   = $usuario['statusConta'];

echo json_encode(["sucesso" => true, "mensagem" => "Acesso permitido!"]);
?>
