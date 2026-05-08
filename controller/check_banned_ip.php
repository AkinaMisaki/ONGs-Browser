<?php
/**
 * Verifica se o IP do visitante está na lista de banidos.
 * Inclua este arquivo após o config.php em controllers que aceitam tráfego público.
 * Se o IP estiver banido, retorna JSON de erro e encerra a execução.
 */
function checkBannedIp(mysqli $conn): void {
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("SELECT id FROM banned_ips WHERE ip_address = ? LIMIT 1");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->store_result();
    $isBanned = $stmt->num_rows > 0;
    $stmt->close();

    if ($isBanned) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
        exit;
    }
}
