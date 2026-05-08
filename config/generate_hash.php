<?php
/**
 * ONE-TIME HELPER — DELETE THIS FILE AFTER USE.
 * Run this to generate your admin password hash, then paste the result
 * into your .env as: ADMIN_PASSWORD_HASH=<hash>
 */

// Block access if a hash has already been set in .env (safety guard)
$env = parse_ini_file(__DIR__ . '/.env');
if (!empty($env['ADMIN_PASSWORD_HASH']) && $env['ADMIN_PASSWORD_HASH'] !== 'CHANGE_ME_RUN_GENERATE_HASH_PHP') {
    http_response_code(403);
    die('<b>Acesso bloqueado:</b> senha já configurada. Delete este arquivo.');
}

$hash = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass    = $_POST['pass']    ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($pass) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($pass !== $confirm) {
        $erro = 'As senhas não coincidem.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerar Hash — USO ÚNICO</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #eee; padding: 2rem; max-width: 600px; margin: auto; }
        h2   { color: #ff4444; }
        form { display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem; }
        input { padding: 0.6rem; font-size: 1rem; border-radius: 4px; border: none; }
        button { padding: 0.7rem; background: #0d47a1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .hash-box { background: #2a2a2a; border: 1px solid #444; padding: 1rem; border-radius: 6px; word-break: break-all; margin-top: 1.5rem; }
        .hash-box h3 { color: #4caf50; margin-bottom: 0.5rem; }
        .hash-box code { color: #fff; font-size: 0.95rem; }
        .step { background: #2a2a2a; border-left: 3px solid #0d47a1; padding: 0.8rem 1rem; margin-top: 0.5rem; font-size: 0.9rem; }
        .erro { color: #ff4444; }
    </style>
</head>
<body>
<h2>&#9888; USE ÚNICO — DELETE APÓS USO</h2>
<p>Este arquivo gera o hash da senha de administrador para o painel Admin.</p>

<?php if ($hash): ?>
    <div class="hash-box">
        <h3>&#10003; Hash gerado com sucesso!</h3>
        <p>Copie a linha abaixo e cole no seu <code>.env</code>:</p>
        <br>
        <code>ADMIN_PASSWORD_HASH=<?php echo htmlspecialchars($hash); ?></code>
    </div>
    <div class="step" style="margin-top:1.5rem; border-left-color:#ff4444;">
        <strong>&#9888; Próximos passos:</strong><br>
        1. Substitua o valor de <code>ADMIN_PASSWORD_HASH</code> no seu <code>.env</code><br>
        2. <strong>Delete este arquivo imediatamente.</strong>
    </div>
<?php else: ?>
    <form method="POST">
        <label>Nova senha (mín. 8 caracteres):</label>
        <input type="password" name="pass" placeholder="Senha" required>
        <label>Confirmar senha:</label>
        <input type="password" name="confirm" placeholder="Confirmar senha" required>
        <?php if ($erro): ?>
            <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>
        <button type="submit">Gerar Hash</button>
    </form>
<?php endif; ?>
</body>
</html>
