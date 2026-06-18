document.addEventListener('DOMContentLoaded', function () {

    function toBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}


    const form = document.getElementById('resetForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        const token           = document.getElementById('token')?.value;
        const password        = document.getElementById('password')?.value;
        const passwordConfirm = document.getElementById('password_confirm')?.value;
        const csrfToken       = document.getElementById('csrf_token')?.value;

        if (!token || !csrfToken) {
            mostrarMensagem('Erro na página. Recarregue e tente novamente.', 'erro');
            return;
        }

        // Valida tamanho da senha
        if (password.length < 8) {
            mostrarMensagem('A senha deve ter pelo menos 8 caracteres.', 'erro');
            return;
        }

        if (password !== passwordConfirm) {
            mostrarMensagem('As senhas não coincidem.', 'erro');
            return;
        }

        // Bloqueia o botão pra não floodar o sistema
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Alterando...';

try {
            // etapa 1:  importar a chave publica
            const derResp = await fetch('../public.der');
            if (!derResp.ok) throw new Error('Falha ao carregar chave pública do servidor.');
            const derbuffer = await derResp.arrayBuffer();
            
            const pubkey = await crypto.subtle.importKey(
                'spki', derbuffer, { name: 'RSA-OAEP', hash: 'SHA-1' }, false, ['encrypt']
            );

            // etapa 2: gerar chave aes da sessao
            const chaveAES = await crypto.subtle.generateKey(
                { name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']
            );

            // etapa 3: empacota  os dados da nova senha
            // 
            const dados = JSON.stringify({ 
                token: token, 
                password: password, 
                password_confirm: passwordConfirm, 
                csrf_token: csrfToken 
            });
            
            const iv = crypto.getRandomValues(new Uint8Array(12));
            const dadosCifrados = await crypto.subtle.encrypt(
                { name: 'AES-GCM', iv }, chaveAES, new TextEncoder().encode(dados)
            );

            // Etapa 4: Esconder a Chave AES
            const chaveAESRaw = await crypto.subtle.exportKey('raw', chaveAES);
            const chaveAESCifrada = await crypto.subtle.encrypt(
                { name: 'RSA-OAEP' }, pubkey, chaveAESRaw
            );

            // Etapa 5: envia o jason criptografado
            const resposta = await fetch('../controller/alterar_senha.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    keyAESCifrada: toBase64(chaveAESCifrada),
                    msg_cifrada:   toBase64(dadosCifrados),
                    iv:            toBase64(iv)
                })
            });

            if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);

            const resultado = await resposta.json();

            if (resultado.sucesso) {
                mostrarMensagem('Senha alterada com sucesso! Redirecionando...', 'sucesso');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                const erros = {
                    'invalid_token':    'Link inválido.',
                    'token_expired':    'Link expirado. Solicite um novo.',
                    'password_too_short': 'A senha deve ter pelo menos 8 caracteres.',
                    'password_mismatch': 'As senhas não coincidem.',
                    'invalid_csrf':     'Erro de segurança. Recarregue a página.',
                    'update_failed':    'Erro ao atualizar a senha. Tente novamente.',
                };
                mostrarMensagem(erros[resultado.mensagem] || 'Erro desconhecido.', 'erro');
            }

        } catch (erro) {
            console.error(erro);
            mostrarMensagem('Erro ao conectar com o servidor. Tente novamente.', 'erro');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Alterar Senha';
        }
    });

});

function mostrarMensagem(texto, tipo = 'erro') {
    const el = document.getElementById('mensagem');
    if (!el) return;
    el.textContent = texto;
    el.className = tipo;
}