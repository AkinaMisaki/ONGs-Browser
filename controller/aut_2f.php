<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../config.php';

use PragmaRX\Google2FA\Google2FA;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

// Sessão 2FA não existe — usuário não passou pelo login
if (!isset($_SESSION['2fa_pending_id'], $_SESSION['2fa_pending_login'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'sessao_invalida']);
    exit;
}

$codigoEnviado = trim($_POST['codigo'] ?? '');

if (empty($codigoEnviado) || strlen($codigoEnviado) !== 6 || !ctype_digit($codigoEnviado)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código inválido. Digite os 6 dígitos.']);
    exit;
}

// Busca o segredo TOTP do usuário
$stmt = $conn->prepare("SELECT codVerificador FROM usuario_verificacao WHERE fk_usuario = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['2fa_pending_id']);
$stmt->execute();
$result  = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$usuario || empty($usuario['codVerificador'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'sessao_invalida']);
    exit;
}

$google2fa = new Google2FA();
$valido = $google2fa->verifyKey($usuario['codVerificador'], $codigoEnviado);

if (!$valido) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código incorreto. Tente novamente.']);
    exit;
}

// Código correto — promove a sessão para autenticada
$_SESSION['usuario_id']    = $_SESSION['2fa_pending_id'];
$_SESSION['usuario_login'] = $_SESSION['2fa_pending_login'];

unset($_SESSION['2fa_pending_id'], $_SESSION['2fa_pending_login']);

echo json_encode(['sucesso' => true, 'mensagem' => 'Login realizado com sucesso!']);
?>
