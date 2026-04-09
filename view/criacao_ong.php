<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <link rel="stylesheet" href="index.css">
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
        <h1>Criação de Ongs</h1>

        <form id="formContato">
            <label for="NomeOng">Nome da Ong</label>
            <input type="text" id="nomeOng" name="Nome_Ong" placeholder="Digite o nome da Ong">

            <label for="SiglaOng">Sigla da Ong:</label>
            <input type="text" id="SiglaOng" name="SiglaOng" placeholder="Digite a sigla da Ong">

            <label for="descricao">Descriçao da Ong</label>
            <input type="text" id="descricaoOng" name="descricaoOng" placeholder="Escreva uma descrição">

            <label for="imagemOng">Imagem da Ong</label>
            <input type="file" accept="image/jpeg, image/png" id="imagemOng" name="imagemOng" placeholder="arraste ou baixe uma imagem aqui">

            <button type="button" onclick="realizarLogin()">Registrar Ong</button>
            <button><a href=""></a></button>
        </form>
        <a href="registrar.php">Novo por Aqui? Cadastre-se!</a>
    </main>

    <script src="index.js"></script>
</body>
</html>