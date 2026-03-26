<?php
header('Content-Type: application/json');
include __DIR__ . '/../../config/config.php';

if (!isset($_SESSION)) session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['sucesso' => false, 'message' => 'invalid_method']);
    exit;
}

$token           = trim($_POST['token']            ?? '');
$password        = trim($_POST['password']         ?? '');
$passwordConfirm = trim($_POST['password_confirm'] ?? '');

// --- Validate inputs ---
if (empty($token)) {
    echo json_encode(['sucesso' => false, 'message' => 'invalid_token']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['sucesso' => false, 'message' => 'password_too_short']);
    exit;
}

if ($password !== $passwordConfirm) {
    echo json_encode(['sucesso' => false, 'message' => 'password_mismatch']);
    exit;
}

// --- Validate token in DB ---
$stmt = $conn->prepare("SELECT id_user, reset_expire FROM user WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['sucesso' => false, 'message' => 'invalid_token']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (strtotime($user['reset_expire']) < time()) {
    echo json_encode(['sucesso' => false, 'message' => 'token_expired']);
    $conn->close();
    exit;
}

// --- Update password ---
$newPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    UPDATE user
    SET senha = ?, reset_token = NULL, reset_expire = NULL
    WHERE id_user = ?
");
$stmt->bind_param("si", $newPassword, $user['id_user']);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['sucesso' => true, 'message' => 'password_updated']);
exit;