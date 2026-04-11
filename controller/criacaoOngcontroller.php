<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rawOng = (isset($_POST['Ong']) && is_string($_POST['Ong'])) ? trim($_POST['Ong']) : '';
    $rawSigla = (isset($_POST['Sigla']) && is_string($_POST['Sigla'])) ? trim($_POST['Sigla']) : '';
    $rawDescricao = (isset($_POST['Descricao']) && is_string($_POST['Descricao'])) ? trim($_POST['Descricao']) : '';

    $diretorioDestino = __DIR__ . '/../uploads/'; 
    
    if (!is_dir($diretorioDestino)) {
        mkdir($diretorioDestino, 0775, true);
    }

    if (!empty($rawOng) && !empty($rawSigla) && !empty($rawDescricao) && isset($_FILES['imagemOng'])) {
        
        $arquivo = $_FILES['imagemOng'];

        if ($arquivo['error'] === UPLOAD_ERR_OK)
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            $novoNome = uniqid() . "." . $extensao; 
            $caminhoCompleto = $diretorioDestino . $novoNome;
            $caminhoBanco = "uploads/" . $novoNome; 

            if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                try {
                    $sql = "INSERT INTO CriacaoOng (nome_Ong, Sigla_Ong, Descricao_Ong, caminho_arquivo) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "ssss", 
                        $rawOng, 
                        $rawSigla, 
                        $rawDescricao, 
                        $caminhoBanco
                    );
                    $stmt->execute();
                    
                    $resposta = ["sucesso" => true, "mensagem" => "Cadastro e upload realizados com sucesso!"];
                } catch (mysqli_sql_exception $e) {
                    unlink($caminhoCompleto);
                    if ($e->getCode() == 1062) {
                        $resposta = ["sucesso" => false, "mensagem" => "Erro: Nome ou Sigla já cadastrados."];
                    } else {
                        $resposta = ["sucesso" => false, "mensagem" => "Erro no banco de dados."];
                    }
                }
            } else {
                $resposta = ["sucesso" => false, "mensagem" => "Erro ao mover o arquivo para a pasta de destino."];
            }
        } else {
            $resposta = ["sucesso" => false, "mensagem" => "Erro no envio do arquivo."];
        }

    } else {
        $resposta = ["sucesso" => false, "mensagem" => "Preencha todos os campos e selecione uma imagem."];
    }

    echo json_encode($resposta);
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
}
?>