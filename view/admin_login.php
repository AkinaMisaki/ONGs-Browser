<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_crud.php');
    exit;
}

include __DIR__ . '/../controller/init/admin_login.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Acesso Restrito</title>
    <link rel="stylesheet" href="css/admin_crud.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<?php include __DIR__ . '/reusable/header.php'; ?>

<main class="login-main">
    <div class="shield">&#128274;</div>
    <h1>Área Restrita</h1>

    <?php if ($timeout): ?>
        <p class="aviso-timeout">Sua sessão expirou por inatividade. Faça login novamente.</p>
    <?php endif; ?>

    <?php if ($viewMode === 'otp_request'): ?>

        <form class="login-form" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="action" value="request_otp">

            <label for="login">Usuário:</label>
            <input type="text" id="login" name="login" placeholder="Digite seu usuário"
                   autocomplete="username" required autofocus>

            <?php if ($erro): ?>
                <p class="erro-login"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p class="sucesso-login"><?= htmlspecialchars($sucesso) ?></p>
            <?php endif; ?>

            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($CAPTCHA_SITE) ?>"></div>
            <button type="submit" class="btn-telegram">Receber código no Telegram</button>
            <a href="admin_login.php?modo=recovery" class="otp-cancel">Perdi meu dispositivo</a>
        </form>

    <?php elseif ($viewMode === 'otp_verify'): ?>

        <form class="login-form" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="action" value="verify_otp">

            <p class="otp-info">
                Código enviado para <strong><?= htmlspecialchars($_SESSION['admin_otp']['login'] ?? '') ?></strong> via Telegram.
                Expira em 5 minutos.
            </p>

            <label for="otp">Código de 6 caracteres:</label>
            <input type="text" id="otp" name="otp" placeholder="Ex: A3BK7Z" maxlength="6"
                   autocomplete="one-time-code" style="text-transform:uppercase;letter-spacing:0.2em;"
                   required autofocus>

            <?php if ($erro): ?>
                <p class="erro-login"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p class="sucesso-login"><?= htmlspecialchars($sucesso) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn-telegram">Verificar código</button>
            <a href="admin_login.php?cancel_otp=1" class="otp-cancel">Cancelar</a>
        </form>

    <?php elseif ($viewMode === 'totp_verify'): ?>

        <form class="login-form" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="action" value="verify_totp">

            <p class="otp-info">
                Telegram verificado. Digite o código de 6 dígitos do <strong>Google Authenticator</strong>.
            </p>

            <label for="codigo">Código do autenticador:</label>
            <input type="text" id="codigo" name="codigo" placeholder="000000" maxlength="6"
                   inputmode="numeric" autocomplete="one-time-code"
                   style="text-align:center;letter-spacing:0.3em;font-size:1.3rem;"
                   required autofocus>

            <?php if ($erro): ?>
                <p class="erro-login"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p class="sucesso-login"><?= htmlspecialchars($sucesso) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn-telegram">Verificar</button>
            <a href="admin_login.php?cancel_otp=1" class="otp-cancel">Cancelar</a>
        </form>

    <?php elseif ($viewMode === 'recovery_start'): ?>

        <form class="login-form" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="action" value="recovery_start">

            <p class="otp-info">Recuperação de acesso. Informe seu usuário para carregar sua pergunta de segurança.</p>

            <label for="login">Usuário:</label>
            <input type="text" id="login" name="login" placeholder="Digite seu usuário"
                   autocomplete="username" required autofocus>

            <?php if ($erro): ?>
                <p class="erro-login"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>

            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($CAPTCHA_SITE) ?>"></div>
            <button type="submit">Continuar</button>
            <a href="admin_login.php?cancel_recovery=1" class="otp-cancel">Cancelar</a>
        </form>

    <?php elseif ($viewMode === 'recovery_question'): ?>

        <form class="login-form" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
            <input type="hidden" name="action" value="verify_answer">

            <p class="otp-info">Responda à sua pergunta de segurança.</p>

            <label><?= htmlspecialchars($recoveryQuestion) ?></label>
            <input type="text" name="answer" placeholder="Sua resposta" required autofocus autocomplete="off">

            <?php if ($erro): ?>
                <p class="erro-login"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>

            <button type="submit">Verificar Resposta</button>
            <a href="admin_login.php?cancel_recovery=1" class="otp-cancel">Cancelar</a>
        </form>

    <?php endif; ?>

</main>

</body>
</html>
