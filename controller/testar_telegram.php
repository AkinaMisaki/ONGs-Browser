<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'nao_autenticado']);
    exit;
}

include __DIR__ . '/../config.php';

$id_usuario = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT telegram_id FROM usuario_verificacao WHERE fk_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (empty($row['telegram_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Telegram não conectado. Conecte sua conta primeiro.']);
    exit;
}

$chat_id   = db_decrypt($row['telegram_id'], $DB_ENCRYPT_KEY);
$bot_token = $TELEGRAM_BOT_TOKEN ?? '';

if (empty($bot_token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token do bot não configurado no servidor.']);
    exit;
}

$url     = "https://api.telegram.org/bot{$bot_token}/sendMessage";
$payload = json_encode(['chat_id' => $chat_id, 'text' => 'Conexão com o Telegram funcionando!']);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resposta  = curl_exec($ch);
$errocurl  = curl_error($ch);
curl_close($ch);

if ($errocurl) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao contatar o Telegram.']);
    exit;
}

$json = json_decode($resposta, true);
if (!$json || !$json['ok']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Telegram recusou a mensagem.']);
    exit;
}

echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem enviada ao Telegram com sucesso!']);
