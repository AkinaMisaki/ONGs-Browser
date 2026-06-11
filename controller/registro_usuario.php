<?php
$meurastro = [];

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../config.php';
require_once __DIR__ . '/check_banned_ip.php';
checkBannedIp($conn);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
    exit;
}

// ─── Etapa 1: Ler payload JSON ────────────────────────────────────────────────
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    echo json_encode(["sucesso" => false, "mensagem" => "Payload inválido ou vazio."]);
    exit;
}

foreach (['msg_cifrada', 'keyAESCifrada', 'iv', 'captchaToken'] as $campo) {
    if (empty($payload[$campo])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Campo obrigatório ausente: $campo"]);
        exit;
    }
}

// ─── Etapa 2: Verificar reCAPTCHA ────────────────────────────────────────────
$captchaToken    = $payload['captchaToken'];
$respostaGoogle  = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret={$CAPTCHA_SECRETA}&response={$captchaToken}"
);
$resultadoCaptcha = json_decode($respostaGoogle, true);
if (!$resultadoCaptcha || !$resultadoCaptcha['success']) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha na verificação do reCAPTCHA. Tente novamente."]);
    exit;
}

// ─── Etapa 3: Descriptografar payload híbrido (RSA + AES-GCM) ────────────────
$msgCifrada = base64_decode($payload['msg_cifrada'],   true);
$keyCifrada = base64_decode($payload['keyAESCifrada'], true);
$iv         = base64_decode($payload['iv'],            true);

if ($msgCifrada === false || $keyCifrada === false || $iv === false) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao decodificar base64."]);
    exit;
}

$pvkeyPath = '/var/www/config/private.pem';
if (!file_exists($pvkeyPath)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Chave privada não encontrada."]);
    exit;
}

$pvkey = openssl_pkey_get_private(file_get_contents($pvkeyPath));
if (!$pvkey) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha ao carregar chave privada RSA."]);
    exit;
}

$chaveAES = '';
if (!openssl_private_decrypt($keyCifrada, $chaveAES, $pvkey, OPENSSL_PKCS1_OAEP_PADDING)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha ao decifrar chave AES com RSA."]);
    exit;
}

$tag    = substr($msgCifrada, -16);
$cipher = substr($msgCifrada,  0, -16);
$json   = openssl_decrypt($cipher, 'aes-256-gcm', $chaveAES, OPENSSL_RAW_DATA, $iv, $tag);

if ($json === false) {
    echo json_encode(["sucesso" => false, "mensagem" => "Falha ao decifrar mensagem com AES-GCM."]);
    exit;
}

$form = json_decode($json, true);
if (!$form || !isset($form['campoNome'], $form['campoEmail'], $form['campoUsuario'], $form['campoSenha'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Dados do formulário mal formatados após decifração."]);
    exit;
}

// ─── Etapa 4: Sanitizar e validar ────────────────────────────────────────────
$rawNome    = trim($form['campoNome']);
$rawEmail   = trim($form['campoEmail']);
$rawUsuario = trim($form['campoUsuario']);
$rawSenha   = $form['campoSenha'];

if (empty($rawNome) || empty($rawEmail) || empty($rawUsuario) || empty($rawSenha)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Dados inválidos. Preencha todos os campos corretamente."]);
    exit;
}

if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Endereço de e-mail inválido."]);
    exit;
}

$padraoSenha = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
if (!preg_match($padraoSenha, $rawSenha)) {
    echo json_encode(["sucesso" => false, "mensagem" => "A senha deve ter no mínimo 8 caracteres, letra maiúscula, minúscula, número e caractere especial."]);
    exit;
}

$options = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
$senhaCriptografada = password_hash($rawSenha, PASSWORD_ARGON2ID, $options);

$statusConta      = 0;
$activationToken  = bin2hex(random_bytes(32));
$activationExpire = date("Y-m-d H:i:s", strtotime("+24 hours"));

// ─── Etapa 5: Verificar unicidade (comparação antes da cifragem) ──────────────
// Nota: email_hash (HMAC-SHA256) é usado como blind index para pesquisa eficiente.
$emailHash = hash_hmac('sha256', strtolower($rawEmail), 'ongs-browser-email-index');

$stmt = $conn->prepare("SELECT email_hash, usuario_login FROM usuario WHERE email_hash = ? OR usuario_login = ? LIMIT 1");
$stmt->bind_param("ss", $emailHash, $rawUsuario);
$stmt->execute();
$stmt->bind_result($hashEncontrado, $loginEncontrado);

if ($stmt->fetch()) {
    $emailEmUso = $hashEncontrado === $emailHash;
    $loginEmUso = $loginEncontrado === $rawUsuario;

    if ($emailEmUso && $loginEmUso) {
        $mensagem = "E-mail e usuário já cadastrados.";
    } elseif ($emailEmUso) {
        $mensagem = "E-mail já cadastrado.";
    } else {
        $mensagem = "Nome de usuário já cadastrado.";
    }

    $stmt->close();
    $conn->close();
    echo json_encode(["sucesso" => false, "mensagem" => $mensagem]);
    exit;
}
$stmt->close();

// ─── Etapa 6: Cifrar dados sensíveis com chave AES-256 do BD (S.3.2.c) ───────
if ($DB_ENCRYPT_KEY === null) {
    echo json_encode(["sucesso" => false, "mensagem" => "Chave de criptografia do BD não configurada. Execute generate_db_key.php."]);
    exit;
}

$nomeCifrado  = aes_encrypt($rawNome,  $DB_ENCRYPT_KEY);
$emailCifrado = aes_encrypt($rawEmail, $DB_ENCRYPT_KEY);

// ─── Etapa 7: Persistir no BD ────────────────────────────────────────────────
try {
    $sql  = "INSERT INTO usuario (nome_usuario, email, email_hash, usuario_login, usuario_password)
             VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nomeCifrado, $emailCifrado, $emailHash, $rawUsuario, $senhaCriptografada);
    $stmt->execute();
    $novoId = $conn->insert_id;
    $stmt->close();

    $sql  = "INSERT INTO usuario_verificacao (fk_usuario, statusConta, reset_token, reset_expire)
             VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $novoId, $statusConta, $activationToken, $activationExpire);
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        echo json_encode(["sucesso" => false, "mensagem" => "Usuário ou e-mail já estão cadastrados."]);
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro interno. Tente novamente."]);
    }
    $conn->close();
    exit;
}

