<?php
require_once __DIR__ . '/../conn/config.php';

function searchONGs($query) {
    global $conn;

    $query = trim($query);

    if ($query === '') {
        $sql = "SELECT id_ong, nome_ong, descricao FROM ong LIMIT 50"; //Ver depois se fica melhor sem limite, ou se é melhor fazer uma paginação
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT id_ong, nome_ong, descricao 
                FROM ong 
                WHERE nome_ong LIKE ? 
                   OR descricao LIKE ?
                LIMIT 50";
        $stmt = $conn->prepare($sql);
        $search = "%{$query}%";
        $stmt->bind_param("ss", $search, $search);
    }

    if (!$stmt) {
        die("Erro SQL: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $ongs = [];
    while ($row = $result->fetch_assoc()) {
        $ongs[] = $row;
    }


    $stmt->close();

    return $ongs;
}
?>