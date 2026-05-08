<?php
include_once __DIR__ . "/../config.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONGs Browser — Verificação</title>
    <link rel="stylesheet" href="css/captcha.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

    <div class="captcha-card">
        <div class="captcha-logo">ONGs Browser</div>

        <div class="captcha-icon">&#128373;</div>

        <p class="captcha-title">Verificação de acesso</p>
        <p class="captcha-subtitle">Confirme que você não é um robô para continuar.</p>

        <form id="formCaptcha" action="../controller/captcha_controller.php" method="POST">
            <div class="captcha-widget">
                <div class="g-recaptcha"
                     data-sitekey="<?php echo $CAPTCHA_SITE; ?>"
                     data-callback="autoSubmitForm">
                </div>
            </div>
        </form>
    </div>

    <script>
        function autoSubmitForm() {
            document.getElementById("formCaptcha").submit();
        }
    </script>

</body>
</html>
