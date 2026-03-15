<?php
// Restringe para um unico ponto de entrada (controller) e define o tipo de resposta como JSON.
header('Content-Type: application/json; charset=utf-8');

// Método para garantir que este arquivo só seja acessado via POST (quando o formulário for enviado) e não diretamente pela URL.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Primeira sessão do processo de receber os dados e cuidar com eles (validação e segurança).
    $rawUsuario = (isset($_POST['usuario']) && is_string($_POST['usuario'])) ? trim($_POST['usuario']) : '';
    $rawSenha = (isset($_POST['senha']) && is_string($_POST['senha'])) ? trim($_POST['senha']) : '';

    // Garante que os dados sejam tratados como strings e que espaços extras sejam removidos. Se não forem enviados, ficam vazios.
    $usuarioSeguro = htmlspecialchars($rawUsuario, ENT_QUOTES, 'UTF-8');

    // Implementar a tratativa de conexão com o banco de dados para puxar as credencias do usuário mandando o usuário, retornando a senha criptografada do banco, e comparar com a senha enviada pelo usuário usando password_verify() para validar o login.
    if (!empty($usuarioSeguro) && !empty($rawSenha)) {

        // Se tudo estiver certo, preparamos uma resposta de SUCESSO.
        $resposta = [
            "sucesso" => true,
            "mensagem" => "Acesso permitido!"
        ];

    } else {
        // Se algo falhou (ex: tentaram burlar o JS e mandar vazio), preparamos resposta de ERRO.
        $resposta = [
            "sucesso" => false,
            "mensagem" => "Dados inválidos. Preencha todos os campos corretamente."
        ];
    }

    // Retornamos no formato JSON para o JavaScript que fez a requisição, para que ele possa mostrar a mensagem certa para o usuário.
    echo json_encode($resposta);

} else {
    
    // Se alguém tentar acessar digitando direto na URL (método GET), será bloqueado e receberá uma mensagem de acesso negado.
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Acesso negado. Método incorreto."
    ]);
}
?>