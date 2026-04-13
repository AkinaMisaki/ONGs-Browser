<?php include '../../view/reusable/header.php'; 

if (!$usuario_id) {
    header('Location: ../../view/login.php');
    exit();
}
?>



<main>
    <h2>Configurar autenticação em dois fatores</h2>

    <?php if ($erro): ?>
        <p style="color: red;"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>

    <?php else: ?>
        <p>Escaneie o QR Code abaixo no Google Authenticator ou Microsoft Authenticator:</p>

        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($uri) ?>" alt="QR Code 2FA">

        <p>Ou digite o código manualmente no app:</p>
        <strong><?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?></strong>

        <p>Após escanear, digite o código gerado pelo app para confirmar:</p>
        <form action="../../controller/2fa/verificar.php" method="POST">
            <input type="text" name="codigo" maxlength="6" placeholder="000000" required>
            <button type="submit">Confirmar</button>
        </form>
    <?php endif; ?>
</main>