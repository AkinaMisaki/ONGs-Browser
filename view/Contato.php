<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato — ONGs Browser</title>
    <link rel="stylesheet" href="css/login_usuario.css">
</head>
<body>

<?php include __DIR__ . '/reusable/header.php'; ?>

<main>
    <div style="padding: 20px;">
        <form class="centro cadastro" action="" method="post">
            <p style="text-align: center;">Contate-nos</p>
            <br>

            <label for="contato_nome">Nome:</label>
            <input type="text" id="contato_nome" name="nome" required><br><br>

            <label for="contato_email">Email:</label>
            <input type="text" id="contato_email" name="email" required><br><br>

            <label for="contato_assunto">Assunto:</label>
            <input type="text" id="contato_assunto" name="assunto" required><br><br>

            <label for="contato_mensagem">Mensagem:</label>
            <textarea id="contato_mensagem" name="mensagem" rows="5" style="width: 100%;" required></textarea><br><br>

            <input type="submit" value="Enviar Mensagem">
        </form>
    </div>
</main>

</body>
</html>
