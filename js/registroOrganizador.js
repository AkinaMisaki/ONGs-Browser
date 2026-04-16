async function realizarCadastro(){
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

        //php
        if (resultado.sucesso) {
            alert(resultado.mensagem); 
            document.getElementById('meuForm').reset();
            
        } else {
            alert('Erro no Cadastro:\n' + resultado.mensagem);
        }

    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.');
    }
}