<?php
include __DIR__ . '/../../../config/configuni.php';
if (!isset($_SESSION)) session_start();
$token   = $_GET['token'] ?? null;
$message = null;
if (!$token) {
    header() //Colocar link pra index aqui ou login
} else {
    $stmt = $conn->prepare("SELECT id_usuario, reset_expire FROM usuario WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        $message = "invalid_token";
    } else {
        $user = $result->fetch_assoc();
        if (strtotime($user['reset_expire']) < time()) {
            $message = "token_expired";
        }
    }
    $stmt->close();
    $conn->close();
}   
$showForm = $message === null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/alterar_senha.css">
</head>
<body>

    <div class="barra-fixa">
        <span>Alterar Senha</span>
    </div>

    <main>
        <h1>Alterar Senha</h1>

        <?php if ($showForm): ?>
        <form id="resetForm">
            <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">

            <label for="password">Nova Senha</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                autocomplete="new-password"
            >

            <label for="password_confirm">Nova Senha</label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                required
                minlength="8"
                autocomplete="new-password"
            >

            <button type="submit">Alterar Senha</button>

        </form>
        <?php endif; ?>
    </main>
    <script src="../js/alterar_senha.js"></script>
</body>
</html>