<?php
session_start();
require_once __DIR__ . '/../config.php';

$token = $_POST['g-recaptcha-response'] ?? '';

if (empty($token)) {
    $_SESSION['captcha_aprovado'] = false;
    header('Location: ../index.php');
    exit;
}

$url_google = "https://www.google.com/recaptcha/api/siteverify?secret={$CAPTCHA_SECRETA}&response={$token}";
$resposta   = file_get_contents($url_google);
$resultado  = json_decode($resposta, true);

if ($resultado['success'] === true) {
    $_SESSION['captcha_aprovado'] = true;
} else {
    $_SESSION['captcha_aprovado'] = false;
}

header('Location: ../index.php');
exit;
?>