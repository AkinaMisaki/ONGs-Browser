<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Organizador</title>
    <link rel="stylesheet" href="css/registroOrganizadores.css">
</head>
<body>

    <div class="barra-fixa">
        <div class="header-container">
            <h1 onclick="window.location.href='/universidade/index.php'">ONGs Browser</h1>
            <nav>
                <button onclick="window.location.href='gerenciar_conta.php'">Minha Conta</button>
                <button onclick="window.location.href='/universidade/index.php'">Início</button>
            </nav>
        </div>
    </div>

    <main>
        <h1>Cadastro de Organizador</h1>

        <div id="mensagem-global" class="mensagem-global hidden"></div>

        <section class="card">
            <h2>Suas Informações</h2>
            <form id="meuForm" onsubmit="realizarCadastro(event)">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="Apenas números ou com traços" required>

                <label for="rg">RG</label>
                <input type="text" id="rg" name="rg" maxlength="20" required>

                <label for="telefone">Telefone para contato</label>
                <input type="text" id="telefone" name="telefone" maxlength="15" placeholder="(11) 99999-9999" required>

                <button type="submit">Cadastrar</button>
            </form>
        </section>
    </main>

    <script src="../js/registroOrganizador.js"></script>
</body>
</html>
