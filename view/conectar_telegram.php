<?php
require __DIR__ . '/reusable/safeguard.php';
include __DIR__ . '/../config.php';

$id_usuario = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT telegram_id, telegram_pass FROM usuario_verificacao WHERE fk_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($row['telegram_id'])) {
    header('Location: /universidade/view/gerenciar_conta.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function gerarPasskeyUnica($conn) {
    do {
        $candidate = strtoupper(bin2hex(random_bytes(6)));
        $chk = $conn->prepare("SELECT 1 FROM usuario_verificacao WHERE telegram_pass = ?");
        $chk->bind_param("s", $candidate);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
        $chk->close();
    } while ($exists);
    return $candidate;
}

// Regenerate passkey on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'regenerar'
    && !empty($_POST['csrf_token'])
    && $_POST['csrf_token'] === $_SESSION['csrf_token']
) {
    $telepass = gerarPasskeyUnica($conn);
    $stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_pass = ? WHERE fk_usuario = ?");
    $stmt->bind_param("si", $telepass, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: conectar_telegram.php');
    exit;
}

// Generate passkey if none exists yet
if (empty($row['telegram_pass'])) {
    $telepass = gerarPasskeyUnica($conn);
    $stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_pass = ? WHERE fk_usuario = ?");
    $stmt->bind_param("si", $telepass, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $row['telegram_pass'] = $telepass;
}

$conn->close();

$passkey = htmlspecialchars($row['telegram_pass']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar Telegram</title>
    <link rel="stylesheet" href="css/gerenciar_conta.css">
    <style>
        .passkey-box {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: #f4f7fc;
            border: 1px solid #dce3ee;
            border-radius: 6px;
            padding: 0.8rem 1rem;
        }

        .passkey-box code {
            flex: 1;
            font-size: 1.3rem;
            font-weight: bold;
            letter-spacing: 3px;
            color: #0056b3;
            word-break: break-all;
        }

        .btn-copiar {
            background: none;
            border: 1px solid #0056b3;
            color: #0056b3;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            white-space: nowrap;
            transition: background 0.2s, color 0.2s;
        }

        .btn-copiar:hover {
            background: #0056b3;
            color: white;
        }

        .steps {
            list-style: none;
            counter-reset: step;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-bottom: 1.2rem;
        }

        .steps li {
            counter-increment: step;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            font-size: 0.95rem;
            color: #444;
            line-height: 1.5;
        }

        .steps li::before {
            content: counter(step);
            background: #0056b3;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .btn-voltar {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 0.85rem;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.3s;
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn-voltar:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/reusable/header.php'; ?>

    <main>
        <h1>Conectar Telegram</h1>

        <div id="mensagem-global" class="mensagem-global hidden"></div>

        <section class="card card-lgpd">
            <h2>Como conectar</h2>
            <ul class="steps">
                <li>Abra o Telegram e encontre o nosso bot.</li>
                <li>Envie o comando abaixo com sua passkey:</li>
            </ul>

            <div class="passkey-box">
                <code id="passkey-text">/verificar <?= $passkey ?></code>
                <button class="btn-copiar" onclick="copiarPasskey()">Copiar</button>
            </div>

            <p class="lgpd-descricao" style="margin-top:1rem; margin-bottom:0;">
                A passkey expira após o uso. Se você precisar de uma nova, clique em <strong>Regenerar Passkey</strong>.
            </p>
        </section>

        <section class="card">
            <h2>Regenerar Passkey</h2>
            <p class="lgpd-descricao">Gera uma nova passkey e invalida a atual.</p>
            <form method="POST" action="conectar_telegram.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="acao" value="regenerar">
                <button type="submit">Regenerar Passkey</button>
            </form>
        </section>

        <section class="card">
            <button type="button" class="btn-voltar" onclick="window.location.href='/universidade/view/gerenciar_conta.php'">
                Voltar para Gerenciar Conta
            </button>
        </section>
    </main>

    <script>
        function copiarPasskey() {
            const texto = document.getElementById('passkey-text').textContent;
            navigator.clipboard.writeText(texto).then(() => {
                const btn = document.querySelector('.btn-copiar');
                btn.textContent = 'Copiado!';
                setTimeout(() => { btn.textContent = 'Copiar'; }, 2000);
            });
        }
    </script>
</body>
</html>
