<?php
//header('Location: index.php');
// Config público para vocês acessarem o banco de dados
$env = parse_ini_file(dirname(__DIR__) . '/../config/.env');

$db_host      = $env['DB_HOST'];
$db_user      = $env['DB_USER'];
$db_pass      = $env['DB_PASS'];
$db_name      = $env['DB_NAME'];
$db_port      = (int) $env['DB_PORT'];
$SMTP_PASSWORD        = $env['SMTP_PASSWORD'];
$CAPTCHA_SITE = $env['RECAPTCHA_SITE'];
$CAPTCHA_SECRETA = $env['RECAPTCHA_SECRET'];

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    die("Database connection failed.");
}
?>
