document.addEventListener('DOMContentLoaded', function () {

    const containerInfo = document.getElementById('info-usuario-container');
    const containerCheckboxes = document.getElementById('checkboxes-exclusao');

    async function carregarDadosUsuario() {
        if (!containerInfo || !containerCheckboxes) return;

        const dados = new FormData();
        dados.append('acao', 'resgatar_dados_parciais');
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value; 
        if(csrfToken) dados.append('csrf_token', csrfToken);

        try {
            const resposta = await fetch('../controller/gerenciar_conta.php', { method: 'POST', body: dados });
            const resultado = await resposta.json();

            if (resultado.sucesso) {
                renderizarTelaParcial(resultado.dados);
            } else {
                mostrarMensagem(resultado.mensagem, 'erro');
            }
        } catch (erro) {
            console.error(erro);
            mostrarMensagem('Erro ao carregar seus dados.', 'erro');
        }
    }

    function renderizarTelaParcial(dados) {
        let htmlInfo = '';
        let htmlChecks = '';

        const camposProtegidos = ['nome_usuario', 'email'];

        Object.entries(dados).forEach(([chave, valor]) => {
            if (valor) {
                htmlInfo += `<p><strong>${chave.toUpperCase()}:</strong> ${valor}</p>`;
                if (!camposProtegidos.includes(chave)) {
                    htmlChecks += `
                        <label class="checkbox-label" style="margin-bottom: 0.5rem; display: block;">
                            <input type="checkbox" name="campos_exclusao[]" value="${chave}">
                            Remover ${chave.toUpperCase()}
                        </label>
                    `;
                }
            }
        });

        containerInfo.innerHTML = htmlInfo;
        
        const btnExcluir = document.getElementById('btnExcluirParcial');
        if (htmlChecks === '') {
            containerCheckboxes.innerHTML = '<p style="color: green;">Nenhum dado adicional armazenado.</p>';
            if(btnExcluir) btnExcluir.disabled = true;
        } else {
            containerCheckboxes.innerHTML = htmlChecks;
            if(btnExcluir) btnExcluir.disabled = false;
        }
    }

    carregarDadosUsuario();

    const formExclusaoParcial = document.getElementById('formExclusaoParcial');
    if (formExclusaoParcial) {
        formExclusaoParcial.addEventListener('submit', async function (e) {
            e.preventDefault();
            const marcados = formExclusaoParcial.querySelectorAll('input[name="campos_exclusao[]"]:checked');
            if (marcados.length === 0) {
                mostrarMensagem('Selecione pelo menos um dado para excluir.', 'erro');
                return;
            }
            if (!window.confirm('Tem certeza que deseja apagar os dados selecionados?')) return;
            await enviarFormulario(formExclusaoParcial, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) {
                    carregarDadosUsuario();
                }
            });
        });
    }

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

            const novaSenha      = document.getElementById('nova_senha')?.value ?? '';
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
            btn.disabled    = true;
            btn.textContent = 'Gerando arquivo...';

            try {
                const resposta = await fetch('../controller/gerenciar_conta.php', {
                    method: 'POST',
                    body: new FormData(formExportar)
                });
                const resultado = await resposta.json();

                if (resultado.sucesso) {
                    const blob = new Blob([JSON.stringify(resultado.dados, null, 2)], { type: 'application/json' });
                    const url  = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
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
                btn.disabled    = false;
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

            if (!window.confirm('Tem certeza que deseja excluir sua conta?\n\nEsta ação é IRREVERSÍVEL e todos os seus dados serão removidos permanentemente.')) return;

            await enviarFormulario(formDeletar, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) {
                    mostrarMensagem('Conta excluída. Redirecionando...', 'sucesso');
                    setTimeout(() => { window.location.href = '/universidade/index.php'; }, 2500);
                }
            });
        });
    }

    // --- 2FA: Gerar QR Code ---
    const btnGerarQr = document.getElementById('btnGerarQr');
    if (btnGerarQr) {
        btnGerarQr.addEventListener('click', async function () {
            btnGerarQr.disabled    = true;
            btnGerarQr.textContent = 'Gerando QR Code...';

            // Busca o csrf_token do formulário de ativação
            const csrfToken = document.querySelector('#formAtivar2fa input[name="csrf_token"]')?.value;

            const dados = new FormData();
            dados.append('acao', 'gerar_qr_2fa');
            dados.append('csrf_token', csrfToken);

            try {
                const resposta  = await fetch('../controller/gerenciar_conta.php', { method: 'POST', body: dados });
                const resultado = await resposta.json();

                if (resultado.sucesso) {
                    document.getElementById('qrContainer').innerHTML  = resultado.qr_svg;
                    document.getElementById('secretManual').textContent = resultado.secret;
                    document.getElementById('setup2fa').classList.remove('hidden');
                    btnGerarQr.style.display = 'none';
                } else {
                    tratarErro(resultado.mensagem);
                    btnGerarQr.disabled    = false;
                    btnGerarQr.textContent = 'Configurar 2FA';
                }
            } catch (erro) {
                console.error(erro);
                mostrarMensagem('Erro ao gerar QR Code. Tente novamente.', 'erro');
                btnGerarQr.disabled    = false;
                btnGerarQr.textContent = 'Configurar 2FA';
            }
        });
    }

    // --- 2FA: Confirmar ativação ---
    const formAtivar2fa = document.getElementById('formAtivar2fa');
    if (formAtivar2fa) {
        formAtivar2fa.addEventListener('submit', async function (e) {
            e.preventDefault();
            await enviarFormulario(formAtivar2fa, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) {
                    setTimeout(() => { window.location.reload(); }, 1500);
                }
            });
        });
    }

    // --- 2FA: Desativar ---
    const formDesativar2fa = document.getElementById('formDesativar2fa');
    if (formDesativar2fa) {
        formDesativar2fa.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!window.confirm('Deseja mesmo desativar o 2FA? Sua conta ficará menos segura.')) return;
            await enviarFormulario(formDesativar2fa, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) {
                    setTimeout(() => { window.location.reload(); }, 1500);
                }
            });
        });
    }

    // --- Telegram: Desconectar ---
    const formDesconectarTelegram = document.getElementById('formDesconectarTelegram');
    if (formDesconectarTelegram) {
        formDesconectarTelegram.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!window.confirm('Deseja mesmo desconectar o Telegram desta conta?')) return;
            await enviarFormulario(formDesconectarTelegram, '../controller/gerenciar_conta.php', function (resultado) {
                if (resultado.sucesso) setTimeout(() => { window.location.reload(); }, 1500);
            });
        });
    }

    // --- Só dígitos nos campos TOTP ---
    document.querySelectorAll('input[inputmode="numeric"]').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    });

});

