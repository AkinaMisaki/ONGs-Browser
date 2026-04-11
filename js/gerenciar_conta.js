async function atualizarCredenciais() {
    const usuario = document.getElementById('novoUsuario').value.trim();
    const senha = document.getElementById('novaSenha').value.trim();

    if (usuario === '') {
        alert("O nome de usuário não pode ficar vazio.");
        return;
    }

    const dados = new FormData();
    dados.append('action', 'atualizar_credenciais');
    dados.append('usuario', usuario);
    dados.append('senha', senha);

    enviarParaServidor(dados);
}

async function virarOrganizador() {
    const cpf = document.getElementById('cpf').value.trim();
    const rg = document.getElementById('rg').value.trim();
    const telefone = document.getElementById('telefone').value.trim();

    if (cpf === '' || rg === '' || telefone === '') {
        alert("Para virar organizador, você deve preencher CPF, RG e Telefone.");
        return;
    }

    const dados = new FormData();
    dados.append('action', 'virar_organizador');
    dados.append('cpf', cpf);
    dados.append('rg', rg);
    dados.append('telefone', telefone);

    enviarParaServidor(dados, true);
}
async function enviarParaServidor(dadosFormulario, recarregar = false) {
    try {
        const resposta = await fetch('../controller/gerenciar_controller.php', {
            method: 'POST',
            body: dadosFormulario
        });
        const resultado = await resposta.json();

        alert(resultado.mensagem);
        if (resultado.sucesso && recarregar) {
            window.location.reload();
        }
    } catch (erro) {
        alert("Erro ao comunicar com o servidor.");
    }
}