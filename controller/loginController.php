<?php
ini_set('display_errors', '0');
$meurastro = [];
include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado. Método incorreto."]);
    exit;
}

$rawUsuario = (isset($_POST['usuario']) && is_string($_POST['usuario'])) ? trim($_POST['usuario']) : '';
$rawSenha   = (isset($_POST['senha'])   && is_string($_POST['senha']))   ? trim($_POST['senha'])   : '';
$meurastro[] = "Dados recebidos: usuario='$rawUsuario', senha='[PROTEGIDA]'";

$usuarioSeguro = htmlspecialchars($rawUsuario, ENT_QUOTES, 'UTF-8');

if (empty($usuarioSeguro) || empty($rawSenha)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos."]);
    exit;
}

$sql = "SELECT id_usuario, usuario_login, usuario_password, statusConta, codVerificador 
        FROM usuario
        WHERE usuario_login = ?
        LIMIT 1";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuarioSeguro);
    $stmt->execute();
    $result  = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro interno: " . $e->getMessage()]);
    exit;
}

// Usuário não encontrado ou senha incorreta
if (!$usuario || !password_verify($rawSenha, $usuario['usuario_password'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Usuário ou senha incorretos."]);
    exit;
}

// Conta não ativada
if ((int) $usuario['statusConta'] !== 1) {
    echo json_encode(["sucesso" => false, "mensagem" => "Usuário inativo. Confirme seu email antes de continuar."]);
    exit;
}

session_start();

// 2FA configurado — exige verificação TOTP
if (!empty($usuario['codVerificador'])) {
    $_SESSION['2fa_pending_id']    = $usuario['id_usuario'];
    $_SESSION['2fa_pending_login'] = $usuario['usuario_login'];
    echo json_encode(["sucesso" => true, "acao" => "2fa_required"]);
    exit;
}

// 2FA não configurado — login direto
$_SESSION['usuario_id']    = $usuario['id_usuario'];
$_SESSION['usuario_login'] = $usuario['usuario_login'];

echo json_encode(["sucesso" => true, "mensagem" => "Acesso permitido!"]);
?>
