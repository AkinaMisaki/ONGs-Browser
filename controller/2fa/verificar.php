<?php
header('Content-Type: text/html; charset=utf-8');
include __DIR__ . '/../../config.php';
require_once '../../libs/autoload.php';
use OTPHP\TOTP;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
$erro       = null;
$sucesso    = false;

// detecta qual fluxo é
$fluxoAtivacao = !empty($_SESSION['totp_secret_pendente']);
$fluxoLogin    = !empty($_SESSION['2fa_pendente']);

if (!$fluxoAtivacao && !$fluxoLogin) {
    header('Location: ../../view/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');

    if ($fluxoLogin) {
        $secret = $_SESSION['2fa_secret'];
    } else {
        $secret = $_SESSION['totp_secret_pendente'];
    }

    $totp = TOTP::createFromSecret($secret);

    if ($totp->verify($codigo)) {
        if ($fluxoLogin) {
            // cria a sessão de login
            $_SESSION['usuario_id']    = $_SESSION['temp_usuario_id'];
            $_SESSION['usuario_login'] = $_SESSION['temp_usuario_login'];
            $_SESSION['statusConta']   = $_SESSION['temp_statusConta'];

            // limpa temporários
            unset($_SESSION['2fa_pendente']);
            unset($_SESSION['2fa_secret']);
            unset($_SESSION['temp_usuario_id']);
            unset($_SESSION['temp_usuario_login']);
            unset($_SESSION['temp_statusConta']);

            header('Location: ../../index.php');
            exit();
        } else {
            // salva o secret no banco (ativação)
            $sql  = "UPDATE usuario SET usuario_2fa = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $secret, $usuario_id);
            $stmt->execute();

            unset($_SESSION['totp_secret_pendente']);
            $sucesso = true;
        }
    } else {
        $erro = "Código inválido. Tente novamente.";
    }
}

include '../../view/2fa/verificar.php';
?>