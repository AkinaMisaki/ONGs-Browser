document.addEventListener('DOMContentLoaded', function () {

    // --- Alterar Nome ---
    const formNome = document.getElementById('formAlterarNome');
    if (formNome) {
        formNome.addEventListener('submit', async function (e) {
            e.preventDefault();
            await enviarFormulario(formNome, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso && resultado.novo_nome) {
                    const nomeAtual = document.getElementById('nome-atual');
                    if (nomeAtual) nomeAtual.textContent = resultado.novo_nome;
                    formNome.reset();
                }
            });
        });
    }

    // --- Alterar Senha ---
    const formSenha = document.getElementById('formAlterarSenha');
    if (formSenha) {
        formSenha.addEventListener('submit', async function (e) {
            e.preventDefault();

            const novaSenha    = document.getElementById('nova_senha')?.value ?? '';
            const confirmarSenha = document.getElementById('confirmar_senha')?.value ?? '';

            if (novaSenha !== confirmarSenha) {
                mostrarMensagem('A nova senha e a confirmação não coincidem.', 'erro');
                return;
            }

            await enviarFormulario(formSenha, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) formSenha.reset();
            });
        });
    }

    // --- Exportar Dados ---
    const formExportar = document.getElementById('formExportarDados');
    if (formExportar) {
        formExportar.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = formExportar.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Gerando arquivo...';

            try {
                const dadosFormulario = new FormData(formExportar);
                const resposta = await fetch('../controller/gerenciar_conta.php', {
                    method: 'POST',
                    body: dadosFormulario
                });

                if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);

                const resultado = await resposta.json();

                if (resultado.sucesso) {
                    const json = JSON.stringify(resultado.dados, null, 2);
                    const blob = new Blob([json], { type: 'application/json' });
                    const url  = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href     = url;
                    link.download = 'meus_dados_ongs_browser.json';
                    link.click();
                    URL.revokeObjectURL(url);
                    mostrarMensagem('Seus dados foram exportados com sucesso.', 'sucesso');
                } else {
                    tratarErro(resultado.mensagem);
                }
            } catch (erro) {
                console.error(erro);
                mostrarMensagem('Erro ao conectar com o servidor. Tente novamente.', 'erro');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Baixar Meus Dados';
            }
        });
    }

    // --- Deletar Conta ---
    const formDeletar = document.getElementById('formDeletarConta');
    if (formDeletar) {
        formDeletar.addEventListener('submit', async function (e) {
            e.preventDefault();

            const confirmado = document.getElementById('confirmar_delecao')?.checked;
            if (!confirmado) {
                mostrarMensagem('Você precisa marcar a caixa de confirmação para excluir a conta.', 'erro');
                return;
            }

            const confirmacaoUsuario = window.confirm(
                'Tem certeza que deseja excluir sua conta?\n\nEsta ação é IRREVERSÍVEL e todos os seus dados serão removidos permanentemente.'
            );
            if (!confirmacaoUsuario) return;

            await enviarFormulario(formDeletar, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) {
                    mostrarMensagem('Conta excluída. Redirecionando...', 'sucesso');
                    setTimeout(() => {
                        window.location.href = '/universidade/index.php';
                    }, 2500);
                }
            });
        });
    }

});

// -----------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------

async function enviarFormulario(form, url, callbackSucesso) {
    const btn = form.querySelector('button[type="submit"]');
    const textoOriginal = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Aguarde...';

    try {
        const dadosFormulario = new FormData(form);
        const resposta = await fetch(url, {
            method: 'POST',
            body: dadosFormulario
        });

        if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);

        const resultado = await resposta.json();

        if (resultado.sucesso) {
            mostrarMensagem(resultado.mensagem, 'sucesso');
            if (callbackSucesso) callbackSucesso(resultado);
        } else {
            tratarErro(resultado.mensagem);
        }
    } catch (erro) {
        console.error(erro);
        mostrarMensagem('Erro ao conectar com o servidor. Tente novamente.', 'erro');
    } finally {
        btn.disabled = false;
        btn.textContent = textoOriginal;
    }
}

function tratarErro(mensagem) {
    const erros = {
        'nao_autenticado': 'Sessão expirada. Faça login novamente.',
        'invalid_csrf':    'Erro de segurança. Recarregue a página e tente novamente.',
        'Ação inválida.':  'Ação inválida. Recarregue a página.',
    };
    mostrarMensagem(erros[mensagem] || mensagem || 'Erro desconhecido.', 'erro');

    if (mensagem === 'nao_autenticado') {
        setTimeout(() => {
            window.location.href = '/universidade/view/login.php';
        }, 2000);
    }
}

function mostrarMensagem(texto, tipo = 'erro') {
    const el = document.getElementById('mensagem-global');
    if (!el) return;
    el.textContent = texto;
    el.className = `mensagem-global ${tipo}`;

    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => {
        el.className = 'mensagem-global hidden';
    }, 5000);
}

function realizarLogout() {
    fetch('../controller/loginController.php', {
        method: 'POST',
        body: new URLSearchParams({ acao: 'logout' })
    }).finally(() => {
        window.location.href = '/universidade/view/login.php';
    });
}
