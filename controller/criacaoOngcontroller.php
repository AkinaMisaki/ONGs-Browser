<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // POST
    $rawOng = (isset($_POST['Ong']) && is_string($_POST['Ong'])) ? trim($_POST['Ong']) : '';
    $rawSigla = (isset($_POST['Sigla']) && is_string($_POST['Sigla'])) ? trim($_POST['Sigla']) : '';
    $rawDescricao = (isset($_POST['Descricao']) && is_string($_POST['Descricao'])) ? trim($_POST['Descricao']) : '';
    
    if (!empty($rawOng) && !empty($rawSigla) && !empty($rawDescricao)) {
        try {
            $sql = "INSERT INTO CriacaoOng (nome_Ong, Sigla_Ong, Descricao_Ong, caminho_arquivo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssss", 
                $rawOng, 
                $rawSigla, 
                $rawDescricao, 
                "caminho_Arquivo"
            );
            $stmt->execute();
            
            $resposta = [
                "sucesso" => true,
                "mensagem" => "Cadastro realizado com sucesso!"
            ];
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $resposta = ["sucesso" => false, "mensagem" => "Erro: Nome da Ong ou Sigla ja estão cadastrados."];
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