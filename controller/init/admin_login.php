<?php
define('MAX_ATTEMPTS',     5);
define('LOCKOUT_DURATION', 900);
define('OTP_EXPIRY',       300);
define('OTP_MAX_ATTEMPTS', 3);

const PERSONAL_QUESTIONS = [
    1  => 'Qual o nome do seu primeiro animal de estimação?',
    2  => 'Qual é o nome de solteira de sua mãe?',
    3  => 'Em que cidade você nasceu?',
    4  => 'Qual era o seu apelido na infância?',
    5  => 'Qual foi o nome da sua primeira escola?',
    6  => 'Qual é o nome do seu melhor amigo de infância?',
    7  => 'Qual foi o modelo do seu primeiro carro?',
    8  => 'Qual é o nome da rua em que você cresceu?',
    9  => 'Qual é o segundo nome da sua mãe?',
    10 => 'Qual foi o nome do seu professor favorito?',
];

require_once __DIR__ . '/../../config.php';
require       __DIR__ . '/../../../vendor/autoload.php';
use PragmaRX\Google2FA\Google2FA;

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

function sendTelegramOtp(string $botToken, string $chatId, string $otp, string $nome = ''): bool {
    $saudacao = $nome ? "Olá, {$nome}!\n\n" : '';
    $text = "{$saudacao}Código de confirmação: <code>{$otp}</code>\n\nVálido por 5 minutos. Não compartilhe.";
    $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return ($data['ok'] ?? false) === true;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

// Cancelamentos via GET — limpa a sessão e volta pro início
if (isset($_GET['cancel_otp'])) {
    unset($_SESSION['admin_otp'], $_SESSION['admin_totp_pending']);
    header('Location: admin_login.php');
    exit;
}
if (isset($_GET['cancel_recovery'])) {
    unset($_SESSION['admin_recovery']);
    header('Location: admin_login.php');
    exit;
}

$erro    = '';
$sucesso = '';
$ip      = $_SERVER['REMOTE_ADDR'];
$lockout = getLockout($ip);

// Define qual tela mostrar com base na sessão ou parâmetro GET
$viewMode         = 'otp_request';
$recoveryQuestion = '';

if (!empty($_SESSION['admin_totp_pending'])) {
    $viewMode = 'totp_verify';
} elseif (!empty($_SESSION['admin_otp'])) {
    $viewMode = 'otp_verify';
} elseif (!empty($_SESSION['admin_recovery'])) {
    $viewMode         = 'recovery_question';
    $recoveryQuestion = $_SESSION['admin_recovery']['question'] ?? '';
} elseif (isset($_GET['modo']) && $_GET['modo'] === 'recovery') {
    $viewMode = 'recovery_start';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $submittedToken)) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        $erro = 'Requisição inválida. Recarregue a página.';

    } elseif ($lockout['locked_until'] > time()) {
        $wait = ceil(($lockout['locked_until'] - time()) / 60);
        $erro = "Acesso bloqueado. Tente novamente em {$wait} minuto(s).";

    } else {
        $action = $_POST['action'] ?? 'request_otp';

        // Captcha só nos formulários de entrada, não nos passos intermediários
        $needsCaptcha = in_array($action, ['request_otp', 'recovery_start'], true);
        if ($needsCaptcha) {
            $captchaToken = $_POST['g-recaptcha-response'] ?? '';
            if (empty($captchaToken)) {
                $erro = 'Por favor, confirme que você não é um robô.';
            } else {
                $captchaRes = json_decode(file_get_contents(
                    "https://www.google.com/recaptcha/api/siteverify?secret={$CAPTCHA_SECRETA}&response={$captchaToken}"
                ), true);
                if (!($captchaRes['success'] ?? false)) {
                    $erro = 'Falha na verificação do reCAPTCHA. Tente novamente.';
                }
            }
        }

        if (!$erro) {

        // ── Solicita o OTP via Telegram ──────────────────────────────────────
        if ($action === 'request_otp') {
            $inputLogin = trim($_POST['login'] ?? '');
            $viewMode   = 'otp_request';

            if (!empty($inputLogin)) {
                $stmt = $conn->prepare(
                    "SELECT u.id_usuario, u.usuario_login, u.nome_usuario, uv.telegram_id, uv.codVerificador
                     FROM usuario u
                     INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario
                     WHERE u.usuario_login = ? AND uv.statusConta = 3
                     LIMIT 1"
                );
                $stmt->bind_param("s", $inputLogin);
                $stmt->execute();
                $adminUser = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $adminUser = null;
            }

            if ($adminUser && empty($adminUser['telegram_id'])) {
                $erro = 'Esta conta não tem Telegram vinculado. Configure-o em Gerenciar Conta antes de usar o painel admin.';
            } elseif ($adminUser && empty($adminUser['codVerificador'])) {
                $erro = 'Esta conta não tem autenticador (2FA) configurado. Configure-o em Gerenciar Conta antes de usar o painel admin.';
            } elseif ($adminUser) {
                $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                $otp   = '';
                for ($i = 0; $i < 6; $i++) {
                    $otp .= $chars[random_int(0, strlen($chars) - 1)];
                }

                if (sendTelegramOtp($TELEGRAM_BOT_TOKEN, $adminUser['telegram_id'], $otp, db_decrypt($adminUser['nome_usuario'] ?? '', $DB_ENCRYPT_KEY))) {
                    $_SESSION['admin_otp'] = [
                        'user_id'  => $adminUser['id_usuario'],
                        'login'    => $adminUser['usuario_login'],
                        'hash'     => password_hash($otp, PASSWORD_BCRYPT),
                        'expires'  => time() + OTP_EXPIRY,
                        'attempts' => 0,
                    ];
                    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
                    $viewMode = 'otp_verify';
                    $sucesso  = 'Código enviado! Verifique seu Telegram.';
                } else {
                    $erro = 'Falha ao enviar mensagem no Telegram. Tente novamente.';
                }
            } else {
                // Conta não encontrada — resposta vaga pra não revelar existência
                $sucesso = 'Se esta conta existir e tiver Telegram vinculado, um código foi enviado.';
            }

        // ── Verifica o OTP do Telegram ───────────────────────────────────────
        } elseif ($action === 'verify_otp') {
            $otpSession = $_SESSION['admin_otp'] ?? null;
            $inputOtp   = strtoupper(trim($_POST['otp'] ?? ''));
            $viewMode   = 'otp_verify';

            if (!$otpSession) {
                $erro = 'Sessão expirada. Solicite um novo código.';
                $viewMode = 'otp_request';
            } elseif (time() > $otpSession['expires']) {
                unset($_SESSION['admin_otp']);
                $erro = 'Código expirado. Solicite um novo.';
                $viewMode = 'otp_request';
            } elseif ($otpSession['attempts'] >= OTP_MAX_ATTEMPTS) {
                unset($_SESSION['admin_otp']);
                $erro = 'Muitas tentativas. Solicite um novo código.';
                $viewMode = 'otp_request';
            } elseif (password_verify($inputOtp, $otpSession['hash'])) {
                unset($_SESSION['admin_otp']);

                // Vê se o admin tem 2FA configurado
                $stmt = $conn->prepare("SELECT codVerificador FROM usuario_verificacao WHERE fk_usuario = ? LIMIT 1");
                $stmt->bind_param("i", $otpSession['user_id']);
                $stmt->execute();
                $uv = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!empty($uv['codVerificador'])) {
                    $_SESSION['admin_totp_pending'] = [
                        'user_id' => $otpSession['user_id'],
                        'login'   => $otpSession['login'],
                    ];
                    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
                    $viewMode = 'totp_verify';
                    $sucesso  = 'Telegram verificado. Digite o código do autenticador.';
                } else {
                    $erro     = '2FA não configurado. Configure o autenticador em Gerenciar Conta antes de usar o painel admin.';
                    $viewMode = 'otp_request';
                }
            } else {
                $_SESSION['admin_otp']['attempts']++;
                $remaining = OTP_MAX_ATTEMPTS - $_SESSION['admin_otp']['attempts'];
                if ($remaining > 0) {
                    $erro = "Código incorreto. {$remaining} tentativa(s) restante(s).";
                } else {
                    unset($_SESSION['admin_otp']);
                    $erro = 'Código incorreto. Solicite um novo código.';
                    $viewMode = 'otp_request';
                }
            }

        // ── Verifica o código do Google Authenticator ────────────────────────
        } elseif ($action === 'verify_totp') {
            $pending  = $_SESSION['admin_totp_pending'] ?? null;
            $viewMode = 'totp_verify';

            if (!$pending) {
                $erro = 'Sessão expirada. Faça login novamente.';
                $viewMode = 'otp_request';
            } else {
                $codigo = trim($_POST['codigo'] ?? '');
                if (strlen($codigo) !== 6 || !ctype_digit($codigo)) {
                    $erro = 'Código inválido. Digite os 6 dígitos.';
                } else {
                    $stmt = $conn->prepare("SELECT codVerificador FROM usuario_verificacao WHERE fk_usuario = ? LIMIT 1");
                    $stmt->bind_param("i", $pending['user_id']);
                    $stmt->execute();
                    $uv = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $google2fa = new Google2FA();
                    if ($uv && $google2fa->verifyKey($uv['codVerificador'], $codigo)) {
                        unset($_SESSION['admin_totp_pending']);
                        session_regenerate_id(true);
                        $_SESSION['admin_logged_in']     = true;
                        $_SESSION['admin_last_activity'] = time();
                        $_SESSION['admin_user_login']    = $pending['login'];
                        $_SESSION['admin_user_id']       = $pending['user_id'];
                        $_SESSION['admin_csrf']          = bin2hex(random_bytes(32));
                        saveLockout($ip, ['attempts' => 0, 'locked_until' => 0]);
                        header('Location: admin_crud.php');
                        exit;
                    } else {
                        $erro = 'Código do autenticador incorreto. Tente novamente.';
                    }
                }
            }

        // ── Recuperação: busca a pergunta de segurança do admin ──────────────
        } elseif ($action === 'recovery_start') {
            $inputLogin = trim($_POST['login'] ?? '');
            $viewMode   = 'recovery_start';

            if (!empty($inputLogin)) {
                $stmt = $conn->prepare(
                    "SELECT u.id_usuario, u.usuario_login, uv.personal_id, uv.personal_answer
                     FROM usuario u
                     INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario
                     WHERE u.usuario_login = ? AND uv.statusConta = 3
                     LIMIT 1"
                );
                $stmt->bind_param("s", $inputLogin);
                $stmt->execute();
                $adminUser = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $adminUser = null;
            }

            if (
                $adminUser &&
                !empty($adminUser['personal_id']) &&
                !empty($adminUser['personal_answer']) &&
                isset(PERSONAL_QUESTIONS[(int) $adminUser['personal_id']])
            ) {
                $qText = PERSONAL_QUESTIONS[(int) $adminUser['personal_id']];
                $_SESSION['admin_recovery'] = [
                    'user_id'  => $adminUser['id_usuario'],
                    'login'    => $adminUser['usuario_login'],
                    'hash'     => $adminUser['personal_answer'],
                    'question' => $qText,
                    'attempts' => 0,
                ];
                $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
                $viewMode         = 'recovery_question';
                $recoveryQuestion = $qText;
            } else {
                $erro = 'Pergunta de segurança não configurada para esta conta. Faça login pelo formulário de usuário normal, acesse Gerenciar Conta e configure sua pergunta de recuperação antes de usar esta opção.';
            }

        // ── Recuperação: verifica a resposta da pergunta de segurança ────────
        } elseif ($action === 'verify_answer') {
            $recovery = $_SESSION['admin_recovery'] ?? null;
            $viewMode = 'recovery_question';

            if (!$recovery) {
                $erro = 'Sessão expirada. Tente novamente.';
                $viewMode = 'recovery_start';
            } elseif (($recovery['attempts'] ?? 0) >= 5) {
                unset($_SESSION['admin_recovery']);
                $erro = 'Muitas tentativas incorretas. Tente mais tarde.';
                $viewMode = 'otp_request';
            } else {
                $recoveryQuestion = $recovery['question'];
                $answer = strtolower(trim($_POST['answer'] ?? ''));

                if (password_verify($answer, $recovery['hash'])) {
                    // Limpa as credenciais imediatamente — do zero
                    $stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_id = NULL, codVerificador = NULL, telegram_pass = NULL WHERE fk_usuario = ?");
                    $stmt->bind_param("i", $recovery['user_id']);
                    $stmt->execute();
                    $stmt->close();

                    $_SESSION['admin_recovery_id'] = $recovery['user_id'];
                    unset($_SESSION['admin_recovery'], $_SESSION['admin_recovery_tg_pass'], $_SESSION['admin_recovery_tg_verified'], $_SESSION['admin_recovery_tg_otp']);
                    header('Location: admin_recuperar.php');
                    exit;
                } else {
                    $_SESSION['admin_recovery']['attempts']++;
                    $remaining = 5 - $_SESSION['admin_recovery']['attempts'];
                    if ($remaining > 0) {
                        $erro = "Resposta incorreta. {$remaining} tentativa(s) restante(s).";
                    } else {
                        unset($_SESSION['admin_recovery']);
                        $erro = 'Muitas tentativas incorretas. Tente mais tarde.';
                        $viewMode = 'otp_request';
                    }
                }
            }
        }
        } // fim do bloco de ações
    }
}

$timeout = isset($_GET['timeout']);
