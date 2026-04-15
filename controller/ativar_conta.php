<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

$token = trim($_POST['token'] ?? '');

if (empty($token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token de ativação ausente.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id_usuario, reset_expire, statusConta FROM usuario WHERE reset_token = ? LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'link_invalido']);
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Conta já ativa
if ((int) $usuario['statusConta'] === 1) {
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'ja_ativa']);
    exit;
}

// Link expirado
if (strtotime($usuario['reset_expire']) < time()) {
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'link_expirado']);
    exit;
}

// Ativa a conta e limpa o token
$stmt = $conn->prepare(
    "UPDATE usuario SET statusConta = 1, reset_token = NULL, reset_expire = NULL WHERE id_usuario = ?"
);
$stmt->bind_param("i", $usuario['id_usuario']);
$stmt->execute();

if ($stmt->affected_rows !== 1) {
    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao ativar a conta. Tente novamente.']);
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['sucesso' => true, 'mensagem' => 'Conta ativada com sucesso! Você já pode fazer login.']);
?>
