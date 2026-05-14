<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

$adminId = $_SESSION['admin_recovery_id'] ?? null;
if (!$adminId) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

// Gera uma passkey única a cada carregamento da página
do {
    $pass = strtoupper(bin2hex(random_bytes(6)));
    $chk  = $conn->prepare("SELECT 1 FROM usuario_verificacao WHERE telegram_pass = ?");
    $chk->bind_param("s", $pass);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;
    $chk->close();
} while ($exists);

$_SESSION['admin_recovery_tg_pass'] = $pass;
$stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_pass = ? WHERE fk_usuario = ?");
$stmt->bind_param("si", $pass, $adminId);
$stmt->execute();
$stmt->close();

$telegramPass = $_SESSION['admin_recovery_tg_pass'];
