<?php include '../../view/reusable/header.php'; ?>

<main>
    <h2>Confirmar autenticação em dois fatores</h2>

    <?php if ($sucesso): ?>
        <p style="color: green;">2FA ativado com sucesso!</p>
        <button onclick="window.location.href='../../index.php'">Voltar ao início</button>

    <?php elseif ($erro): ?>
        <p style="color: red;"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <form action="" method="POST">
            <input type="text" name="codigo" maxlength="6" placeholder="000000" required>
            <button type="submit">Confirmar</button>
        </form>

    <?php else: ?>
        <p>Digite o código gerado pelo app autenticador:</p>
        <form action="" method="POST">
            <input type="text" name="codigo" maxlength="6" placeholder="000000" required>
            <button type="submit">Confirmar</button>
        </form>
    <?php endif; ?>
</main>