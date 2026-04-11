function alternarBotaoOrganizador() {
    const checkbox = document.getElementById('termoConsentimento');
    const botao = document.getElementById('btnOrganizador');

    if (checkbox.checked) {
        botao.disabled = false;
        botao.style.opacity = '1';
        botao.style.cursor = 'pointer';
    } else {
        botao.disabled = true;
        botao.style.opacity = '0.5';
        botao.style.cursor = 'not-allowed';
    }
}
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
    const consentimento = document.getElementById('termoConsentimento').checked;

    if (!consentimento) {
        alert("Você precisa consentir com o uso dos dados para continuar.");
        return;
    }

    if (cpf === '' || rg === '' || telefone === '') {
        alert("Preencha todos os campos (CPF, RG e Telefone).");
        return;
    }

    const dados = new FormData();
    dados.append('action', 'virar_organizador');
    dados.append('cpf', cpf);
    dados.append('rg', rg);
    dados.append('telefone', telefone);

    enviarParaServidor(dados, true);
}
async function confirmarExclusao() {
    const aviso = "ATENÇÃO: EXCLUSÃO DE CONTA\n\n" +
                  "Você está prestes a apagar sua conta permanentemente.\n" +
                  "Tem certeza absoluta que deseja prosseguir?";

    if (confirm(aviso)) {
        const dados = new FormData();
        dados.append('action', 'excluir_conta');

        try {
            const resposta = await fetch('../controller/gerenciar_controller.php', {
                method: 'POST',
                body: dados
            });
            const resultado = await resposta.json();

            if (resultado.sucesso) {
                alert(resultado.mensagem);
                window.location.href = '../index.php';
            } else {
                alert("Erro: " + resultado.mensagem);
            }
        } catch (erro) {
            alert("Erro na comunicação com o servidor.");
        }
    }
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
        alert("Erro na requisição.");
    }
}