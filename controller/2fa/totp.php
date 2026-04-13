<?php
header('Content-Type: text/html; charset=utf-8');
include '../../conn/config.php';
require_once '../../libs/autoload.php';
use OTPHP\TOTP;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
$erro       = null;
$secret     = null;
$uri        = null;

if (!$usuario_id) {
    header('Location: ../../view/login.php');
    exit();
}

$sql  = "SELECT usuario_2fa, email FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $linha     = $resultado->fetch_assoc();

    $email = $linha['email'] ?? 'usuario';

    if (!empty($linha['usuario_2fa'])) {
        $erro = "2FA já está ativo na sua conta.";
    } else {
        $totp = TOTP::generate();
        $totp->setLabel($email);
        $totp->setIssuer('ONGs-Browser');

        $secret = $totp->getSecret();
        $uri    = $totp->getProvisioningUri();

        // salva na sessão, não no banco
        $_SESSION['totp_secret_pendente'] = $secret;
    }
} else {
    $erro = "Não foi possível preparar a query.";
}

include '../../view/2fa/totp.php';
?>