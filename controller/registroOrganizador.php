<?php
session_start();
$meurastro = [];

header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rawcpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
    $rawrg = isset($_POST['rg']) ? trim($_POST['rg']) : '';
    $rawtelefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    
    $meurastro[] = "Dados recebidos: cpf='{$rawcpf}', rg='{$rawrg}', telefone='{$rawtelefone}'";

    if (!empty($rawcpf) && !empty($rawrg) && !empty($rawtelefone)) {
        try {
            
            if (isset($_SESSION['usuario_id'])) {
                $idUsuario = $_SESSION['usuario_id']; 
                

                $sql = "INSERT INTO proprietario_ong (cpf, rg, telefone, fk_usuario) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $rawcpf, $rawrg, $rawtelefone, $idUsuario);
                
                if ($stmt->execute()) {
                    $_SESSION['statusConta'] = 2; 

                    $sql = "
                        UPDATE usuario
                        SET statusConta = ?
                        WHERE id_usuario = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $_SESSION['statusConta'], $idUsuario);
                    $stmt->execute();
                    $stmt->close();
                    $conn->close();

                    $resposta = [
                        "sucesso" => true,
                        "mensagem" => "Parabéns! Cadastro de organizador realizado com sucesso."
                    ];
                } else {
                    $resposta = ["sucesso" => false, "mensagem" => "Erro ao salvar os dados no banco."];
                }
            } else {
                $resposta = [
                    "sucesso" => false, 
                    "mensagem" => "Sua sessão expirou. Por favor, faça login novamente."
                ];
            }

        } catch (mysqli_sql_exception $e) {
    $resposta = [
        "sucesso" => false,
        "mensagem" => "Erro no banco de dados.",
        "erro" => $e->getMessage()
    ];
    }

    } else {
        $resposta = [
            "sucesso" => false,
            "mensagem" => "Dados inválidos. Preencha todos os campos.",
            "debug" => $meurastro
        ];
    }

    echo json_encode($resposta);

} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
}
?>