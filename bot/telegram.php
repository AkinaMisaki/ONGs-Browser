<?php
// Envia uma mensagem de volta pelo Telegram como resposta JSON
function reply($chat_id, $text) {
    header('Content-Type: application/json');
    echo json_encode([
        'method'  => 'sendMessage',
        'chat_id' => $chat_id,
        'text'    => $text,
    ]);
}

// Lê o JSON enviado pelo Telegram no corpo do POST
$update = json_decode(file_get_contents('php://input'), true);

// Extrai o ID do chat e o texto da mensagem
$chat_id = $update['message']['chat']['id'] ?? null;
$texto   = $update['message']['text'] ?? '';

// Para a execução se não houver mensagem de texto (ex: foto, sticker)
if (!$chat_id || !$texto) exit;

try {
    include __DIR__ . '/../config.php';

    // Comando /id — retorna o chat_id do usuário
    if ($texto === '/id') {
        reply($chat_id, (string) $chat_id);
        exit;
    }

    if (strpos($texto, '/verificar') === 0) {
        $chatIdHash = hash_hmac('sha256', (string) $chat_id, 'ongs-browser-telegram-index');

        // Vê se essa conta do Telegram já está vinculada a algum usuário do site
        $stmtChk = $conn->prepare("SELECT u.nome_usuario FROM usuario_verificacao uv JOIN usuario u ON u.id_usuario = uv.fk_usuario WHERE uv.telegram_id_hash = ?");
        $stmtChk->bind_param("s", $chatIdHash);
        $stmtChk->execute();
        $rowChk = $stmtChk->get_result()->fetch_assoc();
        if ($rowChk) {
            reply($chat_id, "Este Telegram já está conectado à conta " . $rowChk['nome_usuario'] . ". Desconecte primeiro pelo site se quiser vincular outra conta.");
            exit;
        }

        $t = explode(' ', $texto);
        if (empty($t[1])) {
            reply($chat_id, 'Mande o passkey junto com o comando. (Ex: /verificar 123456789)');
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT uv.telegram_id, u.nome_usuario
             FROM usuario_verificacao uv
             JOIN usuario u ON u.id_usuario = uv.fk_usuario
             WHERE uv.telegram_pass = ?"
        );
        $stmt->bind_param("s", $t[1]);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            if ($usuario['telegram_id'] != null) {
                reply($chat_id, "Você já conectou esta conta do Telegram com uma conta do site.");
            } else {
                $chatIdCifrado = aes_encrypt((string) $chat_id, $DB_ENCRYPT_KEY);
                $stmt2 = $conn->prepare("UPDATE usuario_verificacao SET telegram_id = ?, telegram_id_hash = ?, telegram_pass = NULL WHERE telegram_pass = ?");
                $stmt2->bind_param("sss", $chatIdCifrado, $chatIdHash, $t[1]);
                $stmt2->execute();
                reply($chat_id, "Conectado com a conta " . $usuario['nome_usuario'] . " com sucesso!");
            }
        } else {
            reply($chat_id, 'Passkey inválida. Vá para a página de gerenciamento de conta e gere uma nova clicando em "Conectar Telegram".');
        }
        exit;
    }

    // Ecoa a mensagem de volta
    reply($chat_id, $texto);

} catch (Throwable $e) {
    reply($chat_id, "Ocorreu um erro. Tente novamente mais tarde.");
}
