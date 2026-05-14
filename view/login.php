<?php
include __DIR__ . '/../controller/init/login.php';

$otpPending = !empty($_SESSION['user_otp']);
$otpLogin   = $otpPending ? htmlspecialchars($_SESSION['user_otp']['login']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login_usuario.css">
    <script src="https://www.google.com/recaptcha/api.js?onload=initRecaptcha&render=explicit" async defer></script>
    <script>const CAPTCHA_SITE = '<?= htmlspecialchars($CAPTCHA_SITE) ?>';</script>
</head>
<body>

<?php include 'reusable/header.php'; ?>

<main>
    <h1>Login de Usuário</h1>

    <?php if (!$otpPending): ?>
    <div class="login-tabs">
        <button class="login-tab active" id="tab-senha" onclick="switchTab('senha')">Senha</button>
        <button class="login-tab" id="tab-telegram" onclick="switchTab('telegram')">Telegram</button>
    </div>
    <?php endif; ?>

    <!-- Password form -->
    <form id="form-senha" onsubmit="realizarLogin(event)" <?= $otpPending ? 'style="display:none"' : '' ?>>
        <label for="user_acess">Usuário:</label>
        <input type="text" id="user_acess" name="userAcess" placeholder="Digite seu usuário">

        <label for="passCheck">Senha:</label>
        <input type="password" id="passCheck" name="password" placeholder="Digite sua senha">

        <div class="opcoes-senha">
            <a href="recuperar_senha.php" class="link-esqueceu">Esqueceu a senha?</a>
        </div>

        <div id="recaptcha-senha"></div>
        <button type="submit">Login</button>
    </form>

    <!-- Telegram OTP request form -->
    <form id="form-telegram" onsubmit="solicitarOtp(event)" style="display:none">
        <label for="tg-usuario">Usuário:</label>
        <input type="text" id="tg-usuario" name="usuario" placeholder="Digite seu usuário">

        <div id="recaptcha-telegram"></div>
        <button type="submit" class="btn-telegram">Enviar código no Telegram</button>
    </form>

    <!-- OTP verify form -->
    <form id="form-otp" onsubmit="verificarOtp(event)" <?= $otpPending ? '' : 'style="display:none"' ?>>
        <p class="otp-info">
            Código enviado para <strong id="otp-login-label"><?= $otpLogin ?></strong> via Telegram.
            Expira em 5 minutos.
        </p>

        <label for="otp-input">Código de 6 caracteres:</label>
        <input
            type="text"
            id="otp-input"
            name="otp"
            placeholder="Ex: A3BK7Z"
            maxlength="6"
            autocomplete="one-time-code"
            style="text-transform: uppercase; letter-spacing: 0.2em;"
            required
            autofocus
        >

        <button type="submit" class="btn-telegram">Verificar código</button>
        <a class="otp-cancel" onclick="cancelarOtp()">Cancelar e usar outro método</a>
    </form>

    <div class="links-externos">
        <a href="registrar.php">Novo por aqui? Cadastre-se!</a>
    </div>
</main>

<script src="../js/login_usuario.js"></script>
</body>
</html>
