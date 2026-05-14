<?php
include_once __DIR__ . '/../../config.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare("SELECT fk_usuario AS id_usuario, reset_expire FROM usuario_verificacao WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    header('Location: login.php?erro=invalid_token');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (strtotime($user['reset_expire']) < time()) {
    header('Location: login.php?erro=token_expired');
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
