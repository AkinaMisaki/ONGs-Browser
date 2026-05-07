<?php
include_once __DIR__ . "/../config.php";
?>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<form id="formCaptcha" action="../controller/captcha_controller.php" method="POST">

    <div class="captcha-container">
        <div class="g-recaptcha" 
             data-sitekey="<?php echo $CAPTCHA_SITE; ?>"
             data-callback="autoSubmitForm">
        </div>
    </div>

</form>

<script>
    function autoSubmitForm() {
        document.getElementById("formCaptcha").submit();
    }
</script>