// -----------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------

async function enviarFormulario(form, url, callbackSucesso) {
    const btn = form.querySelector('button[type="submit"]');
    const textoOriginal = btn.textContent;
    btn.disabled    = true;
    btn.textContent = 'Aguarde...';

    try {
        const resposta  = await fetch(url, { method: 'POST', body: new FormData(form) });
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
        btn.disabled    = false;
        btn.textContent = textoOriginal;
    }
}

function tratarErro(mensagem) {
    const erros = {
        'nao_autenticado': 'Sessão expirada. Faça login novamente.',
        'invalid_csrf':    'Erro de segurança. Recarregue a página e tente novamente.',
    };
    mostrarMensagem(erros[mensagem] || mensagem || 'Erro desconhecido.', 'erro');

    if (mensagem === 'nao_autenticado') {
        setTimeout(() => { window.location.href = '/universidade/view/login.php'; }, 2000);
    }
}

function mostrarMensagem(texto, tipo = 'erro') {
    const el = document.getElementById('mensagem-global');
    if (!el) return;
    el.textContent = texto;
    el.className   = `mensagem-global ${tipo}`;

    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => {
        el.className = 'mensagem-global hidden';
    }, 5000);
}

function realizarLogout() {
    fetch('../controller/logout.php', { method: 'GET' })
        .finally(() => { window.location.href = '/universidade/view/login.php'; });
}

function testarTele() {
    fetch('../controller/testar_telegram.php', { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagem(data.mensagem, 'sucesso');
            } else {
                tratarErro(data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao conectar com o servidor.', 'erro');
        });
}