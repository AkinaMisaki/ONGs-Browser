<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
    <link rel="stylesheet" href="css/login_usuario.css">
</head>
<body>

<?php include __DIR__ . '/reusable/header.php'; ?>

<main>
    <h1>Recuperação de Senha</h1>

    <div id="mensagem-alerta"></div>

    <form id="formContato" onsubmit="realizarCadastroUsuario(event)">
        <label for="new_mailCheck">Email:</label>
        <input
            type="email"
            id="new_mailCheck"
            name="email"
            placeholder="Digite seu email"
            autocomplete="email"
        >
        <button type="submit">Enviar</button>
    </form>

    <div class="form-links">
        <a href="login.php">Voltar ao Login</a>
    </div>
</main>

<script src="../js/reset_senha.js"></script>
</body>
</html>
