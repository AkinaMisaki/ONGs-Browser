function toBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}

async function realizarCadastro(event) {
    event.preventDefault();
    const campoCpf = document.getElementById('cpf').value.trim();
    const campoRg = document.getElementById('rg').value.trim();
    const campoTelefone = document.getElementById('telefone').value.trim();

    if (campoCpf === '' || campoRg === '' || campoTelefone === '') {
        mostrarMensagem('Atenção: Todos os campos são obrigatórios!', 'erro');
        return;
    }

    const regexCpf = /^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/;
    const regexRg  = /^\d{1,2}\.?\d{3}\.?\d{3}-?[\dXx]$/;
    const regexTel = /^\(?\d{2}\)?[\s-]?\d{4,5}-?\d{4}$/;

    if (!regexCpf.test(campoCpf)) {
        mostrarMensagem('CPF inválido. Use o formato 000.000.000-00 ou apenas números.', 'erro');
        return;
    }
    if (!regexRg.test(campoRg)) {
        mostrarMensagem('RG inválido. Use o formato 00.000.000-0.', 'erro');
        return;
    }
    if (!regexTel.test(campoTelefone)) {
        mostrarMensagem('Telefone inválido. Use o formato (11) 99999-9999.', 'erro');
        return;
    }

    try {

        const derkey = await fetch('../public.der');
        const derbuffer = await derkey.arrayBuffer();
        const pubkey = await crypto.subtle.importKey('spki', derbuffer, { name: 'RSA-OAEP', hash: 'SHA-1' }, false, ['encrypt']);
        

        const dadosOriginais = JSON.stringify({ cpf: campoCpf, rg: campoRg, telefone: campoTelefone });
        

        const chaveAES = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']);
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const dadosCifrados = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, chaveAES, new TextEncoder().encode(dadosOriginais));
        

        const chaveAESRaw = await crypto.subtle.exportKey('raw', chaveAES);
        const chaveAESCifrada = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, pubkey, chaveAESRaw);


        const dadosFormulario = new FormData();
        dadosFormulario.append('keyAESCifrada', toBase64(chaveAESCifrada));
        dadosFormulario.append('msg_cifrada', toBase64(dadosCifrados));
        dadosFormulario.append('iv', toBase64(iv));

        const resposta = await fetch('../controller/registroOrganizador.php', {
            method: 'POST',
            body: dadosFormulario
        });
        
        const resultado = await resposta.json();
        
        if (resultado.sucesso) {
            window.location.href = 'gerenciar_conta.php';
        } else {
            mostrarMensagem(resultado.mensagem, 'erro');
        }
    } catch (erro) {
        mostrarMensagem('Erro crítico: Falha de comunicação com o servidor.', 'erro');
        console.error("Detalhes do erro:", erro);
    }
}

function mostrarMensagem(texto, tipo) {
    const el = document.getElementById('mensagem-global');
    el.textContent = texto;
    el.className = 'mensagem-global ' + tipo;
    el.classList.remove('hidden');
}