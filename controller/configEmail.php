<?php 
    require "../libs/PHPMailer-7.0.2/PHPMailer-7.0.2/src/PHPMailer.php";
    require "../libs/PHPMailer-7.0.2/PHPMailer-7.0.2/src/Exception.php";
    require "../libs/PHPMailer-7.0.2/PHPMailer-7.0.2/src/SMTP.php";
    $password =   "yep9,M'3CyWMgAc";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer(true); 

    try {
        $protonmail = "gabrielsouz@proton.me";
        $mail-> IsSMTP();
        $mail->CharSet = "UTF-8";
        
 
        $mail->SMTPDebug = 0; 
        $mail->SMTPAuth = true; 
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465; //proton : 587 gmail: 587

        $mail->Username = $protonmail; 
        $mail->Password = $password; 

        $mail->setFrom('email@gmail.com', 'Sistema de Recuperação');
        $mail->addAddress('bieleubolacha@gmail.com'); //email a ser enviado

        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de Senha';
        $mail->Body    = 'Clique no link para recuperar sua senha: <a href="http://seusite.com/redefinir.php?token=123">Redefinir</a>';

        $mail->send();
        echo 'E-mail enviado com sucesso!';
    } catch (Exception $e) {
        echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";

        //mensagem de consetimento pro cadastro no email
        //colocar um link , quando apertar habilitar a pagina 

    }



?>