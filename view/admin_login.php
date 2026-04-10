<?php
// Configura cookies de sessão mais seguros antes de iniciar
// (mesmas flags do admin_guard.php — precisam estar aqui também pois essa página inicia a sessão)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Já autenticado — redireciona direto pro painel sem mostrar o formulário
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_crud.php');
    exit;
}

// ── Configurações ──────────────────────────────────────────────────────────
define('MAX_ATTEMPTS',      5);
define('LOCKOUT_DURATION',  900); // 15 minutos em segundos

// Reutiliza o config.php que já carrega o .env e cria a conexão com o banco
require_once __DIR__ . '/../config.php';
$storedHash = $ADMIN_PASSWORD_HASH;

// ── Controle de bloqueio por IP ────────────────────────────────────────────
// O bloqueio é salvo em arquivo no servidor, não na sessão.
// Isso impede que o atacante burle o limite simplesmente deletando o cookie.

// Caminho do arquivo de bloqueio pra esse IP (identificado pelo md5 do endereço)
function lockoutFile(string $ip): string {
    return sys_get_temp_dir() . '/ongs_admin_' . md5($ip) . '.json';
}

// Carrega os dados de bloqueio do IP: quantas tentativas e até quando está bloqueado
function getLockout(string $ip): array {
    $file = lockoutFile($ip);
    if (!file_exists($file)) return ['attempts' => 0, 'locked_until' => 0];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : ['attempts' => 0, 'locked_until' => 0];
}

// Salva o estado de bloqueio atualizado no arquivo (LOCK_EX evita escrita simultânea)
function saveLockout(string $ip, array $data): void {
    file_put_contents(lockoutFile($ip), json_encode($data), LOCK_EX);
}

// ── Token CSRF ─────────────────────────────────────────────────────────────
// Gera um token aleatório por sessão. Ele é enviado no formulário e conferido no POST.
// Isso garante que só o próprio formulário desta página pode fazer login — não um site externo.
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

// ── Processar login ────────────────────────────────────────────────────────
$erro    = '';
$ip      = $_SERVER['REMOTE_ADDR'];
$lockout = getLockout($ip);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verifica o token CSRF — se não bater, a requisição não veio do nosso formulário
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $submittedToken)) {
        // Regenera o token pra que um reenvio da mesma requisição também falhe
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        $erro = 'Requisição inválida. Recarregue a página.';

    // 2. Verifica se o IP ainda está no período de bloqueio
    } elseif ($lockout['locked_until'] > time()) {
        $wait = ceil(($lockout['locked_until'] - time()) / 60);
        $erro = "Acesso bloqueado. Tente novamente em {$wait} minuto(s).";

    // 3. Verifica se a senha foi configurada no .env (evita acesso com hash padrão)
    } elseif (empty($storedHash) || $storedHash === 'CHANGE_ME_RUN_GENERATE_HASH_PHP') {
        $erro = 'Senha de administrador não configurada. Acesso negado.';

    // 4. Verifica a senha com bcrypt — password_verify leva o mesmo tempo pra acertos e erros
    } else {
        $inputPassword = $_POST['password'] ?? '';

        if (password_verify($inputPassword, $storedHash)) {
            // ── Sucesso ────────────────────────────────────────────────────
            // Gera um novo ID de sessão pra evitar fixação de sessão
            session_regenerate_id(true);

            $_SESSION['admin_logged_in']     = true;
            $_SESSION['admin_last_activity'] = time();

            // Gera um novo token CSRF pra sessão autenticada
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));

            // Limpa o contador de tentativas desse IP
            saveLockout($ip, ['attempts' => 0, 'locked_until' => 0]);

            header('Location: admin_crud.php');
            exit;

        } else {
            // ── Tentativa inválida ─────────────────────────────────────────
            $lockout['attempts']++;

            // Após 5 tentativas erradas, bloqueia o IP por 15 minutos
            if ($lockout['attempts'] >= MAX_ATTEMPTS) {
                $lockout['locked_until'] = time() + LOCKOUT_DURATION;
                $erro = 'Muitas tentativas incorretas. Acesso bloqueado por 15 minutos.';
            } else {
                $remaining = MAX_ATTEMPTS - $lockout['attempts'];
                $erro = "Senha incorreta. {$remaining} tentativa(s) restante(s) antes do bloqueio.";
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

        <label for="password">Senha de Administrador:</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Digite a senha"
            autocomplete="current-password"
            required
            autofocus
        >

        <?php if ($erro): ?>
            <p class="erro-login"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <button type="submit">Entrar</button>
    </form>
</main>

</body>
</html>
