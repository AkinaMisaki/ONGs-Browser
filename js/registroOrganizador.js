async function realizarCadastro(event) {
    event.preventDefault();
    const campoCpf = document.getElementById('cpf').value.trim();
    const campoRg = document.getElementById('rg').value.trim();
    const campoTelefone = document.getElementById('telefone').value.trim();

    if (campoCpf === '' || campoRg === '' || campoTelefone === '') {
        alert('Atenção: Todos os campos são obrigatórios!');
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

    const dadosFormulario = new FormData();
    dadosFormulario.append('cpf', campoCpf);
    dadosFormulario.append('rg', campoRg);
    dadosFormulario.append('telefone', campoTelefone);

    try {
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
    }
}

function mostrarMensagem(texto, tipo) {
    const el = document.getElementById('mensagem-global');
    el.textContent = texto;
    el.className = 'mensagem-global ' + tipo;
}