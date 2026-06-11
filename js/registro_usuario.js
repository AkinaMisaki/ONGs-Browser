function toBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}

async function realizarCadastroUsuario(event) {
    event.preventDefault();

    const campoNome    = document.getElementById('new_user_name').value.trim();
    const campoEmail   = document.getElementById('new_user_email').value.trim();
    const campoUsuario = document.getElementById('new_user_acess').value.trim();
    const campoSenha   = document.getElementById('new_passCheck').value.trim();

    if (campoNome === '')    { alert('Atenção: O campo Nome é obrigatório!');    return; }
    if (campoEmail === '')   { alert('Atenção: Campo e-mail é obrigatório');     return; }
    if (campoUsuario === '') { alert('Atenção: O campo Usuário é obrigatório!'); return; }
    if (campoSenha === '')   { alert('Atenção: A senha é obrigatória!');         return; }

    const captchaToken = grecaptcha.getResponse();
    if (!captchaToken) { alert('Por favor, confirme que você não é um robô.'); return; }

    try {
        // Etapa 2: carregar chave pública RSA do servidor
        const derResp = await fetch('../public.der');
        if (!derResp.ok) throw new Error('Não foi possível carregar a chave pública (public.der).');
        const derbuffer = await derResp.arrayBuffer();

        // Etapa 2: importar chave pública
        const pubkey = await crypto.subtle.importKey(
            'spki',
            derbuffer,
            { name: 'RSA-OAEP', hash: 'SHA-1' },
            false,
            ['encrypt']
        );

        // Etapa 3: gerar chave AES-256-GCM de sessão
        const chaveAES = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        // Etapa 4: serializar dados do formulário e cifrar com AES-GCM
        const dados = JSON.stringify({ campoNome, campoEmail, campoUsuario, campoSenha });
        const iv    = crypto.getRandomValues(new Uint8Array(12));
        const dadosCifrados = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            chaveAES,
            new TextEncoder().encode(dados)
        );

        // Etapa 5: cifrar chave AES com RSA pública
        const chaveAESRaw    = await crypto.subtle.exportKey('raw', chaveAES);
        const chaveAESCifrada = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            pubkey,
            chaveAESRaw
        );

        const resposta = await fetch('../controller/registro_usuario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                keyAESCifrada: toBase64(chaveAESCifrada),
                msg_cifrada:   toBase64(dadosCifrados),
                iv:            toBase64(iv),
                captchaToken:  captchaToken
            })
        });

        if (!resposta.ok) throw new Error(`Erro HTTP ${resposta.status} ao contatar o servidor.`);

        const resultado = await resposta.json();

        if (resultado.sucesso) {
            alert(resultado.mensagem);
            document.getElementById('formContato').reset();
            grecaptcha.reset();
        } else {
            alert('Erro no Cadastro:\n' + resultado.mensagem);
            grecaptcha.reset();
        }

    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.\n' + erro.message);
    }
}
