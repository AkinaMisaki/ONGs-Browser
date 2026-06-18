<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';
require_once __DIR__ . '/check_banned_ip.php';

checkBannedIp($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_POST['keyAESCifrada'], $_POST['msg_cifrada'], $_POST['iv'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Dados inválidos ou não criptografados."]);
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


    $rawcpf = isset($dadosOriginais['cpf']) ? trim($dadosOriginais['cpf']) : '';
    $rawrg = isset($dadosOriginais['rg']) ? trim($dadosOriginais['rg']) : '';
    $rawtelefone = isset($dadosOriginais['telefone']) ? trim($dadosOriginais['telefone']) : '';


    $regexCpf = '/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/';
    $regexRg  = '/^\d{1,2}\.?\d{3}\.?\d{3}-?[\dXx]$/';
    $regexTel = '/^\(?\d{2}\)?[\s\-]?\d{4,5}-?\d{4}$/';

    if (!preg_match($regexCpf, $rawcpf)) {
        echo json_encode(["sucesso" => false, "mensagem" => "CPF inválido."]);
        exit();
    }
    if (!preg_match($regexRg, $rawrg)) {
        echo json_encode(["sucesso" => false, "mensagem" => "RG inválido."]);
        exit();
    }
    if (!preg_match($regexTel, $rawtelefone)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Telefone inválido."]);
        exit();
    }


    if (!empty($rawcpf) && !empty($rawrg) && !empty($rawtelefone)) {
        
        if (!isset($DB_ENCRYPT_KEY) || $DB_ENCRYPT_KEY === null) {
            echo json_encode(["sucesso" => false, "mensagem" => "Chave de criptografia do BD não configurada. Execute generate_db_key.php."]);
            exit();
        }
        
        try {
            if (isset($_SESSION['usuario_id'])) {
                $idUsuario = $_SESSION['usuario_id'];

                $cpfCifrado      = aes_encrypt($rawcpf,      $DB_ENCRYPT_KEY);
                $rgCifrado       = aes_encrypt($rawrg,       $DB_ENCRYPT_KEY);
                $telefoneCifrado = aes_encrypt($rawtelefone, $DB_ENCRYPT_KEY);

                $sql = "INSERT INTO proprietario_ong (cpf, rg, telefone, fk_usuario) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $cpfCifrado, $rgCifrado, $telefoneCifrado, $idUsuario);
                
                if ($stmt->execute()) {
                    $_SESSION['statusConta'] = 2; 
                    
                    $sql_update = "UPDATE usuario_verificacao SET statusConta = ? WHERE fk_usuario = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("ii", $_SESSION['statusConta'], $idUsuario);
                    $stmt_update->execute();
                    $stmt_update->close();
                    
                    echo json_encode([
                        "sucesso" => true,
                        "mensagem" => "Parabéns! Cadastro de organizador realizado com sucesso."
                    ]);
                } else {
                    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao salvar os dados no banco."]);
                }
                $stmt->close();
            } else {
                echo json_encode(["sucesso" => false, "mensagem" => "Sua sessão expirou. Por favor, faça login novamente."]);
            }
        } catch (mysqli_sql_exception $e) {
            echo json_encode(["sucesso" => false, "mensagem" => "Erro no banco de dados.", "erro" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos corretamente."]);
    }
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
}
?>