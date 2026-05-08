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

$rawNome    = (isset($_POST['nome'])    && is_string($_POST['nome']))    ? trim($_POST['nome'])    : '';
$rawEmail   = (isset($_POST['email'])   && is_string($_POST['email']))   ? trim($_POST['email'])   : '';
$rawUsuario = (isset($_POST['usuario']) && is_string($_POST['usuario'])) ? trim($_POST['usuario']) : '';
$rawSenha   = (isset($_POST['senha'])   && is_string($_POST['senha']))   ? trim($_POST['senha'])   : '';

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

// Conta inicia inativa — ativação via e-mail (LGPD: consentimento explícito, Art. 7, I)
$statusConta       = 0;
$activationToken   = bin2hex(random_bytes(32));
$activationExpire  = date("Y-m-d H:i:s", strtotime("+24 hours"));

try {
    $sql = "INSERT INTO usuario (nome_usuario, email, usuario_login, usuario_password)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $rawNome, $rawEmail, $rawUsuario, $senhaCriptografada);
    $stmt->execute();
    $novoId = $conn->insert_id;
    $stmt->close();

    $sql = "INSERT INTO usuario_verificacao (fk_usuario, statusConta, reset_token, reset_expire)
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

$activationLink = "https://hanafuda.moe/universidade/view/ativar_conta.php?token=" . urlencode($activationToken);
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

        <!-- Cabeçalho -->
        <div style='background-color: #0056b3; padding: 20px 30px;'>
            <h1 style='color: #fff; margin: 0; font-size: 1.4rem;'>ONGs Browser</h1>
        </div>

        <!-- Corpo -->
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
                Se você não realizou este cadastro, ignore este e-mail. Nenhuma ação é necessária
                e seus dados não serão mantidos após a expiração do link.
            </p>
        </div>

        <!-- Aviso LGPD -->
        <div style='background-color: #f4f7fc; border-top: 1px solid #ddd;
                    padding: 20px 30px; font-size: 0.8rem; color: #666; line-height: 1.6;'>
            <strong>Informações sobre Privacidade e Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong><br><br>

            <strong>Controlador dos dados:</strong> ONGs Browser (projeto acadêmico).<br>
            <strong>Dados coletados:</strong> nome, endereço de e-mail e nome de usuário, fornecidos
            voluntariamente no momento do cadastro.<br>
            <strong>Finalidade:</strong> criação e gestão da sua conta de acesso à plataforma.<br>
            <strong>Base legal:</strong> consentimento do titular (Art. 7º, I, LGPD).<br>
            <strong>Retenção:</strong> seus dados são mantidos enquanto a conta estiver ativa.
            Contas não ativadas em 24 horas são descartadas.<br><br>

            <strong>Seus direitos como titular (Art. 18, LGPD):</strong><br>
            Você pode, a qualquer momento: confirmar a existência de tratamento; acessar, corrigir
            ou excluir seus dados; solicitar portabilidade; revogar o consentimento; ou opor-se
            ao tratamento. Para exercer esses direitos, acesse a seção
            <em>Gerenciar Conta</em> após o login ou entre em contato pelo e-mail
            <a href='mailto:noreply@hanafuda.moe' style='color: #0056b3;'>noreply@hanafuda.moe</a>.<br><br>

            Este é um e-mail automático. Por favor, não responda diretamente a esta mensagem.
        </div>
    </div>";

    $mail->AltBody = "Bem-vindo(a), {$nomeSeguro}!\n\n"
        . "Acesse o link abaixo para ativar sua conta (válido por 24 horas):\n"
        . "{$activationLink}\n\n"
        . "Se não realizou este cadastro, ignore este e-mail.\n\n"
        . "--- Aviso LGPD ---\n"
        . "Controlador: ONGs Browser. Dados coletados: nome, e-mail e usuário.\n"
        . "Finalidade: criação de conta. Base legal: consentimento (Art. 7, I, LGPD).\n"
        . "Direitos (Art. 18): acesso, correção, exclusão e portabilidade via Gerenciar Conta.\n"
        . "Contato: noreply@hanafuda.moe";

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
