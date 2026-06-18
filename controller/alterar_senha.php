<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_method']);
    exit;
}

// Check de Cross Site Request Forgery

// 1. recebe o payload JSON em plain text
$payloadStr = file_get_contents('php://input');
$payload = json_decode($payloadStr, true);

if (!$payload || empty($payload['keyAESCifrada']) || empty($payload['msg_cifrada']) || empty($payload['iv'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_payload']);
    exit;
}

// 2. decodifica base 64
$keyCifrada = base64_decode($payload['keyAESCifrada'], true);
$msgCifrada = base64_decode($payload['msg_cifrada'], true);
$iv         = base64_decode($payload['iv'], true);

// 3. abri a chave AES usando a chave privada RSA do servidor
$pvkeyPath = '/var/www/config/private.pem'; // caminho
if (!file_exists($pvkeyPath)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'config_error']);
    exit;
}

$pvkey = openssl_pkey_get_private(file_get_contents($pvkeyPath));
$chaveAES = '';
if (!openssl_private_decrypt($keyCifrada, $chaveAES, $pvkey, OPENSSL_PKCS1_OAEP_PADDING)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'decryption_error']);
    exit;
}

// 4. separa a tag de autenticação do GCM e abrir a mensagem
$tag    = substr($msgCifrada, -16);
$cipher = substr($msgCifrada, 0, -16);
$jsonDescriptografado = openssl_decrypt($cipher, 'aes-256-gcm', $chaveAES, OPENSSL_RAW_DATA, $iv, $tag);

if ($jsonDescriptografado === false) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'decryption_error']);
    exit;
}

// 5. extrai os dados reais do formulario
$formDados = json_decode($jsonDescriptografado, true);

if (!$formDados) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'decryption_error']);
    exit;
}

$csrfToken       = $formDados['csrf_token'] ?? '';
$token           = trim($formDados['token'] ?? '');
$password        = $formDados['password'] ?? '';
$passwordConfirm = $formDados['password_confirm'] ?? '';

// 6. fazer a verificação do CSRF 
if (empty($csrfToken) || $csrfToken !== $_SESSION['csrf_token']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_csrf']);
    exit;
}

// Valida o token e a senha
if (empty($token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_token']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'password_too_short']);
    exit;
}

if ($password !== $passwordConfirm) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'password_mismatch']);
    exit;
}

// Valida o token no BD
$stmt = $conn->prepare("SELECT fk_usuario AS id_usuario, reset_expire FROM usuario_verificacao WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_token']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (strtotime($user['reset_expire']) < time()) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'token_expired']);
    $conn->close();
    exit;
}

// Criptografa Senha e Atualiza no BD
$options = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
$newPassword = password_hash($password, PASSWORD_ARGON2ID, $options);
$stmt = $conn->prepare("UPDATE usuario SET usuario_password = ? WHERE id_usuario = ?");
$stmt->bind_param("si", $newPassword, $user['id_usuario']);
$stmt->execute();

if ($stmt->affected_rows !== 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'update_failed']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare("UPDATE usuario_verificacao SET reset_token = NULL, reset_expire = NULL WHERE fk_usuario = ?");
$stmt->bind_param("i", $user['id_usuario']);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['sucesso' => true, 'mensagem' => 'password_updated']);
exit;