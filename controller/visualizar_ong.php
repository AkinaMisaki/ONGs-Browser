<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit();
}

$sql = "SELECT o.nome_ong, o.descricao, o.caminho_arquivo, u.nome_usuario
        FROM ong o
        JOIN proprietario_ong p ON p.id_prop = o.fk_proprietario_id
        JOIN usuario u ON u.id_usuario = p.fk_usuario
        WHERE o.id_ong = ?";
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
        'proprietario'    => db_decrypt($linha['nome_usuario'], $DB_ENCRYPT_KEY),
    ]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ONG não encontrada.']);
}

$stmt->close();
