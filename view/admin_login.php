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
</head>
<body>

<?php include __DIR__ . '/reusable/header.php'; ?>

<main class="login-main">
    <div class="shield">&#128274;</div>
    <h1>Área Restrita</h1>

    <?php if ($timeout): ?>
        <p class="aviso-timeout">Sua sessão expirou por inatividade. Faça login novamente.</p>
    <?php endif; ?>

    <form class="login-form" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf']); ?>">

        <label for="login">Usuário:</label>
        <input
            type="text"
            id="login"
            name="login"
            placeholder="Digite seu usuário"
            autocomplete="username"
            required
            autofocus
        >

        <label for="password">Senha:</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Digite sua senha"
            autocomplete="current-password"
            required
        >

        <?php if ($erro): ?>
            <p class="erro-login"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <button type="submit">Entrar</button>
    </form>
</main>

</body>
</html>
