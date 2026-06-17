<?php
header('Content-Type: application/json');
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

// Valida email
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email inválido.']);
    exit;
}

// Checa se o email esta no BD via blind index (email_hash)
$emailHash = hash_hmac('sha256', strtolower($email), 'ongs-browser-email-index');
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email_hash = ?");
$stmt->bind_param("s", $emailHash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {

    // Gera token e define a validade por 1 hora
    $token  = bin2hex(random_bytes(32));
    $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $conn->prepare("UPDATE usuario_verificacao uv INNER JOIN usuario u ON u.id_usuario = uv.fk_usuario SET uv.reset_token=?, uv.reset_expire=? WHERE u.email_hash=?");
    $stmt->bind_param("sss", $token, $expire, $emailHash);
    $stmt->execute();

    $scheme       = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $_proj        = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
    $resetLink    = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_proj . '/view/alterar_senha.php?token=' . $token;

    // Manda email por SMTP
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
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;
                        max-width: 600px; margin: auto; border: 1px solid #ddd;
                        padding: 20px; border-radius: 8px;'>
                <h2 style='color: #0056b3;'>Recuperação de Conta</h2>
                <p>Olá,</p>
                <p>Recebemos um pedido para redefinir a senha da sua conta.
                   Clique no botão abaixo para criar uma nova senha:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink'
                       style='background-color: #0056b3; color: #ffffff; padding: 12px 25px;
                              text-decoration: none; border-radius: 5px;
                              font-weight: bold; display: inline-block;'>
                        Redefinir Minha Senha
                    </a>
                </div>
                <p style='font-size: 14px;'>Este link expira em 1 hora.</p>
                <p style='font-size: 14px;'>Se você não fez essa solicitação,
                   ignore este e-mail. Sua senha permanecerá a mesma.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px;
                            font-size: 12px; color: #555; text-align: justify;'>
                    <strong>Termos de Uso e Privacidade:</strong><br>
                    Ao concluir a redefinição de sua senha, você declara que leu e concorda
                    com nossos Termos de Uso e Política de Privacidade.
                </div>
            </div>";

        $mail->send();
        echo json_encode([
            'sucesso'  => true,
            'mensagem' => 'Se este email estiver cadastrado, você receberá as instruções em breve.'
        ]);
    } catch (Exception $e) {
        // Se der erro não mostra o erro pro usuário, só um erro genérico
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Não foi possível enviar o email. Tente novamente.'
        ]);
    }

} else {
    // Se o email não estiver no BD, ainda mostra a mesma mensagem pra evitar enumeração de email
    echo json_encode([
        'sucesso'  => true,
        'mensagem' => 'Se este email estiver cadastrado, você receberá as instruções em breve.'
    ]);
}

$stmt->close();
$conn->close();
exit;