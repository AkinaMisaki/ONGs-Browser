<?php

include_once __DIR__ . '/config.php';

echo "TEST_ENV: " . $TEST_ENV . "<br>";
echo "DB_USER: " . $db_user . "<br>";
echo "DB_PASS: " . $db_pass . "<br>";
echo "SMTP_PASSWORD: " . $SMTP_PASSWORD . "<br>";
echo "RECAPTCHA_SITE: " . $CAPTCHA_SITE . "<br>";
echo "RECAPTCHA_SECRET: " . $CAPTCHA_SECRETA . "<br>";
echo "TELEGRAM_BOT_TOKEN: " . $TELEGRAM_BOT_TOKEN . "<br>";

exit;
?>