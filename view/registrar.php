<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Página de Prática</title>
    
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <header class="barra-fixa">
        <nav>
            <button onclick="window.location.href='../index.php'">Início</button>
            <button onclick="window.location.href='sobre.html'">Sobre</button>
            <button onclick="window.location.href='contato.html'">Contato</button>
        </nav>
    </header>

    <main>
        <h1>Criação de Usuário</h1>
        
        <div id="mensagem-alerta"></div>

        <form id="formContato">
            <label for="user_name">Nome:</label>
            <input type="text" id="new_user_name" name="userName" placeholder="Digite seu nome">

            <label for="user_name">E-mail:</label>
            <input type="email" id="new_user_email" name="userEmail" placeholder="Digite seu email">

            <label for="user_acess">Novo Usuário:</label>
            <input type="text" id="new_user_acess" name="userAcess" placeholder="Digite seu usuário">

            <label for="passCheck">Nova Senha:</label>
            <input type="password" id="new_passCheck" name="password" placeholder="Digite sua senha">

            <button type="button" onclick="realizarCadastroUsuario()">Cadastrar</button>
        </form>
    </main>

    <script src="../js/login.js"></script>
</body>
</html>
