async function realizarLogin() {
    // Pegando os valores
    const campoUsuario = document.getElementById('user_acess').value.trim();
    const campoSenha = document.getElementById('passCheck').value.trim();

    // Validação no front-end usando o alert nativo
    if (campoUsuario === '') {
        alert('Atenção: O campo Usuário é obrigatório!');
        return;
    }

    if (campoSenha === '') {
        alert('Atenção: A senha é obrigatória!');
        return;
    }

    // Empacota os dados
    const dadosFormulario = new FormData();
    dadosFormulario.append('usuario', campoUsuario);
    dadosFormulario.append('senha', campoSenha);

    try {
        const resposta = await fetch('../controller/loginController.php', {
            method: 'POST',
            body: dadosFormulario
        });

        const resultado = await resposta.json();

        // Tratando a resposta do PHP com alert
        if (resultado.sucesso) {
            alert(resultado.mensagem); // Mensagem de sucesso
            document.getElementById('formContato').reset(); // Limpa o formulário
        } else {
            alert('Erro no Login:\n' + resultado.mensagem); // O \n quebra a linha no alert
        }

    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.');
    }
}