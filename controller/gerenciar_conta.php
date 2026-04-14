<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

// Verifica sessão
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'nao_autenticado']);
    exit;
}

// Verifica CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'invalid_csrf']);
    exit;
}

include __DIR__ . '/../conn/config.php';

$id_usuario = (int) $_SESSION['usuario_id'];
$acao = trim($_POST['acao'] ?? '');

// -----------------------------------------------------------------------
// AÇÃO: Alterar Nome
// -----------------------------------------------------------------------
if ($acao === 'alterar_nome') {
    $novo_nome = trim($_POST['novo_nome'] ?? '');

    if (empty($novo_nome)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O nome não pode ficar em branco.']);
        exit;
    }

    if (strlen($novo_nome) > 120) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O nome deve ter no máximo 120 caracteres.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuario SET nome_usuario = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $novo_nome, $id_usuario);
    $stmt->execute();

    if ($stmt->affected_rows < 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar o nome. Tente novamente.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Nome atualizado com sucesso!', 'novo_nome' => htmlspecialchars($novo_nome)]);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Alterar Senha
// -----------------------------------------------------------------------
if ($acao === 'alterar_senha') {
    $senha_atual   = $_POST['senha_atual']    ?? '';
    $nova_senha    = $_POST['nova_senha']     ?? '';
    $confirmar     = $_POST['confirmar_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos.']);
        exit;
    }

    if ($nova_senha !== $confirmar) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A nova senha e a confirmação não coincidem.']);
        exit;
    }

    $padraoSenha = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!preg_match($padraoSenha, $nova_senha)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter no mínimo 8 caracteres, letra maiúscula, minúscula, número e caractere especial.']);
        exit;
    }

    // Busca senha atual do banco para verificar
    $stmt = $conn->prepare("SELECT usuario_password FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if (!$usuario || !password_verify($senha_atual, $usuario['usuario_password'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha atual incorreta.']);
        $conn->close();
        exit;
    }

    $options = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_ARGON2ID, $options);

    $stmt = $conn->prepare("UPDATE usuario SET usuario_password = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $nova_senha_hash, $id_usuario);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar a senha. Tente novamente.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso!']);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Exportar Dados (LGPD — Art. 18, portabilidade)
// -----------------------------------------------------------------------
if ($acao === 'exportar_dados') {
    $stmt = $conn->prepare("SELECT nome_usuario, email, usuario_login, statusConta FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$dados) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível recuperar seus dados.']);
        exit;
    }

    $exportacao = [
        'aviso_lgpd'     => 'Dados exportados em conformidade com a LGPD (Lei nº 13.709/2018), Art. 18.',
        'data_exportacao' => date('Y-m-d H:i:s'),
        'dados_pessoais' => [
            'nome'           => $dados['nome_usuario'],
            'email'          => $dados['email'],
            'usuario_login'  => $dados['usuario_login'],
            'status_conta'   => $dados['statusConta'],
            'data_criacao'   => 'Não disponível',
        ],
    ];

    echo json_encode(['sucesso' => true, 'dados' => $exportacao]);
    exit;
}

// -----------------------------------------------------------------------
// AÇÃO: Deletar Conta (LGPD — Art. 18, direito ao esquecimento)
// -----------------------------------------------------------------------
if ($acao === 'deletar_conta') {
    $senha_delecao = $_POST['senha_delecao'] ?? '';

    if (empty($senha_delecao)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Informe sua senha para confirmar a exclusão.']);
        exit;
    }

    // Verifica a senha antes de deletar
    $stmt = $conn->prepare("SELECT usuario_password FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if (!$usuario || !password_verify($senha_delecao, $usuario['usuario_password'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta. A conta não foi excluída.']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir a conta. Tente novamente.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();
    $conn->close();

    session_destroy();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Conta excluída com sucesso. Seus dados foram removidos.']);
    exit;
}

// Ação desconhecida
echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
?>
