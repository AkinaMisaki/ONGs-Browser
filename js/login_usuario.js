let recaptchaSenhaId, recaptchaTelegramId;

function initRecaptcha() {
    recaptchaSenhaId = grecaptcha.render('recaptcha-senha', { sitekey: CAPTCHA_SITE });
    recaptchaTelegramId = grecaptcha.render('recaptcha-telegram', { sitekey: CAPTCHA_SITE });
}

function switchTab(tab) {
    const formSenha    = document.getElementById('form-senha');
    const formTelegram = document.getElementById('form-telegram');
    const tabSenha     = document.getElementById('tab-senha');
    const tabTelegram  = document.getElementById('tab-telegram');

    if (tab === 'senha') {
        formSenha.style.display    = '';
        formTelegram.style.display = 'none';
        tabSenha.classList.add('active');
        tabTelegram.classList.remove('active');
    } else {
        formSenha.style.display    = 'none';
        formTelegram.style.display = '';
        tabSenha.classList.remove('active');
        tabTelegram.classList.add('active');
    }
}

async function realizarLogin(event) {
    event.preventDefault();

    const campoUsuario = document.getElementById('user_acess').value.trim();
    const campoSenha   = document.getElementById('passCheck').value.trim();

    if (campoUsuario === '') { alert('Atenção: O campo Usuário é obrigatório!'); return; }
    if (campoSenha === '')   { alert('Atenção: A senha é obrigatória!'); return; }

    const dados = new FormData();
    dados.append('usuario', campoUsuario);
    dados.append('senha', campoSenha);
    dados.append('g-recaptcha-response', grecaptcha.getResponse(recaptchaSenhaId));

    try {
        const resposta  = await fetch('../controller/loginController.php', { method: 'POST', body: dados });
        const resultado = await resposta.json();

        if (resultado.sucesso && resultado.acao === '2fa_required') {
            window.location.href = 'aut_2f.php';
        } else if (resultado.sucesso) {
            window.location.href = '../index.php';
        } else {
            alert('Erro no Login:\n' + resultado.mensagem);
            grecaptcha.reset(recaptchaSenhaId);
        }
    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.' + erro);
    }
}

async function solicitarOtp(event) {
    event.preventDefault();

    const usuario = document.getElementById('tg-usuario').value.trim();
    if (usuario === '') { alert('Atenção: O campo Usuário é obrigatório!'); return; }

    const dados = new FormData();
    dados.append('action', 'request');
    dados.append('usuario', usuario);
    dados.append('g-recaptcha-response', grecaptcha.getResponse(recaptchaTelegramId));

    try {
        const resposta  = await fetch('../controller/telegram_otp_user.php', { method: 'POST', body: dados });
        const resultado = await resposta.json();

        if (resultado.sucesso) {
            document.getElementById('otp-login-label').textContent = resultado.login ?? usuario;
            document.getElementById('form-telegram').style.display = 'none';
            document.getElementById('form-otp').style.display      = '';
            document.querySelector('.login-tabs').style.display     = 'none';
            document.getElementById('otp-input').focus();
        } else {
            alert(resultado.mensagem);
            grecaptcha.reset(recaptchaTelegramId);
        }
    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.' + erro);
    }
}

async function verificarOtp(event) {
    event.preventDefault();

    const otp = document.getElementById('otp-input').value.trim().toUpperCase();
    if (otp.length !== 6) { alert('O código deve ter 6 caracteres.'); return; }

    const dados = new FormData();
    dados.append('action', 'verify');
    dados.append('otp', otp);

    try {
        const resposta  = await fetch('../controller/telegram_otp_user.php', { method: 'POST', body: dados });
        const resultado = await resposta.json();

        if (resultado.sucesso && resultado.acao === '2fa_required') {
            window.location.href = 'aut_2f.php';
        } else if (resultado.sucesso) {
            window.location.href = '../index.php';
        } else {
            alert(resultado.mensagem);
            document.getElementById('otp-input').value = '';
            if (resultado.expirado) {
                // OTP invalidated — go back to Telegram tab
                document.getElementById('form-otp').style.display      = 'none';
                document.getElementById('form-telegram').style.display = '';
                document.querySelector('.login-tabs').style.display     = '';
                switchTab('telegram');
                grecaptcha.reset(recaptchaTelegramId);
            }
        }
    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.' + erro);
    }
}

async function cancelarOtp() {
    const dados = new FormData();
    dados.append('action', 'cancel');
    await fetch('../controller/telegram_otp_user.php', { method: 'POST', body: dados }).catch(() => {});

    document.getElementById('form-otp').style.display      = 'none';
    document.getElementById('form-telegram').style.display = 'none';
    document.getElementById('form-senha').style.display    = '';
    document.querySelector('.login-tabs').style.display    = '';
    switchTab('senha');
    grecaptcha.reset(recaptchaSenhaId);
}
