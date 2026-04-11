<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

$sql = "SELECT usuario_login, statusConta FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['statusConta'] === 'pendente') {
    die("<div style='text-align:center; margin-top:50px; font-family:Arial;'>
            <h2>Acesso Negado</h2>
            <p>Sua conta ainda não está ativada. Verifique seu e-mail.</p>
            <a href='login.php'>Voltar</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Conta</title>
    <link rel="stylesheet" href="css/login_usuario.css">
</head>
<body>
    <header class="barra-fixa">
        <nav>
            <button onclick="window.location.href='../index.php'">Início</button>
            <button onclick="window.location.href='../controller/logout.php'" style="background: #dc3545; border-color: #c82333;">Sair</button>
        </nav>
    </header>

    <main>
        <h1>Painel de Controle</h1>
        <p style="margin-bottom: 20px;">
            Olá, <strong><?= htmlspecialchars($user['usuario_login']) ?></strong>! 
            Status: <span style="color: #28a745; text-transform: uppercase; font-weight: bold;"><?= htmlspecialchars($user['statusConta']) ?></span>
        </p>

        <form id="formCredenciais" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px;">Alterar Dados de Acesso</h3>
            
            <label>Nome de Usuário:</label>
            <input type="text" id="novoUsuario" value="<?= htmlspecialchars($user['usuario_login']) ?>">
            
            <label>Nova Senha (deixe em branco para não alterar):</label>
            <input type="password" id="novaSenha" placeholder="Digite a nova senha">
            
            <button type="button" onclick="atualizarCredenciais()">Salvar Alterações</button>
        </form>

        <?php if ($user['statusConta'] === 'ativo'): ?>
        <form id="formOrganizador" style="background-color: #e9ecef; border-color: #adb5bd;">
            <h3 style="color: #0056b3; margin-bottom: 10px;">Deseja ser um Organizador?</h3>
            <p style="font-size: 0.9rem; color: #555; margin-bottom: 15px;">Forneça os dados abaixo para liberar a criação de ONGs.</p>
            
            <label>CPF:</label>
            <input type="text" id="cpf" placeholder="000.000.000-00">
            
            <label>RG:</label>
            <input type="text" id="rg" placeholder="00.000.000-0">
            
            <label>Telefone:</label>
            <input type="text" id="telefone" placeholder="(00) 00000-0000">

            <div style="margin-top: 15px; margin-bottom: 15px; display: flex; align-items: flex-start; gap: 10px;">
                <input type="checkbox" id="termoConsentimento" onchange="alternarBotaoOrganizador()" style="width: auto; margin-top: 4px; cursor: pointer;">
                <label for="termoConsentimento" style="font-size: 0.9rem; color: #444; font-weight: normal; cursor: pointer;">
                    Declaro que as informações são verdadeiras e <strong>consinto com o armazenamento dos meus dados</strong> (CPF, RG e Telefone) para fins de verificação e criação de ONGs, conforme a LGPD.
                </label>
            </div>

            <button type="button" id="btnOrganizador" onclick="virarOrganizador()" style="background-color: #0056b3; opacity: 0.5; cursor: not-allowed;" disabled>
                Solicitar Acesso de Organizador
            </button>
        </form>
        <?php endif; ?>

        <hr style="margin: 40px 0; width: 100%; border: 0; border-top: 1px solid #ddd;">

        <div class="danger-zone" style="background-color: #fff5f5; border: 1px solid #feb2b2; padding: 20px; border-radius: 8px; width: 100%; max-width: 450px;">
            <h3 style="color: #c53030; margin-top: 0;">Zona de Perigo</h3>
            <p style="color: #742a2a; font-size: 0.95rem; margin-bottom: 15px;">
                Ao excluir sua conta, todas as suas informações (perfil, dados de organizador e vínculos) serão removidas permanentemente. <strong>Esta ação não pode ser desfeita.</strong>
            </p>
            <button type="button" onclick="confirmarExclusao()" style="width: 100%; background-color: #e53e3e; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                Excluir Minha Conta Permanentemente
            </button>
        </div>
    </main>

    <script src="../js/gerenciar_conta.js"></script>
</body>
</html>