$conn->close();

// ─── Etapa 8: Enviar e-mail de ativação ──────────────────────────────────────
$scheme         = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$_proj          = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
$activationLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_proj . '/view/ativar_conta.php?token=' . urlencode($activationToken);
$nomeSeguro     = htmlspecialchars($rawNome, ENT_QUOTES, 'UTF-8');

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug  = 0;
    $mail->isSMTP();
    $mail->CharSet    = 'UTF-8';
    $mail->Host       = 'serv.hanafuda.moe';
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Username   = 'noreply@hanafuda.moe';
    $mail->Password   = $SMTP_PASSWORD;
    $mail->setFrom('noreply@hanafuda.moe', 'ONGs Browser');
    $mail->addAddress($rawEmail, $nomeSeguro);
    $mail->isHTML(true);
    $mail->Subject = 'Ative sua conta — ONGs Browser';
    $mail->Body = "
    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto;
                 border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
        <div style='background-color: #0056b3; padding: 20px 30px;'>
            <h1 style='color: #fff; margin: 0; font-size: 1.4rem;'>ONGs Browser</h1>
        </div>
        <div style='padding: 30px;'>
            <h2 style='color: #0056b3;'>Bem-vindo(a), {$nomeSeguro}!</h2>
            <p>Obrigado por criar sua conta no <strong>ONGs Browser</strong>. Para ativá-la,
               clique no botão abaixo. O link é válido por <strong>24 horas</strong>.</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$activationLink}'
                   style='background-color: #28a745; color: #fff; padding: 14px 28px;
                          text-decoration: none; border-radius: 5px; font-weight: bold;
                          font-size: 1rem; display: inline-block;'>
                    Ativar Minha Conta
                </a>
            </div>
            <p style='font-size: 0.9rem; color: #555;'>
                Se o botão não funcionar, copie e cole este endereço no seu navegador:<br>
                <a href='{$activationLink}' style='color: #0056b3; word-break: break-all;'>{$activationLink}</a>
            </p>
            <p style='font-size: 0.9rem; color: #555;'>
                Se você não realizou este cadastro, ignore este e-mail.
            </p>
        </div>
        <div style='background-color: #f4f7fc; border-top: 1px solid #ddd;
                    padding: 20px 30px; font-size: 0.8rem; color: #666; line-height: 1.6;'>
            <strong>Informações sobre Privacidade e Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong><br><br>
            <strong>Controlador dos dados:</strong> ONGs Browser (projeto acadêmico).<br>
            <strong>Dados coletados:</strong> nome, endereço de e-mail e nome de usuário.<br>
            <strong>Finalidade:</strong> criação e gestão da sua conta de acesso à plataforma.<br>
            <strong>Base legal:</strong> consentimento do titular (Art. 7º, I, LGPD).<br>
            <strong>Retenção:</strong> seus dados são mantidos enquanto a conta estiver ativa.<br><br>
            Este é um e-mail automático. Por favor, não responda diretamente a esta mensagem.
        </div>
    </div>";

    $mail->AltBody = "Bem-vindo(a), {$nomeSeguro}!\n\n"
        . "Acesse o link abaixo para ativar sua conta (válido por 24 horas):\n"
        . "{$activationLink}\n\n"
        . "Se não realizou este cadastro, ignore este e-mail.\n\n"
        . "Controlador: ONGs Browser. Contato: noreply@hanafuda.moe";

    $mail->send();

    echo json_encode([
        "sucesso"  => true,
        "mensagem" => "Cadastro realizado! Verifique seu e-mail para ativar a conta."
    ]);

} catch (Exception $e) {
    echo json_encode([
        "sucesso"  => false,
        "mensagem" => "Cadastro registrado, mas não foi possível enviar o e-mail de ativação. Tente novamente."
    ]);
}
?>
