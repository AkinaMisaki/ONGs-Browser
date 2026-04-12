<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$url_base = "$protocolo://$host/Experiencia Criativa";
?>

<header class="barra-fixa">
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px;">
        <h1 style="color: white; margin: 0; font-size: 1.5rem; cursor: pointer;" onclick="window.location.href='<?= $url_base ?>/index.php'">ONGs Browser</h1>
        
        <nav>
            <button onclick="window.location.href='<?= $url_base ?>/index.php'">Início</button>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button onclick="window.location.href='<?= $url_base ?>/view/gerenciar_conta.php'">Minha Conta</button>
                
                <?php if ($_SESSION['statusConta'] === 2): ?>
                    <button onclick="window.location.href='<?= $url_base ?>/view/criacao_ong.php'" style="background-color: #28a745; color: white;">Criar ONG</button>
                <?php endif; ?>

                <button onclick="window.location.href='<?= $url_base ?>/controller/logout.php'" style="background-color: #dc3545; color: white; border-color: #c82333;">Sair</button>
            
            <?php else: ?>
                <button onclick="window.location.href='<?= $url_base ?>/view/login.php'">Entrar</button>
                <button onclick="window.location.href='<?= $url_base ?>/view/registrar.php'" style="background-color: #28a745; color: white;">Cadastrar</button>
            <?php endif; ?>
        </nav>
    </div>
</header>