<?php
session_start();
if (!isset($_SESSION['2fa_pending_id'])) {
    header('Location: /universidade/view/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação em Dois Fatores — ONGs Browser</title>
    <link rel="stylesheet" href="css/alterar_senha.css">
    <style>
        .codigo-input {
            text-align: center;
            font-size: 2rem !important;
            letter-spacing: 8px;
            font-weight: bold;
        }
        .info-2fa {
            background-color: #f0f4ff;
            color: #0056b3;
            border: 1px solid #c8d8f5;
            padding: 0.8rem;
            border-radius: 4px;
            font-size: 0.88rem;
            line-height: 1.5;
            text-align: center;
        }
        #mensagem.sucesso { color: #155724; }
        #mensagem.erro    { color: #721c24; }
    </style>
</head>
<body>
    <div class="barra-fixa">
        <span>Verificação em Dois Fatores</span>
    </div>

    <main>
        <h1>Verificação de Segurança</h1>
        <p id="mensagem"></p>
        <form id="form2fa">
            <label for="codigo">Código do Google Authenticator</label>
            <input type="text" id="codigo" name="codigo" class="codigo-input"
                   maxlength="6" placeholder="000000" inputmode="numeric"
                   autocomplete="one-time-code" required>
            <div class="info-2fa">
                Abra o <strong>Google Authenticator</strong> e digite o código de 6 dígitos
                exibido para <strong>ONGs Browser</strong>.
            </div>
            <button type="submit">Verificar</button>
        </form>
        <div class="login-links">
            <a href="/universidade/view/login.php">Cancelar e voltar ao login</a>
        </div>
    </main>

    <script src="../js/aut_2f.js"></script>
</body>
</html>
