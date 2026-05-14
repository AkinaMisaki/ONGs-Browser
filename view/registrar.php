<?php
session_start();
include_once __DIR__ . '/../config.php';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Página de Prática</title>
    
    <link rel="stylesheet" href="css/registro_usuario.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<?php include 'reusable/header.php'; ?>

    <main>
        <h1>Criação de Usuário</h1>
        
        <div id="mensagem-alerta"></div>

        <form id="formContato" onsubmit="realizarCadastroUsuario(event)">
            <label for="user_name">Nome:</label>
            <input type="text" id="new_user_name" name="userName" placeholder="Digite seu nome">

            <label for="user_name">E-mail:</label>
            <input type="email" id="new_user_email" name="userEmail" placeholder="Digite seu email">

            <label for="user_acess">Novo Usuário:</label>
            <input type="text" id="new_user_acess" name="userAcess" placeholder="Digite seu usuário">

            <label for="new_passCheck">Nova Senha:</label>
            <input type="password" id="new_passCheck" name="password" placeholder="Digite sua senha">
            <div class="alerta-senha">
                Lembre-se: sua senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e pelo menos um caractere especial (!, @, #, etc).
            </div>
            <div class="g-recaptcha" data-sitekey="<?php echo $CAPTCHA_SITE; ?>"></div>
            <button type="submit">Cadastrar</button>
        </form>
    </main>

    <script src="../js/registro_usuario.js"></script>
</body>
</html>
