document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('resetForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        const token           = document.getElementById('token')?.value;
        const password        = document.getElementById('password')?.value;
        const passwordConfirm = document.getElementById('password_confirm')?.value;
        const csrfToken       = document.getElementById('csrf_token')?.value;

        if (!token || !csrfToken) {
            mostrarMensagem('Erro na página. Recarregue e tente novamente.', 'erro');
            return;
        }

        // Valida tamanho da senha
        if (password.length < 8) {
            mostrarMensagem('A senha deve ter pelo menos 8 caracteres.', 'erro');
            return;
        }

        if (password !== passwordConfirm) {
            mostrarMensagem('As senhas não coincidem.', 'erro');
            return;
        }

        // Bloqueia o botão pra não floodar o sistema
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Alterando...';

        // Constroi um form pro controller com os dados
        const dadosFormulario = new FormData();
        dadosFormulario.append('token',            token);
        dadosFormulario.append('password',         password);
        dadosFormulario.append('password_confirm', passwordConfirm);
        dadosFormulario.append('csrf_token',       csrfToken);

        try {
            const resposta = await fetch('../controller/alterar_senha.php', {
                method: 'POST',
                body: dadosFormulario
            });

            if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);

            const resultado = await resposta.json();

            if (resultado.sucesso) {
                mostrarMensagem('Senha alterada com sucesso! Redirecionando...', 'sucesso');
                setTimeout(() => {
                    window.location.href = '/universidade/view/login.php';
                }, 2000);
            } else {
                const erros = {
                    'invalid_token':    'Link inválido.',
                    'token_expired':    'Link expirado. Solicite um novo.',
                    'password_too_short': 'A senha deve ter pelo menos 8 caracteres.',
                    'password_mismatch': 'As senhas não coincidem.',
                    'invalid_csrf':     'Erro de segurança. Recarregue a página.',
                    'update_failed':    'Erro ao atualizar a senha. Tente novamente.',
                };
                mostrarMensagem(erros[resultado.mensagem] || 'Erro desconhecido.', 'erro');
            }

        } catch (erro) {
            console.error(erro);
            mostrarMensagem('Erro ao conectar com o servidor. Tente novamente.', 'erro');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Alterar Senha';
        }
    });

});

function mostrarMensagem(texto, tipo = 'erro') {
    const el = document.getElementById('mensagem');
    if (!el) return;
    el.textContent = texto;
    el.className = tipo;
}