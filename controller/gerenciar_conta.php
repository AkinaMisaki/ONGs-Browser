<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../config.php';

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

// Verifica sessão
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'nao_autenticado']);
    exit;
}

// Verifica CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_csrf']);
    exit;
}

$id_usuario = (int) $_SESSION['usuario_id'];
$acao = trim($_POST['acao'] ?? '');

// -----------------------------------------------------------------------
// AÇÃO: Alterar Nome
// -----------------------------------------------------------------------
if ($acao === 'alterar_nome') {
    $novo_nome = trim($_POST['novo_nome'] ?? '');

    if (empty($novo_nome)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O nome não pode ficar em branco.']);
        exit;
    }

    if (strlen($novo_nome) > 120) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O nome deve ter no máximo 120 caracteres.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuario SET nome_usuario = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $novo_nome, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['sucesso' => true, 'mensagem' => 'Nome atualizado com sucesso!', 'novo_nome' => htmlspecialchars($novo_nome)]);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Alterar Senha
// -----------------------------------------------------------------------
if ($acao === 'alterar_senha') {
    $senha_atual = $_POST['senha_atual']     ?? '';
    $nova_senha  = $_POST['nova_senha']      ?? '';
    $confirmar   = $_POST['confirmar_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos.']);
        exit;
    }

    if ($nova_senha !== $confirmar) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A nova senha e a confirmação não coincidem.']);
        exit;
    }

    $padraoSenha = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!preg_match($padraoSenha, $nova_senha)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter no mínimo 8 caracteres, letra maiúscula, minúscula, número e caractere especial.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT usuario_password FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || !password_verify($senha_atual, $usuario['usuario_password'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha atual incorreta.']);
        $conn->close();
        exit;
    }

    $options = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_ARGON2ID, $options);

    $stmt = $conn->prepare("UPDATE usuario SET usuario_password = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $nova_senha_hash, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso!']);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Exportar Dados (LGPD — Art. 18, portabilidade)
// -----------------------------------------------------------------------
if ($acao === 'exportar_dados') {
    $stmt = $conn->prepare("SELECT u.nome_usuario, u.email, u.usuario_login, uv.statusConta FROM usuario u INNER JOIN usuario_verificacao uv ON uv.fk_usuario = u.id_usuario WHERE u.id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $dados = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$dados) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível recuperar seus dados.']);
        exit;
    }

    $exportacao = [
        'aviso_lgpd'      => 'Dados exportados em conformidade com a LGPD (Lei nº 13.709/2018), Art. 18.',
        'data_exportacao' => date('Y-m-d H:i:s'),
        'dados_pessoais'  => [
            'nome'          => $dados['nome_usuario'],
            'email'         => $dados['email'],
            'usuario_login' => $dados['usuario_login'],
            'status_conta'  => $dados['statusConta'],
        ],
    ];

    echo json_encode(['sucesso' => true, 'dados' => $exportacao]);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Deletar Conta (LGPD — Art. 18, direito ao esquecimento)
// -----------------------------------------------------------------------
if ($acao === 'deletar_conta') {
    $senha_delecao = $_POST['senha_delecao'] ?? '';

    if (empty($senha_delecao)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Informe sua senha para confirmar a exclusão.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT usuario_password FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || !password_verify($senha_delecao, $usuario['usuario_password'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta. A conta não foi excluída.']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    session_destroy();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Conta excluída com sucesso. Seus dados foram removidos.']);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Gerar QR Code para configurar 2FA
// -----------------------------------------------------------------------
if ($acao === 'gerar_qr_2fa') {
    $stmt = $conn->prepare("SELECT email, usuario_login FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$usuario) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }

    $google2fa = new Google2FA();
    $secret    = $google2fa->generateSecretKey();

    // Armazena temporariamente na sessão até confirmação
    $_SESSION['2fa_setup_secret'] = $secret;

    $qrCodeUrl = $google2fa->getQRCodeUrl('ONGs Browser', $usuario['email'], $secret);

    // Gera SVG do QR code
    $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
    $writer   = new Writer($renderer);
    $qrSvg    = $writer->writeString($qrCodeUrl);

    echo json_encode([
        'sucesso' => true,
        'qr_svg'  => $qrSvg,
        'secret'  => $secret,
    ]);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Ativar 2FA — confirma o código e salva o segredo
// -----------------------------------------------------------------------
if ($acao === 'ativar_2fa') {
    if (empty($_SESSION['2fa_setup_secret'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão de configuração expirada. Recarregue a página.']);
        exit;
    }

    $codigoEnviado = trim($_POST['codigo_totp'] ?? '');

    if (empty($codigoEnviado) || strlen($codigoEnviado) !== 6 || !ctype_digit($codigoEnviado)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código inválido.']);
        exit;
    }

    $google2fa = new Google2FA();
    $valido    = $google2fa->verifyKey($_SESSION['2fa_setup_secret'], $codigoEnviado);

    if (!$valido) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código incorreto. Certifique-se de que o QR code foi escaneado corretamente.']);
        exit;
    }

    $secret = $_SESSION['2fa_setup_secret'];
    unset($_SESSION['2fa_setup_secret']);

    $stmt = $conn->prepare("UPDATE usuario_verificacao SET codVerificador = ? WHERE fk_usuario = ?");
    $stmt->bind_param("si", $secret, $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['sucesso' => true, 'mensagem' => '2FA ativado com sucesso!']);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Desativar 2FA — verifica o código atual e remove o segredo
// -----------------------------------------------------------------------
if ($acao === 'desativar_2fa') {
    $codigoEnviado = trim($_POST['codigo_totp'] ?? '');

    if (empty($codigoEnviado) || strlen($codigoEnviado) !== 6 || !ctype_digit($codigoEnviado)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código inválido.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT codVerificador FROM usuario_verificacao WHERE fk_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || empty($usuario['codVerificador'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => '2FA não está ativado nesta conta.']);
        $conn->close();
        exit;
    }

    $google2fa = new Google2FA();
    $valido    = $google2fa->verifyKey($usuario['codVerificador'], $codigoEnviado);

    if (!$valido) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código incorreto.']);
        $conn->close();
        exit;
    }

    $null = null;
    $stmt = $conn->prepare("UPDATE usuario_verificacao SET codVerificador = NULL WHERE fk_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['sucesso' => true, 'mensagem' => '2FA desativado com sucesso.']);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Desconectar Telegram
// -----------------------------------------------------------------------
if ($acao === 'desconectar_telegram') {
    $stmt = $conn->prepare("UPDATE usuario_verificacao SET telegram_id = NULL, telegram_pass = NULL WHERE fk_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['sucesso' => true, 'mensagem' => 'Telegram desconectado com sucesso.']);
    exit;
}

// Ação desconhecida
echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
?>
