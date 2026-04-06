<?php
// Restringe para um unico ponto de entrada (controller) e define o tipo de resposta como JSON.
header('Content-Type: application/json; charset=utf-8');

// Método para garantir que este arquivo só seja acessado via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Recebimendo dos dados via POST
    $rawName = (isset($_POST['nome']) && is_string($_POST['nome'])) ? trim($_POST['nome']) : '';
    $rawEmail = (isset($_POST['email']) && is_string($_POST['email'])) ? trim($_POST['email']) : '';
    $rawUsuario = (isset($_POST['usuario']) && is_string($_POST['usuario'])) ? trim($_POST['usuario']) : '';
    $rawSenha = (isset($_POST['senha']) && is_string($_POST['senha'])) ? trim($_POST['senha']) : '';

    // Aplicando a Validação Regex aprendido em aula e verificado usando a ajuda da internet e ia como se valida no php o regEx
    $padraoSenha = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!empty($rawSenha) && !preg_match($padraoSenha, $rawSenha)) {
        echo json_encode([
            "sucesso" => false, 
            "mensagem" => "A senha deve ter no mínimo 8 caracteres, letra maiúscula, minúscula, número e caractere especial."
        ]);
        exit;
    }
    // Descobri que o htmlspecialchars pode quebrar a senha, por isso não vamos usar especifico para senha.
    $senhaCriptografada = password_hash($rawSenha, PASSWORD_DEFAULT);

    // Valida se os campos não estão vazios
    if (!empty($rawUsuario) && !empty($rawEmail) && !empty($rawNome) && !empty($rawSenha)) {
        try {
            // Gera o código e define o status
            $codigoVerificacao = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $statusConta = 'pendente'; 
            $sql = "INSERT INTO usuarios (nome, email, usuario, senha, statusConta, codVerificador) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssss", 
                $rawNome, 
                $rawEmail, 
                $rawUsuario, 
                $senhaCriptografada, 
                $statusConta, 
                $codigoVerificacao
            );
            $stmt->execute();
            
            $resposta = [
                "sucesso" => true,
                "mensagem" => "Cadastro realizado com sucesso! Verifique seu e-mail para ativar a conta."
            ];
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $resposta = ["sucesso" => false, "mensagem" => "Erro: Usuário ou E-mail já estão cadastrados."];
            } else {
                $resposta = ["sucesso" => false, "mensagem" => "Erro interno."];
            }
        }

    } else {
        $resposta = [
            "sucesso" => false,
            "mensagem" => "Dados inválidos. Preencha todos os campos corretamente."
        ];
    }

    echo json_encode($resposta);

} else {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Acesso negado."
    ]);
}
?>