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

define('MAX_ATTEMPTS',     5);
define('LOCKOUT_DURATION', 900); // 15 minutos

require_once __DIR__ . '/../config.php';

function lockoutFile(string $ip): string {
    return sys_get_temp_dir() . '/ongs_admin_' . md5($ip) . '.json';
}

function getLockout(string $ip): array {
    $file = lockoutFile($ip);
    if (!file_exists($file)) return ['attempts' => 0, 'locked_until' => 0];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : ['attempts' => 0, 'locked_until' => 0];
}

function saveLockout(string $ip, array $data): void {
    file_put_contents(lockoutFile($ip), json_encode($data), LOCK_EX);
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$erro    = '';
$ip      = $_SERVER['REMOTE_ADDR'];
$lockout = getLockout($ip);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $submittedToken)) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        $erro = 'Requisição inválida. Recarregue a página.';

    } elseif ($lockout['locked_until'] > time()) {
        $wait = ceil(($lockout['locked_until'] - time()) / 60);
        $erro = "Acesso bloqueado. Tente novamente em {$wait} minuto(s).";

    } else {
        $inputLogin    = trim($_POST['login']    ?? '');
        $inputPassword =      $_POST['password'] ?? '';

        $adminUser = null;
        if (!empty($inputLogin)) {
            // Busca usuário com statusConta = 3 (admin)
            $stmt = $conn->prepare(
                "SELECT u.id_usuario, u.usuario_login, u.usuario_password
                 FROM usuario u
                 INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario
                 WHERE u.usuario_login = ? AND uv.statusConta = 3
                 LIMIT 1"
            );
            $stmt->bind_param("s", $inputLogin);
            $stmt->execute();
            $adminUser = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if ($adminUser && password_verify($inputPassword, $adminUser['usuario_password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in']     = true;
            $_SESSION['admin_last_activity'] = time();
            $_SESSION['admin_user_login']    = $adminUser['usuario_login'];
            $_SESSION['admin_user_id']       = $adminUser['id_usuario'];
            $_SESSION['admin_csrf']          = bin2hex(random_bytes(32));
            saveLockout($ip, ['attempts' => 0, 'locked_until' => 0]);
            header('Location: admin_crud.php');
            exit;
        } else {
            $lockout['attempts']++;
            if ($lockout['attempts'] >= MAX_ATTEMPTS) {
                $lockout['locked_until'] = time() + LOCKOUT_DURATION;
                $erro = 'Muitas tentativas incorretas. Acesso bloqueado por 15 minutos.';
            } else {
                $remaining = MAX_ATTEMPTS - $lockout['attempts'];
                $erro = "Credenciais inválidas. {$remaining} tentativa(s) restante(s) antes do bloqueio.";
            }
            saveLockout($ip, $lockout);
        }
    }
}

$timeout = isset($_GET['timeout']);
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

<header class="barra-fixa">
    <nav>
        <button onclick="window.location.href='../index.php'">Início</button>
    </nav>
</header>

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
