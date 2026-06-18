<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_POST['keyAESCifrada'], $_POST['msg_cifrada'], $_POST['iv'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Dados inválidos."]);
        exit;
    }

    $keyAESCifrada = base64_decode($_POST['keyAESCifrada']);
    $msgCifradaCompleta = base64_decode($_POST['msg_cifrada']);
    $iv = base64_decode($_POST['iv']);

    $pvkeyPath = '/var/www/config/private.pem';
    if (!file_exists($pvkeyPath)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Chave privada não encontrada."]);
        exit;
    }

    $pvkey = openssl_pkey_get_private(file_get_contents($pvkeyPath));
    if (!$pvkey) {
        echo json_encode(["sucesso" => false, "mensagem" => "Falha ao carregar chave privada RSA."]);
        exit;
    }

    if (!openssl_private_decrypt($keyAESCifrada, $chaveAES, $pvkey, OPENSSL_PKCS1_OAEP_PADDING)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Falha ao decifrar chave AES com RSA."]);
        exit;
    }

    $tag = substr($msgCifradaCompleta, -16);
    $ciphertext = substr($msgCifradaCompleta, 0, -16);
    $dadosDecodificadosJson = openssl_decrypt($ciphertext, 'aes-256-gcm', $chaveAES, OPENSSL_RAW_DATA, $iv, $tag);
    $dadosOriginais = json_decode($dadosDecodificadosJson, true);
    
    $rawOng = htmlspecialchars(trim($dadosOriginais['Ong'] ?? ''));
    $rawDescricao = htmlspecialchars(trim($dadosOriginais['Descricao'] ?? ''));
    $imagemBase64 = $dadosOriginais['imagem'] ?? '';

    $rawProprietarioId = 0;
    if (!empty($_SESSION['usuario_id'])) {
        $stmtProp = $conn->prepare("SELECT id_prop FROM proprietario_ong WHERE fk_usuario = ? LIMIT 1");
        $stmtProp->bind_param("i", $_SESSION['usuario_id']);
        $stmtProp->execute();
        $resProp = $stmtProp->get_result()->fetch_assoc();
        $stmtProp->close();
        if ($resProp) {
            $rawProprietarioId = (int) $resProp['id_prop'];
        }
    }

    $diretorioDestino = __DIR__ . '/../uploads/'; 
    if (!is_dir($diretorioDestino)) {
        mkdir($diretorioDestino, 0755, true);
    }
    
    if (!empty($rawOng) && !empty($rawDescricao) && !empty($imagemBase64) && $rawProprietarioId > 0) {
        
        list($tipoMime, $dadosImagem) = explode(';', $imagemBase64);
        list(, $dadosImagem) = explode(',', $dadosImagem);
        $dadosImagemDecodificados = base64_decode($dadosImagem);
        
        $extensao = 'png';
        if (strpos($tipoMime, 'jpeg') !== false || strpos($tipoMime, 'jpg') !== false) $extensao = 'jpg';
        if (strpos($tipoMime, 'webp') !== false) $extensao = 'webp';

        $novoNome = uniqid() . "." . $extensao; 
        
        $caminhoCompleto = $diretorioDestino . $novoNome;
        $caminhoBanco = 'uploads/' . $novoNome;

        if (file_put_contents($caminhoCompleto, $dadosImagemDecodificados)) {
            try {
                $sql = "INSERT INTO ong (nome_ong, descricao, caminho_arquivo, fk_proprietario_id) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $rawOng, $rawDescricao, $caminhoBanco, $rawProprietarioId);
                $stmt->execute();
                $novoId = $conn->insert_id;

                echo json_encode(["sucesso" => true, "mensagem" => "Cadastro e upload realizados com sucesso!", "id_ong" => $novoId]);
            } catch (mysqli_sql_exception $e) {
                if (file_exists($caminhoCompleto)) unlink($caminhoCompleto);
                if ($e->getCode() == 1062) {
                    echo json_encode(["sucesso" => false, "mensagem" => "Erro: Nome da ONG já cadastrado."]);
                } else {
                    echo json_encode(["sucesso" => false, "mensagem" => "Erro no banco de dados."]);
                }
            }
        } else {
            echo json_encode(["sucesso" => false, "mensagem" => "Erro ao mover o arquivo."]);
        }
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos ou verifique o login."]);
    }
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
}