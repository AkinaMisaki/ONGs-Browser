<?php
// Lê o JSON enviado pelo Telegram no corpo do POST
$update = json_decode(file_get_contents('php://input'), true);

// Extrai o ID do chat e o texto da mensagem
$chat_id = $update['message']['chat']['id'] ?? null;
$texto   = $update['message']['text'] ?? '';

// Envia uma mensagem de volta pelo Telegram como resposta JSON
function reply($chat_id, $text) {
    header('Content-Type: application/json');
    echo json_encode([
        'method'  => 'sendMessage',
        'chat_id' => $chat_id,
        'text'    => $text,
    ]);
}

// Para a execução se não houver mensagem de texto (ex: foto, sticker)
if (!$chat_id || !$texto) exit;

// Responde ao comando /id com o ID do chat do usuário
if ($texto === '/id') {
    reply($chat_id, (string) $chat_id);
} else {
    // Ecoa a mensagem de volta
    reply($chat_id, $texto);
}
?>