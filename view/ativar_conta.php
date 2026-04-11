<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar Conta</title>
    <link rel="stylesheet" href="css/login_usuario.css">
</head>
<body>
    <header class="barra-fixa">
        <nav>
            <button onclick="window.location.href='../index.php'">Início</button>
        </nav>
    </header>

    <main>
        <h1>Ativação de Conta</h1>
        <form id="formAtivacao">
            <input type="hidden" id="emailAtivacao" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
            
            <label for="codigo">Código de Verificação (6 dígitos):</label>
            <input type="text" id="codigo" maxlength="6" placeholder="Ex: 123456" style="text-align: center; font-size: 1.5rem; letter-spacing: 5px;">

            <button type="button" onclick="validarAtivacao()">Ativar Conta</button>
        </form>
    </main>

    <script>
        async function validarAtivacao() {
            const email = document.getElementById('emailAtivacao').value;
            const codigo = document.getElementById('codigo').value.trim();

            if (!email || codigo.length !== 6) {
                alert("Por favor, insira o código de 6 dígitos corretamente.");
                return;
            }

            const dados = new FormData();
            dados.append('email', email);
            dados.append('codigo', codigo);

            try {
                const resposta = await fetch('../controller/ativar_conta.php', { method: 'POST', body: dados });
                const resultado = await resposta.json();

                alert(resultado.mensagem);
                if (resultado.sucesso) {
                    window.location.href = 'login.php'; // Manda pro login se deu certo
                }
            } catch (erro) {
                alert('Erro na comunicação com o servidor.');
            }
        }
    </script>
</body>
</html>