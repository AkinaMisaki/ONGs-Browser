<?php
session_start();
include __DIR__ . '/../controller/init/alterar_senha.php';
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
    <?php include __DIR__ . '/reusable/header.php'; ?>
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