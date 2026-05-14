<?php
require __DIR__ . '/reusable/safeguard.php';
include __DIR__ . '/../controller/init/conectar_telegram.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar Telegram</title>
    <link rel="stylesheet" href="css/gerenciar_conta.css">
    <link rel="stylesheet" href="css/conectar_telegram.css">
</head>
<body>

    <?php include __DIR__ . '/reusable/header.php'; ?>

    <main>
        <h1>Conectar Telegram</h1>

        <div id="mensagem-global" class="mensagem-global hidden"></div>

        <section class="card card-lgpd">
            <h2>Como conectar</h2>
            <ul class="steps">
                <li>Abra o Telegram e <a href="https://t.me/ongsbrowserbot" target="_blank">encontre o nosso bot</a>.</li>
                <li>Envie o comando abaixo com sua passkey:</li>
            </ul>

            <div class="passkey-box">
                <code id="passkey-text">/verificar <?= $passkey ?></code>
                <button class="btn-copiar" onclick="copiarPasskey()">Copiar</button>
            </div>

            <p class="lgpd-descricao" style="margin-top:1rem; margin-bottom:0;">
                A passkey expira após o uso. Se você precisar de uma nova, clique em <strong>Regenerar Passkey</strong>.
            </p>
        </section>

        <section class="card">
            <h2>Regenerar Passkey</h2>
            <p class="lgpd-descricao">Gera uma nova passkey e invalida a atual.</p>
            <form method="POST" action="conectar_telegram.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="acao" value="regenerar">
                <button type="submit">Regenerar Passkey</button>
            </form>
        </section>

        <section class="card">
            <button type="button" class="btn-voltar" onclick="window.location.href='gerenciar_conta.php'">
                Voltar para Gerenciar Conta
            </button>
        </section>
    </main>

    <script>
        function copiarPasskey() {
            const texto = document.getElementById('passkey-text').textContent;
            navigator.clipboard.writeText(texto).then(() => {
                const btn = document.querySelector('.btn-copiar');
                btn.textContent = 'Copiado!';
                setTimeout(() => { btn.textContent = 'Copiar'; }, 2000);
            });
        }
    </script>
</body>
</html>
