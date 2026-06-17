<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

$token = trim($_POST['token'] ?? '');

if (empty($token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token de ativação ausente.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT fk_usuario AS id_usuario, reset_expire, statusConta FROM usuario_verificacao WHERE reset_token = ? LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'link_invalido']);
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Conta já ativa
if ((int) $usuario['statusConta'] === 1) {
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'ja_ativa']);
    exit;
}

// Link expirado — gera novo token e reenvia o e-mail
if (strtotime($usuario['reset_expire']) < time()) {
    $novoToken  = bin2hex(random_bytes(32));
    $novoExpire = date("Y-m-d H:i:s", strtotime("+24 hours"));

    $stmt = $conn->prepare(
        "UPDATE usuario_verificacao SET reset_token = ?, reset_expire = ? WHERE fk_usuario = ?"
    );
    $stmt->bind_param("ssi", $novoToken, $novoExpire, $usuario['id_usuario']);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT nome_usuario, email FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $usuario['id_usuario']);
    $stmt->execute();
    $dadosUsuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    $activationLink = "https://hanafuda.moe/view/ativar_conta.php?token=" . urlencode($novoToken);
    $nomeSeguro     = htmlspecialchars(db_decrypt($dadosUsuario['nome_usuario'], $DB_ENCRYPT_KEY), ENT_QUOTES, 'UTF-8');

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
        $mail->addAddress(db_decrypt($dadosUsuario['email'], $DB_ENCRYPT_KEY), $nomeSeguro);
        $mail->isHTML(true);
        $mail->Subject = 'Novo link de ativação — ONGs Browser';
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto;
                     border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #0056b3; padding: 20px 30px;'>
                <h1 style='color: #fff; margin: 0; font-size: 1.4rem;'>ONGs Browser</h1>
            </div>
            <div style='padding: 30px;'>
                <h2 style='color: #0056b3;'>Novo link de ativação</h2>
                <p>Olá, {$nomeSeguro}! Seu link anterior expirou, então geramos um novo para você.
                   Clique no botão abaixo para ativar sua conta. O link é válido por <strong>24 horas</strong>.</p>
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
            </div>
        </div>";
        $mail->AltBody = "Olá, {$nomeSeguro}!\n\nSeu link de ativação expirou. Acesse o novo link abaixo (válido por 24 horas):\n{$activationLink}";
        $mail->send();
    } catch (Exception $e) {
        // Falha no envio não bloqueia a resposta
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'link_reenviado']);
    exit;
}

// Ativa a conta e limpa o token
$stmt = $conn->prepare(
    "UPDATE usuario_verificacao SET statusConta = 1, reset_token = NULL, reset_expire = NULL WHERE fk_usuario = ?"
);
$stmt->bind_param("i", $usuario['id_usuario']);
$stmt->execute();

if ($stmt->affected_rows !== 1) {
    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao ativar a conta. Tente novamente.']);
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['sucesso' => true, 'mensagem' => 'Conta ativada com sucesso! Você já pode fazer login.']);
?>
