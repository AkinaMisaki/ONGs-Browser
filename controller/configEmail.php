<?php
    $protonMail = "Tomandangelarato@gmail.com";
    $password = 'tpou uole avwj kthu';
    require "../libs/PHPMailer-7.0.2/PHPMailer-7.0.2/src/PHPMailer.php";
    require "../libs/PHPMailer-7.0.2/PHPMailer-7.0.2/src/Exception.php";
    require "../libs/PHPMailer-7.0.2/PHPMailer-7.0.2/src/SMTP.php";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer(true); 

    try {

        $mail->SMTPDebug = 0; 
        
        $mail->isSMTP();
        $mail->CharSet = "UTF-8";
        
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'ssl'; 
        $mail->Port       = 465;

        $mail->Username   = $protonMail; 
        $mail->Password   = $password; 

        $mail->setFrom($protonMail, 'Sistema de Recuperacao');
        $mail->addAddress('bieleubolacha@gmail.com'); //email de destino

        $mail->isHTML(true);
        $mail->Subject = 'Recuperacao de Senha';
        $mail->Body = $mail->isHTML(true);
$mail->Subject = 'Redefinição de Senha';

$mail->Body = "
    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
        <h2 style='color: #0056b3;'>Recuperação de Conta</h2>
        <p>Olá,</p>
        <p>Recebemos um pedido para alterar a senha da sua conta. Para criar uma nova senha, clique no botão abaixo:</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='' style='background-color: #0056b3; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                Redefinir Minha Senha
            </a>
        </div>
        
        <p style='font-size: 14px;'>Se você não fez essa solicitação, apenas ignore este e-mail. Sua senha continuará a mesma.</p>  
        
        <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
        
        <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; font-size: 12px; color: #555; text-align: justify;'>
            <strong>Termos de Uso e Privacidade:</strong><br>
            Atenção: Ao clicar no botão acima e concluir a redefinição de sua senha, você declara que leu, compreendeu e concorda expressamente com os nossos Termos de Uso e nossa Política de Privacidade. O uso contínuo da plataforma após a redefinição de credenciais ratifica a sua concordância com as nossas regras vigentes.
        </div>
    </div>";
   

        $mail->send();
        echo 'E-mail enviado com sucesso!';
    } catch (Exception $e) {
        echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }
    //conexao com luiz -> reforço da senha 
?>