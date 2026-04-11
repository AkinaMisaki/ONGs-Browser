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

            <label for="descricao">Descrição da Ong</label>
            <textarea id="descricaoOng" name="descricaoOng" placeholder="Escreva uma descrição" rows="4"></textarea>
            
            <label for="imagemOng">Imagem da Ong</label>
            <div id="drop-zone" class="drop-zone">
                 <span>Arraste sua imagem aqui</span>
                 <input type="file" id="imagemOng" name="imagemOng" hidden>
            </div>

            <button type="button">Registrar Ong</button>

        </form>
        <a href="registrar.php">Novo por Aqui? Cadastre-se!</a>
    </main>

    <script src="../js/criacao_ong.js"></script>
</body>
</html>