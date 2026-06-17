<?php
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require       __DIR__ . '/../vendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método incorreto.']);
    exit;
}

$adminId = $_SESSION['admin_recovery_id'] ?? null;
if (!$adminId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão de recuperação inválida.']);
    exit;
}

function enviarOtpTelegram(string $token, string $chatId, string $otp, string $nome = ''): bool {
    $saudacao = $nome ? "Olá, {$nome}!\n\n" : '';
    $texto = "{$saudacao}Código de confirmação: <code>{$otp}</code>\n\nVálido por 5 minutos. Não compartilhe.";
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['chat_id' => $chatId, 'text' => $texto, 'parse_mode' => 'HTML']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return (json_decode($resp, true)['ok'] ?? false) === true;
}

$action = $_POST['action'] ?? '';

// ── Verifica se o Telegram foi vinculado e envia um OTP pra confirmar ─────────
if ($action === 'check_telegram') {
    $stmt = $conn->prepare("SELECT uv.telegram_id, u.nome_usuario FROM usuario_verificacao uv JOIN usuario u ON u.id_usuario = uv.fk_usuario WHERE uv.fk_usuario = ? LIMIT 1");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($row['telegram_id'])) {
        echo json_encode(['sucesso' => true, 'linked' => false]);
        exit;
    }

    // Telegram vinculado — manda um OTP pra confirmar que é realmente o dono
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $otp   = '';
    for ($i = 0; $i < 6; $i++) {
        $otp .= $chars[random_int(0, strlen($chars) - 1)];
    }

    if (!enviarOtpTelegram($TELEGRAM_BOT_TOKEN, db_decrypt($row['telegram_id'], $DB_ENCRYPT_KEY), $otp, db_decrypt($row['nome_usuario'] ?? '', $DB_ENCRYPT_KEY))) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao enviar código pelo Telegram. Tente novamente.']);
        exit;
    }

    $_SESSION['admin_recovery_tg_otp'] = [
        'hash'    => password_hash($otp, PASSWORD_BCRYPT),
        'expires' => time() + 300,
        'attempts' => 0,
    ];

    echo json_encode(['sucesso' => true, 'linked' => true]);
    exit;
}

// ── Verifica o OTP enviado pro Telegram ───────────────────────────────────────
if ($action === 'verify_telegram_otp') {
    $otpSessao = $_SESSION['admin_recovery_tg_otp'] ?? null;
    $inputOtp  = strtoupper(trim($_POST['otp'] ?? ''));

    if (!$otpSessao) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código expirado. Clique em verificar novamente.', 'expirado' => true]);
        exit;
    }
    if (time() > $otpSessao['expires']) {
        unset($_SESSION['admin_recovery_tg_otp']);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código expirado. Clique em verificar novamente.', 'expirado' => true]);
        exit;
    }
    if ($otpSessao['attempts'] >= 3) {
        unset($_SESSION['admin_recovery_tg_otp']);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Muitas tentativas. Clique em verificar novamente.', 'expirado' => true]);
        exit;
    }

    if (password_verify($inputOtp, $otpSessao['hash'])) {
        unset($_SESSION['admin_recovery_tg_otp']);
        $_SESSION['admin_recovery_tg_verified'] = true;
        echo json_encode(['sucesso' => true]);
    } else {
        $_SESSION['admin_recovery_tg_otp']['attempts']++;
        $restantes = 3 - $_SESSION['admin_recovery_tg_otp']['attempts'];
        echo json_encode(['sucesso' => false, 'mensagem' => "Código incorreto. {$restantes} tentativa(s) restante(s)."]);
    }
    exit;
}

// ── Gera o QR code pra configurar o 2FA ──────────────────────────────────────
if ($action === 'generate_qr') {
    if (empty($_SESSION['admin_recovery_tg_verified'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Confirme o Telegram primeiro.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT u.email FROM usuario u WHERE u.id_usuario = ? LIMIT 1");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }

    $google2fa = new Google2FA();
    $secret    = $google2fa->generateSecretKey();
    $_SESSION['admin_recovery_2fa_secret'] = $secret;

    $qrUrl = $google2fa->getQRCodeUrl('ONGs Browser', db_decrypt($user['email'], $DB_ENCRYPT_KEY), $secret);

    $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
    $writer   = new Writer($renderer);
    $qrSvg    = $writer->writeString($qrUrl);

    echo json_encode(['sucesso' => true, 'qr_svg' => $qrSvg, 'secret' => $secret]);
    exit;
}

// ── Confirma o código e salva o segredo do 2FA ───────────────────────────────
if ($action === 'confirm_2fa') {
    if (empty($_SESSION['admin_recovery_tg_verified'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Confirme o Telegram primeiro.']);
        exit;
    }

    $secret = $_SESSION['admin_recovery_2fa_secret'] ?? null;
    if (!$secret) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão de configuração expirada. Recarregue a página.']);
        exit;
    }

    $codigo = trim($_POST['codigo'] ?? '');
    if (strlen($codigo) !== 6 || !ctype_digit($codigo)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código inválido. Digite os 6 dígitos.']);
        exit;
    }

    $google2fa = new Google2FA();
    if (!$google2fa->verifyKey($secret, $codigo)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código incorreto. Certifique-se de que o QR foi escaneado corretamente.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuario_verificacao SET codVerificador = ? WHERE fk_usuario = ?");
    $stmt->bind_param("si", $secret, $adminId);
    $stmt->execute();
    $stmt->close();

    unset(
        $_SESSION['admin_recovery_2fa_secret'],
        $_SESSION['admin_recovery_id'],
        $_SESSION['admin_recovery_tg_pass'],
        $_SESSION['admin_recovery_tg_verified']
    );

    echo json_encode(['sucesso' => true]);
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
