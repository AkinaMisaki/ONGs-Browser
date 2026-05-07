<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_method']);
    exit;
}

// Check de Cross Site Request Forgery
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_csrf']);
    exit;
}

$token           = trim($_POST['token'] ?? '');
$password        = $_POST['password']         ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

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