async function realizarCadastroUsuario() {
    const campoEmail = document.getElementById('new_mailCheck').value.trim();
    if (campoEmail === '') {
        alert('Atenção: O campo Email é obrigatório!');
        return;
    }

    const dadosFormulario = new FormData();
    dadosFormulario.append('email', campoEmail);

    try {
        const resposta = await fetch('../controller/forgot_password.php', {
            method: 'POST',
            body: dadosFormulario
        });

        const texto = await resposta.text();
        const resultado = JSON.parse(texto);

        if (resultado.sucesso) {
            alert(resultado.mensagem);
            document.getElementById('formContato').reset();
        } else {
            alert('Erro:\n' + resultado.mensagem);
        }
    } catch (erro) {
        alert('Erro crítico: ' + erro);
    }
}