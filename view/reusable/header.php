<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$url_base = "$protocolo://$host/universidade";
?>

<header class="barra-fixa">
    <div class="header-container">
        <h1 onclick="window.location.href='<?= $url_base ?>/index.php'">ONGs Browser</h1>
        
        <nav>
            <button onclick="window.location.href='<?= $url_base ?>/index.php'">Início</button>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button onclick="window.location.href='<?= $url_base ?>/view/gerenciar_conta.php'">Minha Conta</button>
                
                <?php if (isset($_SESSION['statusConta']) && $_SESSION['statusConta'] === 2): ?>
                    <button class="btn-sucesso" onclick="window.location.href='<?= $url_base ?>/view/criacao_ong.php'">Criar ONG</button>
                <?php endif; ?>

                <button class="btn-perigo" onclick="window.location.href='<?= $url_base ?>/controller/logout.php'">Sair</button>
            
            <?php else: ?>
                <button onclick="window.location.href='<?= $url_base ?>/view/login.php'">Entrar</button>
                <button class="btn-sucesso" onclick="window.location.href='<?= $url_base ?>/view/registrar.php'">Cadastrar</button>
            <?php endif; ?>
        </nav>
    </div>
</header>