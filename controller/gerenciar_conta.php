<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION['usuario_id'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso não autorizado."]);
    exit;
}
$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['usuario_id'];
if ($action === 'atualizar_credenciais') {
    $novoUsuario = trim($_POST['usuario'] ?? '');
    $novaSenha = trim($_POST['senha'] ?? '');
    if (empty($novoUsuario)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Usuário inválido."]);
        exit;
    }
    if (!empty($novaSenha)) {
        $options = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
        $senhaHash = password_hash($novaSenha, PASSWORD_ARGON2ID, $options);
        $sql = "UPDATE usuario SET usuario_login = ?, usuario_password = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $novoUsuario, $senhaHash, $id_usuario);
    } else {
        $sql = "UPDATE usuario SET usuario_login = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $novoUsuario, $id_usuario);
    }
    try {
        $stmt->execute();
        $_SESSION['usuario_login'] = $novoUsuario;
        echo json_encode(["sucesso" => true, "mensagem" => "Dados atualizados com sucesso!"]);
    } catch (Exception $e) {
        echo json_encode(["sucesso" => false, "mensagem" => "O nome de usuário já está em uso."]);
    }
} elseif ($action === 'virar_organizador') {
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if (empty($cpf) || empty($rg) || empty($telefone)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos."]);
        exit;
    }
    $conn->begin_transaction();
    try {
        $sqlInsert = "INSERT INTO organizador (fk_usuario, cpf, rg, telefone) VALUES (?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("isss", $id_usuario, $cpf, $rg, $telefone);
        $stmtInsert->execute();
        $sqlUpdate = "UPDATE usuario SET statusConta = 'organizador' WHERE id_usuario = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id_usuario);
        $stmtUpdate->execute();

        $conn->commit();
        echo json_encode(["sucesso" => true, "mensagem" => "Acesso de Organizador liberado!"]);
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo json_encode(["sucesso" => false, "mensagem" => "Erro. Dados já cadastrados."]);
    }
} elseif ($action === 'excluir_conta') {
    $conn->begin_transaction();
    try {
        $stmtOrg = $conn->prepare("DELETE FROM organizador WHERE fk_usuario = ?");
        $stmtOrg->bind_param("i", $id_usuario);
        $stmtOrg->execute();
        $stmtUser = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmtUser->bind_param("i", $id_usuario);
        $stmtUser->execute();

        $conn->commit();
        session_unset();
        session_destroy();

        echo json_encode(["sucesso" => true, "mensagem" => "Conta excluída permanentemente."]);
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao tentar excluir a conta."]);
    }
}
?>