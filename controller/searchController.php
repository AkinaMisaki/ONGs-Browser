<?php
require_once __DIR__ . '/../../../config/configuni.php';

function searchONGs($query) {
    global $conn;

    $query = trim($query);
    if ($query === '') return [];

    $sql = "SELECT id_ong, nome_ong, descricao
            FROM ong 
            WHERE nome_ong LIKE ? 
               OR descricao LIKE ?
            LIMIT 50";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro SQL: " . $conn->error);
    }

    $search = "%{$query}%";
    $stmt->bind_param("ss", $search, $search);

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