<?php
// Restringe para um unico ponto de entrada (controller) e define o tipo de resposta como JSON.
$meurastro = [];

header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

// Método para garantir que este arquivo só seja acessado via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Recebimendo dos dados via POST
    $rawNome = (isset($_POST['nome']) && is_string($_POST['nome'])) ? trim($_POST['nome']) : '';
    $rawEmail = (isset($_POST['email']) && is_string($_POST['email'])) ? trim($_POST['email']) : '';
    $rawUsuario = (isset($_POST['usuario']) && is_string($_POST['usuario'])) ? trim($_POST['usuario']) : '';
    $rawSenha = (isset($_POST['senha']) && is_string($_POST['senha'])) ? trim($_POST['senha']) : '';
    $meurastro[] = "Dados recebidos: nome='$rawNome', email='$rawEmail', usuario='$rawUsuario', senha='[PROTEGIDA]'";

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
   
    // hash de senha nova Ass: gui
    $options = [
    'memory_cost' => 65536, 
    'time_cost'   => 4,  
    'threads'     => 2,   ];
    $senhaCriptografada = password_hash($rawSenha, PASSWORD_ARGON2ID, $options);
    $meurastro[] = "Senha criptografada com sucesso, $senhaCriptografada.";

    // Valida se os campos não estão vazios
    if (!empty($rawUsuario) && !empty($rawEmail) && !empty($rawNome) && !empty($rawSenha)) {
        try {
            // Gera o código e define o status
            $codigoVerificacao = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $statusConta = 'pendente'; 
            $meurastro[] = "Código de verificação gerado: $codigoVerificacao.";
            $sql = "INSERT INTO usuario (nome_usuario, email, usuario_login, usuario_password, statusConta, codVerificador) VALUES (?, ?, ?, ?, ?, ?)";
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

            // $linkAtivacao = "https://hanafuda.moe/universidade/controller/ativar_conta.php?email=" . urlencode($rawEmail) . "&codigo=" . $codigoVerificacao;
            
            // $assuntoEmail = "Ative sua conta no ONGs Browser";
            // $corpoEmail = "
            //     <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
            //         <h2 style='color: #0056b3;'>Bem-vindo(a), $rawNome!</h2>
            //         <p>Obrigado por se cadastrar. Para liberar seu acesso, por favor clique no botão abaixo para confirmar seu e-mail:</p>
            //         <div style='text-align: center; margin: 30px 0;'>
            //             <a href='$linkAtivacao' style='background-color: #0056b3; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
            //                 Ativar Minha Conta
            //             </a>
            //         </div>
            //         <p>Ou copie e cole o código de verificação manualmente: <strong>$codigoVerificacao</strong></p>
            //     </div>
            // ";

            // enviarEmailSistema($rawEmail, $assuntoEmail, $corpoEmail);
            
            $resposta = [
                "sucesso" => true,
                "mensagem" => "Cadastro realizado com sucesso! Verifique seu e-mail para ativar a conta."
                "debug" => $meurastro
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
            "debug" => $meurastro
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