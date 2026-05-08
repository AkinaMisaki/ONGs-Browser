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
    <span class="admin-user">&#128100; <?php echo htmlspecialchars($_SESSION['admin_user_login'] ?? 'admin'); ?></span>
    <nav>
        <button onclick="window.location.href='../index.php'">Início</button>
        <button onclick="window.location.href='search.php'">Buscar</button>
        <button onclick="window.location.href='?logout=1'" style="background:#c62828;">Sair</button>
    </nav>
</header>

<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="sidebar-section-label">Ferramentas</div>
        <ul id="toolList">
            <li data-tool="broadcast" onclick="openBroadcast()">&#128226; Broadcast</li>
            <li data-tool="bannedips" onclick="openBannedIps()">&#128683; IPs Banidos</li>
        </ul>

        <div class="sidebar-divider"></div>

        <div class="sidebar-section-label">Tabelas</div>
        <ul id="tableList">
            <li class="loading">Carregando...</li>
        </ul>

    </aside>

    <!-- Main -->
    <main class="main-content">

        <div id="placeholder" class="placeholder">
            <p>&#8592; Selecione uma tabela ou ferramenta.</p>
        </div>

        <!-- Generic table CRUD area -->
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

        <!-- Broadcast panel -->
        <div id="broadcastArea" class="hidden">
            <div class="area-header">
                <h2>&#128226; Broadcast via Telegram</h2>
            </div>
            <div id="broadcastMsg" class="mensagem hidden"></div>
            <div class="tool-panel">
                <p class="tool-description">Envia uma mensagem para todos os usuários com Telegram conectado.</p>
                <form id="broadcastForm" onsubmit="sendBroadcast(event)">
                    <div class="field-group">
                        <label for="broadcastText">Mensagem:</label>
                        <textarea id="broadcastText" name="message" rows="5" placeholder="Digite a mensagem do broadcast..." required></textarea>
                    </div>
                    <div class="tool-actions">
                        <button type="submit" class="btn-primary">&#128226; Enviar para todos</button>
                    </div>
                </form>
                <div id="broadcastResult" class="broadcast-result hidden"></div>
            </div>
        </div>

        <!-- Banned IPs panel -->
        <div id="bannedIpsArea" class="hidden">
            <div class="area-header">
                <h2>&#128683; IPs Banidos</h2>
            </div>
            <div id="banMsg" class="mensagem hidden"></div>
            <div class="tool-panel">
                <form id="banForm" onsubmit="banIp(event)">
                    <div class="ban-form-row">
                        <div class="field-group" style="flex:1">
                            <label for="banIpInput">Endereço IP:</label>
                            <input type="text" id="banIpInput" name="ip_address" placeholder="ex: 192.168.1.1" required>
                        </div>
                        <div class="field-group" style="flex:2">
                            <label for="banReasonInput">Motivo (opcional):</label>
                            <input type="text" id="banReasonInput" name="reason" placeholder="ex: Spam, ataque de força bruta...">
                        </div>
                        <div class="field-group ban-btn-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-excluir">&#128683; Banir IP</button>
                        </div>
                    </div>
                </form>

                <div class="table-wrapper" style="margin-top:1.5rem">
                    <table id="banTable">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Motivo</th>
                                <th>Banido em</th>
                                <th>Banido por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="banTableBody">
                            <tr class="empty-row"><td colspan="5">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
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
