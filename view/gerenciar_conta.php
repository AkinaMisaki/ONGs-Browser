<?php
session_start();
include __DIR__ . '/../conn/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . $url_base . "/view/login.php");
    exit;
}


$stmt = $conn->prepare("SELECT nome_usuario, email, usuario_login FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: " . $url_base . "/view/login.php");
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();
$conn->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        <span>Gerenciar Conta</span>
        <nav>
            <button onclick="window.location.href='<?= $url_base ?>/index.php'">Início</button>
            <button onclick="realizarLogout()" class="btn-logout">Sair</button>
        </nav>
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

    <script src="<?= $url_base ?>/js/gerenciar_conta.js"></script>
</body>
</html>
