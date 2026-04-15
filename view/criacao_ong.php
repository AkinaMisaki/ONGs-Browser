<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criação de Ongs</title>
    <link rel="stylesheet" href="criacaoOng.css">
</head>
<body>

<?php include 'reusable/header.php'; ?>

    <main>
        <h1>Criação de Ongs</h1>
        
        <form id="formContato">
            <label for="nomeOng">Nome da Ong</label>
            <input type="text" id="nomeOng" name="Nome_Ong" placeholder="Digite o nome da Ong" required>

            <label for="descricaoOng">Descrição da Ong</label>
            <textarea id="descricaoOng" name="descricaoOng" placeholder="Escreva uma descrição" rows="4" required></textarea>
            
            <label for="imagemOng">Imagem da Ong</label>
            <div id="drop-zone" class="drop-zone">
                 <span>Arraste sua imagem aqui</span>
                 <input type="file" id="imagemOng" name="imagemOng" hidden>
            </div>

            <button type="submit">Registrar Ong</button>
        </form>
        <a href="registrar.php">Novo por Aqui? Cadastre-se!</a>
    </main>

    <script src="../js/criacao_ong.js"></script>
</body>
</html>