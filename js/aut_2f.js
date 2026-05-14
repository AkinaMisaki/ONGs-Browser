document.addEventListener('DOMContentLoaded', function () {

    // Permite apenas dígitos no campo
    const inputCodigo = document.getElementById('codigo');
    if (inputCodigo) {
        inputCodigo.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    const form = document.getElementById('form2fa');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const codigo = document.getElementById('codigo')?.value.trim();

        if (!codigo || codigo.length !== 6) {
            mostrarMensagem('Digite os 6 dígitos do código.', 'erro');
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled    = true;
        btn.textContent = 'Verificando...';

        const dados = new FormData();
        dados.append('codigo', codigo);

        try {
            const resposta = await fetch('../controller/aut_2f.php', {
                method: 'POST',
                body: dados
            });

            if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);

            const resultado = await resposta.json();

            if (resultado.sucesso) {
                mostrarMensagem('Verificado! Redirecionando...', 'sucesso');
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 1500);
            } else {
                const erros = {
                    'sessao_invalida': 'Sessão inválida. Faça login novamente.',
                };
                mostrarMensagem(erros[resultado.mensagem] || resultado.mensagem, 'erro');

                if (resultado.mensagem === 'sessao_invalida') {
                    setTimeout(() => { window.location.href = 'login.php'; }, 2500);
                } else {
                    btn.disabled    = false;
                    btn.textContent = 'Verificar';
                }
            }

        } catch (erro) {
            console.error(erro);
            mostrarMensagem('Erro ao conectar com o servidor. Tente novamente.', 'erro');
            btn.disabled    = false;
            btn.textContent = 'Verificar';
        }
    });

});

function mostrarMensagem(texto, tipo = 'erro') {
    const el = document.getElementById('mensagem');
    if (!el) return;
    el.textContent = texto;
    el.className   = tipo;
}
