async function realizarCadastro(event) {
    event.preventDefault();
    // Pegando os valores
    const campoCpf = document.getElementById('cpf').value.trim();
    const campoRg = document.getElementById('rg').value.trim();
    const campoTelefone = document.getElementById('telefone').value.trim();

    // Validação básica
    if (campoCpf === '' || campoRg === '' || campoTelefone === '') {
        alert('Atenção: Todos os campos são obrigatórios!');
        return;
    }

    // Empacota os dados
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