<?php
include __DIR__ . '/../controller/init/admin_recuperar.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Recuperação de Acesso</title>
    <link rel="stylesheet" href="css/admin_crud.css">
    <style>
        .recuperar-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            padding-top: 80px;
            gap: 1.5rem;
        }
        .recuperar-card {
            background: #0d47a1;
            color: white;
            border: 3px solid #003370;
            border-radius: 8px;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .recuperar-card h2 { margin: 0; font-size: 1.15rem; }
        .step-badge {
            display: inline-block;
            background: #1565c0;
            border-radius: 50%;
            width: 2rem; height: 2rem;
            line-height: 2rem;
            text-align: center;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        .passkey-box {
            background: #fff;
            color: #333;
            border-radius: 4px;
            padding: 0.8rem 1rem;
            font-family: monospace;
            font-size: 1.2rem;
            letter-spacing: 0.1em;
            text-align: center;
            word-break: break-all;
        }
        .instrucao { font-size: 0.9rem; color: rgba(255,255,255,0.8); line-height: 1.5; margin: 0; }
        .status-ok  { color: #a8f0b8; font-weight: bold; margin: 0; }
        .status-err { color: #ffcdd2; font-weight: bold; margin: 0; }
        .card-disabled { opacity: 0.4; pointer-events: none; }
        #qrContainer svg { display: block; margin: 0 auto; }
        .secret-manual { font-size: 0.85rem; color: rgba(255,255,255,0.7); }
        .secret-manual summary { cursor: pointer; }
        .secret-manual code { background: rgba(0,0,0,0.3); padding: 0.3rem 0.5rem; border-radius: 3px; display: block; margin-top: 0.4rem; word-break: break-all; }

        /* Botões */
        .recuperar-card .btn-primario {
            background: #0088cc;
            color: white;
            border: none;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .recuperar-card .btn-primario:hover  { background: #0077b5; }
        .recuperar-card .btn-primario:disabled { background: #555; cursor: default; }

        .recuperar-card .btn-sucesso {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .recuperar-card .btn-sucesso:hover   { background: #218838; }
        .recuperar-card .btn-sucesso:disabled { background: #555; cursor: default; }

        /* Input de código */
        .recuperar-card .input-codigo {
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 0.3em;
            color: #333;
            text-transform: uppercase;
            width: 100%;
            box-sizing: border-box;
        }
        .recuperar-card .input-codigo-numerico {
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 0.3em;
            color: #333;
            width: 100%;
            box-sizing: border-box;
        }
        .recuperar-card label {
            font-size: 0.9rem;
            font-weight: bold;
            color: rgba(255,255,255,0.9);
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/reusable/header.php'; ?>

<main class="recuperar-main">
    <h1>Recuperação de Acesso</h1>
    <p style="color:#555; text-align:center; max-width:480px;">
        Siga os dois passos abaixo para reconectar seu Telegram e reconfigurar o autenticador.
    </p>

    <!-- Passo 1: Telegram -->
    <div class="recuperar-card" id="card-telegram">
        <h2><span class="step-badge">1</span>Reconectar Telegram</h2>
        <p class="instrucao">Envie o comando abaixo para o bot no Telegram:</p>
        <div class="passkey-box">/verificar <?= htmlspecialchars($telegramPass) ?></div>
        <p class="instrucao">Após enviar, clique em verificar. Você receberá um código de confirmação.</p>
        <p id="tg-status"></p>
        <button type="button" id="btn-check-tg" class="btn-primario">Verificar conexão</button>

        <!-- Confirmação via OTP (aparece após o Telegram ser detectado) -->
        <div id="otp-area" style="display:none; flex-direction:column; gap:0.8rem;">
            <label for="tg-otp-input">Código recebido no Telegram:</label>
            <input type="text" id="tg-otp-input" class="input-codigo"
                   placeholder="Ex: A3BK7Z" maxlength="6" autocomplete="one-time-code">
            <p id="otp-status"></p>
            <button type="button" id="btn-verify-otp" class="btn-sucesso">Confirmar código</button>
        </div>
    </div>

    <!-- Passo 2: 2FA (bloqueado até o Telegram ser confirmado) -->
    <div class="recuperar-card card-disabled" id="card-2fa">
        <h2><span class="step-badge">2</span>Reconfigurar Autenticador (2FA)</h2>
        <p class="instrucao">Escaneie o QR code com o Google Authenticator e confirme com o código gerado.</p>

        <button type="button" id="btn-gerar-qr" class="btn-primario">Gerar QR Code</button>

        <div id="qr-area" style="display:none; flex-direction:column; gap:1rem;">
            <div id="qrContainer"></div>
            <details class="secret-manual">
                <summary>Não consegue escanear? Use a chave manual</summary>
                <code id="secretManual"></code>
            </details>
            <label for="totp-input">Código de confirmação:</label>
            <input type="text" id="totp-input" class="input-codigo-numerico"
                   placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
            <p id="totp-status"></p>
            <button type="button" id="btn-confirm-2fa" class="btn-sucesso">Confirmar e Ativar</button>
        </div>
    </div>

</main>

<script src="../js/admin_recuperar.js"></script>
</body>
</html>
