<?php
ini_set('display_errors', '0');
include __DIR__ . '/../config.php';
require_once __DIR__ . '/check_banned_ip.php';
checkBannedIp($conn);

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método incorreto.']);
    exit;
}

define('OTP_EXPIRY',       300);
define('OTP_MAX_ATTEMPTS', 3);

function sendTelegramOtp(string $botToken, string $chatId, string $otp, string $nome = ''): bool {
    $saudacao = $nome ? "Olá, {$nome}!\n\n" : '';
    $text = "{$saudacao}Código de confirmação: <code>{$otp}</code>\n\nVálido por 5 minutos. Não compartilhe.";
    $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return ($data['ok'] ?? false) === true;
}

$action = $_POST['action'] ?? '';

// ── Solicita o OTP via Telegram ──────────────────────────────────────────────
if ($action === 'request') {
    $captchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (empty($captchaToken)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, confirme que você não é um robô.']);
        exit;
    }
    $res = json_decode(file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret={$CAPTCHA_SECRETA}&response={$captchaToken}"
    ), true);
    if (!($res['success'] ?? false)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha na verificação do reCAPTCHA. Tente novamente.']);
        exit;
    }

    $login = trim($_POST['usuario'] ?? '');
    if (empty($login)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Informe seu usuário.']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT u.id_usuario, u.usuario_login, u.nome_usuario, uv.statusConta, uv.telegram_id
         FROM usuario u
         INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario
         WHERE u.usuario_login = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && (int) $user['statusConta'] > 0 && !empty($user['telegram_id'])) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $otp   = '';
        for ($i = 0; $i < 6; $i++) {
            $otp .= $chars[random_int(0, strlen($chars) - 1)];
        }

        if (sendTelegramOtp($TELEGRAM_BOT_TOKEN, $user['telegram_id'], $otp, $user['nome_usuario'] ?? '')) {
            $_SESSION['user_otp'] = [
                'user_id'     => $user['id_usuario'],
                'login'       => $user['usuario_login'],
                'statusConta' => (int) $user['statusConta'],
                'hash'        => password_hash($otp, PASSWORD_BCRYPT),
                'expires'     => time() + OTP_EXPIRY,
                'attempts'    => 0,
            ];
            echo json_encode(['sucesso' => true, 'login' => $user['usuario_login']]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao enviar mensagem no Telegram. Tente novamente.']);
        }
    } else {
        // Resposta vaga pra não revelar se o usuário existe ou não
        echo json_encode(['sucesso' => true, 'login' => $login, 'avisoConta' => true]);
    }
    exit;
}

// ── Verifica o OTP digitado ──────────────────────────────────────────────────
if ($action === 'verify') {
    $inputOtp   = strtoupper(trim($_POST['otp'] ?? ''));
    $otpSession = $_SESSION['user_otp'] ?? null;

    if (!$otpSession) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Solicite um novo código.', 'expirado' => true]);
        exit;
    }

    if (time() > $otpSession['expires']) {
        unset($_SESSION['user_otp']);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código expirado. Solicite um novo.', 'expirado' => true]);
        exit;
    }

    if ($otpSession['attempts'] >= OTP_MAX_ATTEMPTS) {
        unset($_SESSION['user_otp']);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Muitas tentativas. Solicite um novo código.', 'expirado' => true]);
        exit;
    }

    if (password_verify($inputOtp, $otpSession['hash'])) {
        unset($_SESSION['user_otp']);

        // Vê se o usuário tem 2FA configurado
        $stmt2 = $conn->prepare("SELECT codVerificador FROM usuario_verificacao WHERE fk_usuario = ? LIMIT 1");
        $stmt2->bind_param("i", $otpSession['user_id']);
        $stmt2->execute();
        $uv = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if (!empty($uv['codVerificador'])) {
            $_SESSION['2fa_pending_id']    = $otpSession['user_id'];
            $_SESSION['2fa_pending_login'] = $otpSession['login'];
            $_SESSION['statusConta']       = $otpSession['statusConta'];
            echo json_encode(['sucesso' => true, 'acao' => '2fa_required']);
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario_id']    = $otpSession['user_id'];
            $_SESSION['usuario_login'] = $otpSession['login'];
            $_SESSION['statusConta']   = $otpSession['statusConta'];
            echo json_encode(['sucesso' => true]);
        }
    } else {
        $_SESSION['user_otp']['attempts']++;
        $remaining = OTP_MAX_ATTEMPTS - $_SESSION['user_otp']['attempts'];
        if ($remaining > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => "Código incorreto. {$remaining} tentativa(s) restante(s)."]);
        } else {
            unset($_SESSION['user_otp']);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Código incorreto. Solicite um novo código.', 'expirado' => true]);
        }
    }
    exit;
}

// ── Cancela o OTP e limpa a sessão ───────────────────────────────────────────
if ($action === 'cancel') {
    unset($_SESSION['user_otp']);
    echo json_encode(['sucesso' => true]);
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
