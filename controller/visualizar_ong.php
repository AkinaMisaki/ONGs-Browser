<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit();
}

$sql = "SELECT nome_ong, descricao, caminho_arquivo FROM ong WHERE id_ong = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao preparar a consulta.']);
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($linha = $resultado->fetch_assoc()) {
    echo json_encode([
        'sucesso'         => true,
        'nome_ong'        => $linha['nome_ong'],
        'descricao'       => $linha['descricao'],
        'caminho_arquivo' => $linha['caminho_arquivo'],
    ]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ONG não encontrada.']);
}

$stmt->close();
