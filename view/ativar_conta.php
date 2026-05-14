<?php
$token = htmlspecialchars(trim($_GET['token'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar Conta — ONGs Browser</title>
    <link rel="stylesheet" href="css/alterar_senha.css">
    <style>
        .icone-status { font-size: 3rem; display: block; text-align: center; margin-bottom: 1rem; }
        #mensagem { text-align: center; margin-bottom: 1rem; font-size: 1rem; }
        #mensagem.sucesso { color: #155724; }
        #mensagem.erro    { color: #721c24; }
        #mensagem.info    { color: #0056b3; }
        #acoes { text-align: center; margin-top: 1.2rem; }
        #acoes a { color: #0056b3; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/reusable/header.php'; ?>
    <main>
        <h1>Ativar Conta</h1>

        <?php if (empty($token)): ?>
            <p id="mensagem" class="erro">Link de ativação inválido ou ausente.</p>
            <div id="acoes"><a href="login.php">Ir para o Login</a></div>
        <?php else: ?>
            <span id="icone" class="icone-status">⏳</span>
            <p id="mensagem">Verificando seu link de ativação...</p>
            <div id="acoes" style="display:none;">
                <a href="login.php">Ir para o Login</a>
            </div>
        <?php endif; ?>
    </main>

    <?php if (!empty($token)): ?>
    <script>
    (async function () {
        const form = new FormData();
        form.append('token', <?= json_encode($token) ?>);

        const erros = {
            'link_invalido': 'Link de ativação inválido. Certifique-se de usar o link enviado ao seu e-mail.',
            'ja_ativa':      'Esta conta já foi ativada. Faça login normalmente.',
        };

        try {
            const resp   = await fetch('../controller/ativar_conta.php', { method: 'POST', body: form });
            const result = await resp.json();

            if (result.mensagem === 'link_reenviado') {
                document.getElementById('icone').textContent    = '📧';
                document.getElementById('mensagem').className   = 'info';
                document.getElementById('mensagem').textContent = 'Seu link de ativação expirou — enviamos um novo para o seu e-mail. Verifique sua caixa de entrada.';
                document.getElementById('acoes').style.display  = 'block';
                return;
            }

            document.getElementById('icone').textContent = result.sucesso ? '✅' : '❌';
            document.getElementById('mensagem').className   = result.sucesso ? 'sucesso' : 'erro';
            document.getElementById('mensagem').textContent = result.sucesso
                ? result.mensagem
                : (erros[result.mensagem] || result.mensagem);

            document.getElementById('acoes').style.display = 'block';

            if (result.sucesso) {
                setTimeout(() => { window.location.href = 'login.php'; }, 3000);
            }
        } catch (e) {
            document.getElementById('icone').textContent    = '❌';
            document.getElementById('mensagem').className   = 'erro';
            document.getElementById('mensagem').textContent = 'Erro ao conectar com o servidor. Tente novamente.';
            document.getElementById('acoes').style.display  = 'block';
        }
    })();
    </script>
    <?php endif; ?>
</body>
</html>
