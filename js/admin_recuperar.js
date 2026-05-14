const card2fa    = document.getElementById('card-2fa');
const tgStatus   = document.getElementById('tg-status');
const otpStatus  = document.getElementById('otp-status');
const totpStatus = document.getElementById('totp-status');

function setStatus(el, msg, tipo) {
    el.className   = tipo === 'ok' ? 'status-ok' : 'status-err';
    el.textContent = msg;
}

// ── Passo 1a: verifica se o Telegram foi vinculado e envia o OTP ──────────────
document.getElementById('btn-check-tg').addEventListener('click', async function () {
    this.disabled    = true;
    this.textContent = 'Verificando...';
    tgStatus.textContent = '';

    try {
        const dados = new FormData();
        dados.append('action', 'check_telegram');
        const res    = await fetch('../controller/admin_recuperar.php', { method: 'POST', body: dados });
        const result = await res.json();

        if (!result.sucesso) {
            setStatus(tgStatus, result.mensagem ?? 'Erro ao verificar.', 'err');
            this.disabled    = false;
            this.textContent = 'Verificar conexão';
            return;
        }

        if (result.linked) {
            setStatus(tgStatus, 'Telegram vinculado! Um código foi enviado. Confirme abaixo.', 'ok');
            document.getElementById('otp-area').style.display = 'flex';
            document.getElementById('tg-otp-input').focus();
            this.textContent = 'Reenviar código';
            this.disabled    = false;
        } else {
            setStatus(tgStatus, 'Telegram ainda não conectado. Envie o comando e tente novamente.', 'err');
            this.disabled    = false;
            this.textContent = 'Verificar conexão';
        }
    } catch (e) {
        setStatus(tgStatus, 'Erro de comunicação. Tente novamente.', 'err');
        this.disabled    = false;
        this.textContent = 'Verificar conexão';
    }
});

// ── Passo 1b: confirma o OTP recebido no Telegram ─────────────────────────────
document.getElementById('btn-verify-otp').addEventListener('click', async function () {
    const otp = document.getElementById('tg-otp-input').value.trim().toUpperCase();
    if (otp.length !== 6) {
        setStatus(otpStatus, 'Digite os 6 caracteres do código.', 'err');
        return;
    }

    this.disabled    = true;
    this.textContent = 'Verificando...';

    try {
        const dados = new FormData();
        dados.append('action', 'verify_telegram_otp');
        dados.append('otp', otp);
        const res    = await fetch('../controller/admin_recuperar.php', { method: 'POST', body: dados });
        const result = await res.json();

        if (result.sucesso) {
            setStatus(otpStatus, 'Telegram confirmado!', 'ok');
            setStatus(tgStatus, 'Telegram confirmado com sucesso!', 'ok');
            document.getElementById('otp-area').style.display     = 'none';
            document.getElementById('btn-check-tg').style.display = 'none';
            card2fa.classList.remove('card-disabled');
        } else {
            setStatus(otpStatus, result.mensagem ?? 'Código incorreto.', 'err');
            document.getElementById('tg-otp-input').value = '';
            if (result.expirado) {
                document.getElementById('otp-area').style.display = 'none';
                document.getElementById('tg-otp-input').value     = '';
            }
            this.disabled    = false;
            this.textContent = 'Confirmar código';
        }
    } catch (e) {
        setStatus(otpStatus, 'Erro de comunicação. Tente novamente.', 'err');
        this.disabled    = false;
        this.textContent = 'Confirmar código';
    }
});

// ── Passo 2a: gera o QR code ──────────────────────────────────────────────────
document.getElementById('btn-gerar-qr').addEventListener('click', async function () {
    this.disabled    = true;
    this.textContent = 'Gerando...';

    try {
        const dados = new FormData();
        dados.append('action', 'generate_qr');
        const res    = await fetch('../controller/admin_recuperar.php', { method: 'POST', body: dados });
        const result = await res.json();

        if (result.sucesso) {
            document.getElementById('qrContainer').innerHTML    = result.qr_svg;
            document.getElementById('secretManual').textContent = result.secret;
            document.getElementById('qr-area').style.display   = 'flex';
            this.style.display = 'none';
        } else {
            setStatus(totpStatus, result.mensagem ?? 'Erro ao gerar QR code.', 'err');
            this.disabled    = false;
            this.textContent = 'Gerar QR Code';
        }
    } catch (e) {
        setStatus(totpStatus, 'Erro de comunicação. Tente novamente.', 'err');
        this.disabled    = false;
        this.textContent = 'Gerar QR Code';
    }
});

// ── Passo 2b: confirma o código do autenticador ───────────────────────────────
document.getElementById('btn-confirm-2fa').addEventListener('click', async function () {
    const codigo = document.getElementById('totp-input').value.trim();
    if (!/^\d{6}$/.test(codigo)) {
        setStatus(totpStatus, 'Digite os 6 dígitos do código.', 'err');
        return;
    }

    this.disabled    = true;
    this.textContent = 'Verificando...';

    try {
        const dados = new FormData();
        dados.append('action', 'confirm_2fa');
        dados.append('codigo', codigo);
        const res    = await fetch('../controller/admin_recuperar.php', { method: 'POST', body: dados });
        const result = await res.json();

        if (result.sucesso) {
            setStatus(totpStatus, '2FA configurado! Redirecionando para o login...', 'ok');
            setTimeout(() => { window.location.href = 'admin_login.php'; }, 2000);
        } else {
            setStatus(totpStatus, result.mensagem ?? 'Erro ao confirmar 2FA.', 'err');
            this.disabled    = false;
            this.textContent = 'Confirmar e Ativar';
        }
    } catch (e) {
        setStatus(totpStatus, 'Erro de comunicação. Tente novamente.', 'err');
        this.disabled    = false;
        this.textContent = 'Confirmar e Ativar';
    }
});

// Só dígitos no campo do autenticador
document.getElementById('totp-input').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});

// Uppercase no campo do OTP do Telegram
document.getElementById('tg-otp-input').addEventListener('input', function () {
    this.value = this.value.toUpperCase().slice(0, 6);
});
