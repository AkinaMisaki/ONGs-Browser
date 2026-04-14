<?php 

include 'reusable/header.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Página de Prática</title>
    <link rel="stylesheet" href="css/registro_usuario.css">
</head>
<body>

    <main>
        <h1>Criação de Usuário</h1>
        
        <div id="mensagem-alerta"></div>

        <form id="formContato">
            <label for="new_user_name">Nome:</label>
            <input type="text" id="new_user_name" name="userName" placeholder="Digite seu nome">

            <label for="new_user_email">E-mail:</label>
            <input type="email" id="new_user_email" name="userEmail" placeholder="Digite seu email">

            <label for="new_user_acess">Novo Usuário:</label>
            <input type="text" id="new_user_acess" name="userAcess" placeholder="Digite seu usuário">

            <label for="new_passCheck">Nova Senha:</label>
            <input type="password" id="new_passCheck" name="password" placeholder="Digite sua senha">
            
            <div class="alerta-senha">
                Lembre-se: sua senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e pelo menos um caractere especial (!, @, #, etc).       
            </div>
            
            <button type="button" onclick="realizarCadastroUsuario()">Cadastrar</button>
        </form>
    </main>

    <script src="<?php echo $url_base; ?>/js/registro_usuario.js"></script>
</body>
</html>