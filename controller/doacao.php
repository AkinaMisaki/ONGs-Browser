<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../view/dashboard_comum.php");
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    die("Erro: Acesso não autorizado. Por favor, inicie sessão.");
}
require_once __DIR__ . '/conn/config.php';
$idUtilizador = $_SESSION['usuario_id'];
$nomeOng = trim($_POST['ong_nome'] ?? '');
$moedaOng = trim($_POST['ong_moeda'] ?? '');
$moedaUtilizador = trim($_POST['moeda_usuario'] ?? '');

// O filter_input garante que o valor seja tratado rigorosamente como um número de ponto flutuante (float)
$valorDoacao = filter_input(INPUT_POST, 'valor_doacao', FILTER_VALIDATE_FLOAT);
if (empty($nomeOng) || empty($moedaUtilizador) || $valorDoacao === false || $valorDoacao <= 0) {
    // Se tentarem manipular o HTML para enviar valores negativos ou vazios, o servidor barra aqui.
    echo "<script>
            alert('Erro na validação dos dados. Verifique os valores inseridos.');
            window.history.back();
          </script>";
    exit();
}
try {
    // Prepara a query SQL. Os pontos de interrogação (?) protegem contra injeção de código
    $sql = "INSERT INTO doacoes (usuario_id, ong_nome, moeda_utilizada, valor_doacao, status_doacao) 
            VALUES (?, ?, ?, ?, 'concluida')";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro na preparação da query: " . $conn->error);
    }
    $stmt->bind_param("issd", $idUtilizador, $nomeOng, $moedaUtilizador, $valorDoacao);
    
    if ($stmt->execute()) {
        echo "<script>
                alert('Doação processada com sucesso! Obrigado pelo seu apoio à $nomeOng.');
                window.location.href = '../view/doacoes_ong.php'; // Redireciona de volta ou para um histórico
              </script>";
    } else {
        throw new Exception("Erro ao executar a inserção: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    echo "<script>
            alert('Ocorreu um erro interno ao processar a sua doação. Tente novamente mais tarde.');
            window.history.back();
          </script>";
}

$conn->close();
?>