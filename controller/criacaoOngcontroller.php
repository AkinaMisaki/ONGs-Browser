<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rawOng = (isset($_POST['Ong'])) ? htmlspecialchars(trim($_POST['Ong'])) : '';
    $rawDescricao = (isset($_POST['Descricao'])) ? htmlspecialchars(trim($_POST['Descricao'])) : '';

    $diretorioDestino = __DIR__ . '/../uploads/'; 
    
    if (!is_dir($diretorioDestino)) {
        mkdir($diretorioDestino, 0755, true);
    }
    
    if (!empty($rawOng) && !empty($rawDescricao) && isset($_FILES['imagemOng'])) {
        
        $arquivo = $_FILES['imagemOng'];

        if ($arquivo['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            $novoNome = uniqid() . "." . $extensao; 
            $caminhoCompleto = $diretorioDestino . $novoNome;
            $caminhoBanco = '/universidade/uploads/' . $novoNome; 

            if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                try {
                    $sql = "INSERT INTO ong (nome_ong, descricao, caminho_arquivo) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $rawOng, $rawDescricao, $caminhoBanco);
                    $stmt->execute();
                    
                    $resposta = ["sucesso" => true, "mensagem" => "Cadastro e upload realizados com sucesso!"];
                } catch (mysqli_sql_exception $e) {
                    if (file_exists($caminhoCompleto)) unlink($caminhoCompleto);
                    if ($e->getCode() == 1062) {
                        $resposta = ["sucesso" => false, "mensagem" => "Erro: Nome já cadastrado."];
                    } else {
                        $resposta = ["sucesso" => false, "mensagem" => "Erro no banco de dados."];
                    }
                }
            } else {
                $resposta = ["sucesso" => false, "mensagem" => "Erro ao mover o arquivo."];
            }
        } else {
            $resposta = ["sucesso" => false, "mensagem" => "Erro no envio do arquivo."];
        }
    } else {
        $resposta = ["sucesso" => false, "mensagem" => "Preencha todos os campos."];
    }
    echo json_encode($resposta);
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
}