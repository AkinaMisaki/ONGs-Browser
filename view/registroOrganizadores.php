<?php
session_start();

if (!isset($_SESSION['statusConta'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['statusConta'] === 2) {
    header("Location: criacao_ong.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Organizador</title>
    <link rel="stylesheet" href="css/registroOrganizadores.css">
</head>
<body>

<div class="card">
    <h3>Novo Organizador</h3>
    <form id="meuForm">
        <label>CPF:</label>
        <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="Apenas números ou com traços" required>
        
        <label>RG:</label>
        <input type="text" id="rg" name="rg" maxlength="20" required>
        
        <label>Telefone para contato:</label>
        <input type="text" id="telefone" name="telefone" maxlength="15" placeholder="(11) 99999-9999" required>
        
        <button type="button" onclick="realizarCadastro()">Cadastrar</button>
    </form>
    <div id="resposta"></div>
</div>

<script src="../js/registroOrganizador.js"></script>
</body>
</html>