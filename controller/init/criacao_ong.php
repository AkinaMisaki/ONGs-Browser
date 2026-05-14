<?php
include_once __DIR__ . '/../../config.php';

$proprietario = null;
if (!empty($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare(
        "SELECT p.id_prop, u.nome_usuario, u.email
         FROM proprietario_ong p
         JOIN usuario u ON u.id_usuario = p.fk_usuario
         WHERE p.fk_usuario = ?"
    );
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $proprietario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
