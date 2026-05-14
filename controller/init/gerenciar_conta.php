<?php
include_once __DIR__ . '/../../config.php';

$stmt = $conn->prepare("SELECT u.nome_usuario, u.email, u.usuario_login, uv.codVerificador, uv.telegram_id, uv.statusConta, uv.personal_id FROM usuario u INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario WHERE u.id_usuario = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

$stmtProp = $conn->prepare("SELECT id_prop FROM proprietario_ong WHERE fk_usuario = ? LIMIT 1");
$stmtProp->bind_param("i", $_SESSION['usuario_id']);
$stmtProp->execute();
$eProprietario = $stmtProp->get_result()->num_rows > 0;
$stmtProp->close();

$conn->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$tem2fa      = !empty($usuario['codVerificador']);
$temTelegram = !empty($usuario['telegram_id']);
$esAdmin     = ((int) $usuario['statusConta']) === 3;
$temPergunta = !empty($usuario['personal_id']);
