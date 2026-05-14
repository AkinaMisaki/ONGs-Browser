<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_root = rtrim(str_replace('\\', '/', str_replace(
    realpath($_SERVER['DOCUMENT_ROOT']),
    '',
    realpath(__DIR__ . '/../..')
)), '/');
?>

<link rel="stylesheet" href="<?= $_root ?>/view/css/header.css">

<header class="barra-fixa">
    <div class="header-container">
        <h1 onclick="window.location.href='<?= $_root ?>/index.php'">ONGs Browser</h1>

        <nav>
            <button onclick="window.location.href='<?= $_root ?>/index.php'">Início</button>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button onclick="window.location.href='<?= $_root ?>/view/gerenciar_conta.php'">Minha Conta</button>

                <?php if (isset($_SESSION['statusConta']) && $_SESSION['statusConta'] === 2): ?>
                    <button class="btn-sucesso" onclick="window.location.href='<?= $_root ?>/view/criacao_ong.php'">Criar ONG</button>
                <?php endif; ?>

                <button class="btn-perigo" onclick="window.location.href='<?= $_root ?>/controller/logout.php'">Sair</button>

            <?php else: ?>
                <button onclick="window.location.href='<?= $_root ?>/view/login.php'">Entrar</button>
                <button class="btn-sucesso" onclick="window.location.href='<?= $_root ?>/view/registrar.php'">Cadastrar</button>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php include __DIR__ . '/cookie_banner.php'; ?>