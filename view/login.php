<?php
session_start();

include_once __DIR__ . '/../config.php';


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login_usuario.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<?php include 'reusable/header.php'; ?>

    <main>
        <h1>Login de Usuário</h1>

        <form id="formContato" onsubmit="realizarLogin(event)">
            <label for="user_acess">Usuário:</label>
            <input type="text" id="user_acess" name="userAcess" placeholder="Digite seu usuário">

            <label for="passCheck">Senha:</label>
            <input type="password" id="passCheck" name="password" placeholder="Digite sua senha">

            <div class="opcoes-senha">
                <a href="recuperar_senha.php" class="link-esqueceu">Esqueceu a senha?</a>
            </div>
            <div class="g-recaptcha" data-sitekey="<?php echo $CAPTCHA_SITE; ?>"></div>
            <button type="submit">Login</button>
        </form>
        
        <div class="links-externos">
            <a href="registrar.php">Novo por aqui? Cadastre-se!</a>
        </div>
    </main>

    <script src="../js/login_usuario.js"></script> </body>
</html>