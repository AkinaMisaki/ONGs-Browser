<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $codigo = $_POST['codigo'] ?? '';

    // Busca o usuário pelo e-mail
    $sql = "SELECT statusConta, codVerificador FROM usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['statusConta'] === 'ativo' || $row['statusConta'] === 'organizador') {
            echo json_encode(["sucesso" => false, "mensagem" => "Esta conta já foi ativada anteriormente!"]);
            exit;
        }

        // Se estiver pendente, checa o código
        if ($row['codVerificador'] === $codigo) {
            $update = $conn->prepare("UPDATE usuario SET statusConta = 'ativo' WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();

            echo json_encode(["sucesso" => true, "mensagem" => "Conta ativada com sucesso! Você já pode fazer login."]);
        } else {
            echo json_encode(["sucesso" => false, "mensagem" => "Código inválido."]);
        }
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Usuário não encontrado."]);
    }
}
?>