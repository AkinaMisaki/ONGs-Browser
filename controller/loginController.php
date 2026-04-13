<?php
$meurastro = [];
include __DIR__ . '/../conn/config.php';
// Restringe para um unico ponto de entrada (controller) e define o tipo de resposta como JSON.
header('Content-Type: application/json; charset=utf-8');

// Método para garantir que este arquivo só seja acessado via POST (quando o formulário for enviado) e não diretamente pela URL.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Primeira sessão do processo de receber os dados e cuidar com eles (validação e segurança).
    $rawUsuario = (isset($_POST['usuario']) && is_string($_POST['usuario'])) ? trim($_POST['usuario']) : '';



    $rawSenha = (isset($_POST['senha']) && is_string($_POST['senha'])) ? trim($_POST['senha']) : '';
    $meurastro[] = "Dados recebidos: usuario='$rawUsuario', senha='[PROTEGIDA]'";

    // Garante que os dados sejam tratados como strings e que espaços extras sejam removidos. Se não forem enviados, ficam vazios.
    $usuarioSeguro = htmlspecialchars($rawUsuario, ENT_QUOTES, 'UTF-8');

    //Ass:Max
    //Implementei uma conexão básica com o banco verificando o usuário e a senha direto sem nada, mas temos que implementar o hash logo.
    //Melhorar sobre esse sistema tbm

    // Implementar a tratativa de conexão com o banco de dados para puxar as credencias do usuário mandando o usuário, retornando a senha criptografada do banco, e comparar com a senha enviada pelo usuário usando password_verify() para validar o login.
    if (!empty($usuarioSeguro) && !empty($rawSenha)) {
        // Query para procurar um usuario
        $sql = "SELECT id_usuario, usuario_login, usuario_password, statusConta, usuario_2fa
        FROM usuario
        WHERE usuario_login = ? 
        LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuarioSeguro);
        $stmt->execute();

        if ($stmt->error) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Erro ao buscar usuário."
            ]);
            exit;
        }
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();

        if (!$usuario) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Usuário ou senha incorretos."
            ]);
            exit;
        }

        if ($usuario['statusConta'] < 0) {
            echo json_encode([
                "sucesso" => false,
                "mensagem" => "Usuário inativo. Confirme seu email antes de continuar."
            ]);
            exit;
        }


        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ];
        $senhaCriptografada = password_hash($rawSenha, PASSWORD_ARGON2ID, $options);

        if ($usuario && password_verify($rawSenha, $usuario['usuario_password'])) {
            session_start();

            if (!empty($usuario['usuario_2fa'])) {
                $_SESSION['2fa_pendente'] = true;
                $_SESSION['2fa_secret'] = $usuario['usuario_2fa'];
                $_SESSION['temp_usuario_id'] = $usuario['id_usuario'];
                $_SESSION['temp_usuario_login'] = $usuario['usuario_login'];
                $_SESSION['temp_statusConta'] = $usuario['statusConta'];

                $resposta = [
                    "sucesso" => true,
                    "2fa" => true,
                    "redirect" => "/ONGs-Browser/controller/2fa/verificar.php"
                ];
            } else {
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_login'] = $usuario['usuario_login'];
                $_SESSION['statusConta'] = $usuario['statusConta'];

                $resposta = [
                    "sucesso" => true,
                    "2fa" => false,
                    "redirect" => "/ONGs-Browser/index.php"
                ];
            }

        } else {

            $resposta = [
                "sucesso" => false,
                "mensagem" => "Usuário ou senha incorretos."
            ];

        }
    }

    // Retornamos no formato JSON para o JavaScript que fez a requisição, para que ele possa mostrar a mensagem certa para o usuário.
    echo json_encode($resposta);

} else {

    // Se alguém tentar acessar digitando direto na URL (método GET), será bloqueado e receberá uma mensagem de acesso negado.
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Acesso negado. Metodo incorreto."
    ]);
}
?>