<?php
session_start();
include __DIR__ . '/../../../config/configuni.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    header('Location: /login.php');
    exit;
}

$stmt = $conn->prepare("SELECT id_usuario, reset_expire FROM usuario WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    header('Location: /login.php?erro=invalid_token');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (strtotime($user['reset_expire']) < time()) {
    header('Location: /login.php?erro=token_expired');
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Alterar Senha</title>
    <link rel="stylesheet" href="css/alterar_senha.css">
</head>
<body>
    <div class="barra-fixa">
        <span>Alterar Senha</span>
    </div>
    <main>
        <h1>Alterar Senha</h1>
        <p id="mensagem"></p>
        <form id="resetForm">
            <input type="hidden" id="token"      name="token"      value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <label for="password">Nova Senha</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
            <label for="password_confirm">Confirmar Senha</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
            <button type="submit">Alterar Senha</button>
        </form>
    </main>
    <script src="../js/alterar_senha.js"></script>
</body>
</html>