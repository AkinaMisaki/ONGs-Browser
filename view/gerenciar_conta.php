<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /universidade/view/login.php');
    exit;
}

include __DIR__ . '/../config.php';

$stmt = $conn->prepare("SELECT nome_usuario, email, usuario_login, codVerificador FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header('Location: /universidade/view/login.php');
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

$stmtProp = $conn->prepare("SELECT id_prop FROM proprietario_ong WHERE fk_usuario = ? LIMIT 1");
$stmtProp->bind_param("i", $_SESSION['usuario_id']);
$stmtProp->execute();
$eProprietario = $stmtProp->get_result()->num_rows > 0;
$stmtProp->close();

$conn->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$tem2fa = !empty($usuario['codVerificador']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Conta</title>
    <link rel="stylesheet" href="css/gerenciar_conta.css">
</head>
<body>

    <div class="barra-fixa">
        <div class="header-container">
            <h1>Gerenciar Conta</h1>
            <nav>
                <button onclick="window.location.href='/universidade/index.php'">Início</button>
                <button onclick="realizarLogout()" class="btn-logout">Sair</button>
            </nav>
        </div>
    </div>

    <main>
        <h1>Minha Conta</h1>

        <div id="mensagem-global" class="mensagem-global hidden"></div>

        <!-- Informações atuais -->
        <section class="card card-info">
            <h2>Suas Informações</h2>
            <ul class="info-lista">
                <li><span class="info-label">Nome:</span> <span id="nome-atual"><?= htmlspecialchars($usuario['nome_usuario']) ?></span></li>
                <li><span class="info-label">Usuário:</span> <?= htmlspecialchars($usuario['usuario_login']) ?></li>
                <li><span class="info-label">E-mail:</span> <?= htmlspecialchars($usuario['email']) ?></li>
            </ul>
        </section>

        <!-- Alterar Nome -->
        <section class="card">
            <h2>Alterar Nome</h2>
            <form id="formAlterarNome">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="acao" value="alterar_nome">
                <label for="novo_nome">Novo Nome</label>
                <input type="text" id="novo_nome" name="novo_nome" placeholder="Digite seu novo nome" required>
                <button type="submit">Salvar Nome</button>
            </form>
        </section>

        <!-- Alterar Senha -->
        <section class="card">
            <h2>Alterar Senha</h2>
            <form id="formAlterarSenha">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="acao" value="alterar_senha">
                <label for="senha_atual">Senha Atual</label>
                <input type="password" id="senha_atual" name="senha_atual" placeholder="Digite sua senha atual" required autocomplete="current-password">
                <label for="nova_senha">Nova Senha</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres" required autocomplete="new-password">
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a nova senha" required autocomplete="new-password">
                <div class="alerta-senha">
                    A senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e pelo menos um caractere especial (!, @, #, etc).
                </div>
                <button type="submit">Alterar Senha</button>
            </form>
        </section>

        <!-- LGPD - Exportar Dados -->
        <section class="card card-lgpd">
            <h2>Exportar Meus Dados</h2>
            <p class="lgpd-descricao">
                Em conformidade com a <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>,
                Art. 18, você tem o direito de acessar e obter uma cópia dos seus dados pessoais armazenados
                em nossa plataforma.
            </p>
            <form id="formExportarDados">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="acao" value="exportar_dados">
                <button type="submit" class="btn-lgpd">Baixar Meus Dados</button>
            </form>
        </section>

        <!-- Autenticação em Dois Fatores -->
        <section class="card card-2fa <?= $tem2fa ? 'ativa' : '' ?>">
            <h2>Autenticação em Dois Fatores (2FA)</h2>

            <?php if ($tem2fa): ?>
                <p class="status-2fa ativo">2FA ativado via Google Authenticator</p>
                <p class="lgpd-descricao">
                    Para desativar o 2FA, confirme com um código atual do seu aplicativo autenticador.
                </p>
                <form id="formDesativar2fa">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="acao" value="desativar_2fa">
                    <label for="totp_desativar">Código do Google Authenticator</label>
                    <input type="text" id="totp_desativar" name="codigo_totp"
                           maxlength="6" placeholder="000000" inputmode="numeric"
                           autocomplete="one-time-code" required>
                    <button type="submit" class="btn-perigo">Desativar 2FA</button>
                </form>
            <?php else: ?>
                <p class="status-2fa inativo">2FA desativado</p>
                <p class="lgpd-descricao">
                    Adicione uma camada extra de segurança à sua conta. Após ativar, cada login
                    exigirá um código de 6 dígitos gerado pelo <strong>Google Authenticator</strong>
                    (ou qualquer app TOTP compatível).
                </p>
                <button type="button" id="btnGerarQr" class="btn-2fa-ativar">Configurar 2FA</button>

                <div id="setup2fa" class="setup-2fa hidden">
                    <p class="instrucao-qr">
                        1. Abra o <strong>Google Authenticator</strong> e toque em "+"<br>
                        2. Escaneie o QR code abaixo<br>
                        3. Digite o código gerado para confirmar
                    </p>
                    <div id="qrContainer" class="qr-container"></div>
                    <details class="secret-manual">
                        <summary>Não consegue escanear? Use a chave manual</summary>
                        <code id="secretManual"></code>
                    </details>
                    <form id="formAtivar2fa">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="acao" value="ativar_2fa">
                        <label for="totp_confirmar">Código de confirmação</label>
                        <input type="text" id="totp_confirmar" name="codigo_totp"
                               maxlength="6" placeholder="000000" inputmode="numeric"
                               autocomplete="one-time-code" required>
                        <button type="submit">Confirmar e Ativar</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        <!-- Tornar-se Proprietário -->
        <section class="card card-proprietario">
            <h2>Gerenciar ONGs</h2>
            <?php if ($eProprietario): ?>
                <p class="lgpd-descricao">
                    Você já é um proprietário registrado. Acesse para criar e gerenciar suas ONGs.
                </p>
                <button type="button" class="btn-proprietario" onclick="window.location.href='criacao_ong.php'">
                    Criar uma ONG
                </button>
            <?php else: ?>
                <p class="lgpd-descricao">
                    Registre-se como proprietário para poder criar e gerenciar ONGs na plataforma.
                </p>
                <button type="button" class="btn-proprietario" onclick="window.location.href='registroOrganizadores.php'">
                    Tornar-se Proprietário de ONG
                </button>
            <?php endif; ?>
        </section>

        <!-- LGPD - Deletar Conta -->
        <section class="card card-perigo">
            <h2>Excluir Minha Conta</h2>
            <p class="lgpd-descricao">
                Em conformidade com a <strong>LGPD, Art. 18</strong>, você tem o direito de solicitar a
                exclusão dos seus dados pessoais. Essa ação é <strong>irreversível</strong>: sua conta,
                dados cadastrais e histórico serão permanentemente removidos.
            </p>
            <form id="formDeletarConta">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="acao" value="deletar_conta">
                <label for="senha_delecao">Confirme sua Senha</label>
                <input type="password" id="senha_delecao" name="senha_delecao" placeholder="Digite sua senha para confirmar" required autocomplete="current-password">
                <label class="checkbox-label">
                    <input type="checkbox" id="confirmar_delecao" required>
                    Entendo que esta ação é irreversível e que todos os meus dados serão excluídos.
                </label>
                <button type="submit" class="btn-perigo">Excluir Minha Conta</button>
            </form>
        </section>

    </main>

    <script src="../js/gerenciar_conta.js"></script>
</body>
</html>
