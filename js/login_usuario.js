let recaptchaSenhaId, recaptchaTelegramId;

function initRecaptcha() {
    recaptchaSenhaId    = grecaptcha.render('recaptcha-senha',    { sitekey: CAPTCHA_SITE });
    recaptchaTelegramId = grecaptcha.render('recaptcha-telegram', { sitekey: CAPTCHA_SITE });
}

function toBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
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

    const captchaToken = grecaptcha.getResponse(recaptchaSenhaId);
    if (!captchaToken) { alert('Por favor, confirme que você não é um robô.'); return; }

    try {
        // Etapa 2: Carregar chave pública RSA do servidor
        const derkey = await fetch('../public.der');
        if (!derkey.ok) throw new Error('Não foi possível carregar a chave pública (public.der).');
        const derbuffer = await derkey.arrayBuffer();

        // Etapa 2: Importar chave pública
        const pubkey = await crypto.subtle.importKey(
            'spki',
            derbuffer,
            { name: 'RSA-OAEP', hash: 'SHA-1' },
            false,
            ['encrypt']
        );

        // Etapa 3: Gerar chave AES de sessão (256 bits)
        const chaveAES = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        // Etapa 4: Serializar credenciais e cifrar com AES-GCM
        const dados = JSON.stringify({ campoUsuario, campoSenha });
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const dadosCifrado = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            chaveAES,
            new TextEncoder().encode(dados)
        );

        // Etapa 5: Cifrar chave AES com RSA pública
        const chaveAESRaw    = await crypto.subtle.exportKey('raw', chaveAES);
        const chaveAESCifrada = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            pubkey,
            chaveAESRaw
        );

        // Enviar ao servidor
        const resposta = await fetch('../controller/loginController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                keyAESCifrada: toBase64(chaveAESCifrada),
                msg_cifrada:   toBase64(dadosCifrado),
                iv:            toBase64(iv),
                captchaToken:  captchaToken
            })
        });

        if (!resposta.ok) throw new Error(`Erro HTTP ${resposta.status} ao contatar o servidor.`);

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
        alert('Erro crítico: Falha de comunicação com o servidor.\n' + erro.message);
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
        if (!resposta.ok) throw new Error(`Erro HTTP ${resposta.status}`);
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
        alert('Erro crítico: Falha de comunicação com o servidor.\n' + erro.message);
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
        if (!resposta.ok) throw new Error(`Erro HTTP ${resposta.status}`);
        const resultado = await resposta.json();

        if (resultado.sucesso && resultado.acao === '2fa_required') {
            window.location.href = 'aut_2f.php';
        } else if (resultado.sucesso) {
            window.location.href = '../index.php';
        } else {
            alert(resultado.mensagem);
            document.getElementById('otp-input').value = '';
            if (resultado.expirado) {
                document.getElementById('form-otp').style.display      = 'none';
                document.getElementById('form-telegram').style.display = '';
                document.querySelector('.login-tabs').style.display    = '';
                switchTab('telegram');
                grecaptcha.reset(recaptchaTelegramId);
            }
        }
    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.\n' + erro.message);
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
