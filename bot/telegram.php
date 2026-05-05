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

    // Busca o estado atual da conversa do usuário no banco
    function getEstado($conn, $chat_id) {
        $stmt = $conn->prepare("SELECT estado, usuario_login FROM bot_sessao WHERE chat_id = ?");
        $stmt->bind_param("s", $chat_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Salva o estado da conversa no banco
    function setEstado($conn, $chat_id, $estado, $usuario_login = null) {
        $stmt = $conn->prepare("
            INSERT INTO bot_sessao (chat_id, estado, usuario_login)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = ?, usuario_login = ?
        ");
        $stmt->bind_param("sssss", $chat_id, $estado, $usuario_login, $estado, $usuario_login);
        $stmt->execute();
    }

    // Remove o estado da conversa do banco
    function clearEstado($conn, $chat_id) {
        $stmt = $conn->prepare("DELETE FROM bot_sessao WHERE chat_id = ?");
        $stmt->bind_param("s", $chat_id);
        $stmt->execute();
    }

    $sessao = getEstado($conn, $chat_id);
    $estado = $sessao['estado'] ?? null;

    // Comando /id — retorna o chat_id do usuário
    if ($texto === '/id') {
        reply($chat_id, (string) $chat_id);
        exit;
    }

    // Comando /login — inicia o fluxo de login
    if ($texto === '/login') {
        setEstado($conn, $chat_id, 'aguardando_usuario');
        reply($chat_id, "Digite seu usuário:");
        exit;
    }

    // Fluxo de login — aguardando usuário
    if ($estado === 'aguardando_usuario') {
        setEstado($conn, $chat_id, 'aguardando_senha', $texto);
        reply($chat_id, "Digite sua senha:");
        exit;
    }

    // Fluxo de login — aguardando senha
    if ($estado === 'aguardando_senha') {
        $usuario_login = $sessao['usuario_login'];
        $rawSenha = $texto;

        $stmt = $conn->prepare("
            SELECT id_usuario, usuario_login, usuario_password, statusConta, codVerificador
            FROM usuario
            WHERE usuario_login = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $usuario_login);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        clearEstado($conn, $chat_id);

        if (!$usuario || !password_verify($rawSenha, $usuario['usuario_password'])) {
            reply($chat_id, "Usuário ou senha incorretos.");
            exit;
        }

        if ((int) $usuario['statusConta'] <= 0) {
            reply($chat_id, "Conta inativa. Confirme seu email antes de continuar.");
            exit;
        }

        // Conta com 2FA — não suportado pelo bot
        if (!empty($usuario['codVerificador'])) {
            reply($chat_id, "Sua conta tem 2FA ativado. Use o site para fazer login.");
            exit;
        }

        setEstado($conn, $chat_id, 'logado', $usuario['usuario_login']);
        reply($chat_id, "Login realizado! Bem-vindo, " . $usuario['usuario_login'] . ".");
        exit;
    }

    // Comando /status — mostra se está logado
    if ($texto === '/status') {
        if ($estado === 'logado' && !empty($sessao['usuario_login'])) {
            reply($chat_id, "Logado como " . $sessao['usuario_login'] . ".");
        } else {
            reply($chat_id, "Você não está logado. Use /login.");
        }
        exit;
    }

    // Comando /logout — encerra a sessão
    if ($texto === '/logout') {
        if ($estado === 'logado') {
            clearEstado($conn, $chat_id);
            reply($chat_id, "Logout realizado.");
        } else {
            reply($chat_id, "Você não está logado.");
        }
        exit;
    }

    // Ecoa a mensagem de volta
    reply($chat_id, $texto);

} catch (Throwable $e) {
    reply($chat_id, "Ocorreu um erro. Tente novamente mais tarde.");
}
