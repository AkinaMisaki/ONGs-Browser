<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <link rel="stylesheet" href="css/registro_usuario.css">
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
        <h1>Login de Usuário</h1>

        <form id="formContato">
            <label for="user_acess">Usuário:</label>
            <input type="text" id="user_acess" name="userAcess" placeholder="Digite seu usuário">

            <label for="passCheck">Senha:</label>
            <input type="password" id="passCheck" name="password" placeholder="Digite sua senha">

            <button type="button" onclick="realizarLogin()">Login</button>
            <button><a href="recuperarSenhaView.html">esqueceu senha a senha" ?</a></button>
        </form>
        <a href="registro_usuario.php">Novo por Aqui? Cadastre-se!</a>
    </main>

    <script src="../js/login_usuario.js"></script>
</body>
</html>