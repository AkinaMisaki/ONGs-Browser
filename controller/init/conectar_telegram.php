<?php
include_once __DIR__ . '/../../config.php';

$id_usuario = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT telegram_id, telegram_pass FROM usuario_verificacao WHERE fk_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($row['telegram_id'])) {
    header('Location: gerenciar_conta.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function gerarPasskeyUnica($conn) {
    do {
        $candidate = strtoupper(bin2hex(random_bytes(6)));
        $chk = $conn->prepare("SELECT 1 FROM usuario_verificacao WHERE telegram_pass = ?");
        $chk->bind_param("s", $candidate);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
        $chk->close();
    } while ($exists);
    return $candidate;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'regenerar'
    && !empty($_POST['csrf_token'])
    && $_POST['csrf_token'] === $_SESSION['csrf_token']
) {
    $telepass = gerarPasskeyUnica($conn);
    $stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_pass = ? WHERE fk_usuario = ?");
    $stmt->bind_param("si", $telepass, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: conectar_telegram.php');
    exit;
}

if (empty($row['telegram_pass'])) {
    $telepass = gerarPasskeyUnica($conn);
    $stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_pass = ? WHERE fk_usuario = ?");
    $stmt->bind_param("si", $telepass, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $row['telegram_pass'] = $telepass;
}

$conn->close();

$passkey = htmlspecialchars($row['telegram_pass']);
