<?php require_once __DIR__ . '/admin_guard.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Gerenciar Dados</title>
    <link rel="stylesheet" href="css/admin_crud.css">
</head>
<body>

<header class="barra-fixa">
    <span class="logo">ONGs Browser &mdash; Admin</span>
    <nav>
        <button onclick="window.location.href='../index.php'">Início</button>
        <button onclick="window.location.href='search.php'">Buscar</button>
        <button onclick="window.location.href='?logout=1'" style="background:#c62828;">Sair</button>
    </nav>
</header>

<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>Tabelas</h2>
        <ul id="tableList">
            <li class="loading">Carregando...</li>
        </ul>
    </aside>

    <!-- Main -->
    <main class="main-content">

        <div id="placeholder" class="placeholder">
            <p>&#8592; Selecione uma tabela para gerenciar os dados.</p>
        </div>

        <div id="tableArea" class="hidden">
            <div class="area-header">
                <h2 id="tableTitle"></h2>
                <button class="btn-primary" onclick="abrirModal()">+ Novo Registro</button>
            </div>

            <div id="mensagem" class="mensagem hidden"></div>

            <div class="table-wrapper">
                <table id="recordsTable">
                    <thead><tr id="tableHead"></tr></thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal -->
<div id="modal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Novo Registro</h2>
            <button class="fechar-modal" onclick="fecharModal()">&#10005;</button>
        </div>
        <form id="formRecord" onsubmit="salvarRegistro(event)">
            <div id="formFields" class="form-fields"></div>
            <div class="modal-footer">
                <button type="button" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>
<div id="overlay" class="overlay hidden" onclick="fecharModal()"></div>

<script src="../js/admin_crud.js"></script>
</body>
</html>
