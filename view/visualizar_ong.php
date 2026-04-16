<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONGs Browser</title>
    <link rel="stylesheet" href="css/visualizar_ong.css">
</head>
<body>

<?php include 'reusable/header.php'; ?>

<main>
    <div id="loading" class="loading">
        <p>Carregando...</p>
    </div>

    <div id="ong-card" class="ong-card" style="display: none;">
        <div id="ong-imagem-container" class="ong-imagem" style="display: none;">
            <img id="ong-imagem" src="" alt="">
        </div>
        <div class="ong-info">
            <h1 id="ong-nome"></h1>
            <p id="ong-proprietario" class="ong-proprietario"></p>
            <p id="ong-descricao"></p>
            <div class="ong-acoes">
                <button class="btn-voltar" onclick="history.back()">&#8592; Voltar</button>
                <a id="ong-doar-link" href="#" class="btn-doar">Fazer Doação</a>
            </div>
        </div>
    </div>

    <div id="erro-container" class="erro-container" style="display: none;">
        <p id="erro-mensagem"></p>
        <a href="../index.php">Voltar ao início</a>
    </div>
</main>

<script src="../js/visualizar_ong.js"></script>
</body>
</html>
