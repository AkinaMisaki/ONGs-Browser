<?php

include __DIR__ . '/../../config/configuni.php';

// Buscar ONGs aleatórias
$sql = "SELECT id_ong, nome_ong, descricao FROM ong ORDER BY RAND() LIMIT 5";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONGs Browser</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<h1>Teste de ONGs Aleatórias</h1>
<a href="view/login.php">Login</a>
<br>
<a href="view/registrar.php">Registrar</a>
<div class='ongs-container'>
<?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div class='ongs-result'>";
        echo "<h2>" . htmlspecialchars($row["nome_ong"]) . "</h2>";
        echo "<p>" . htmlspecialchars($row["descricao"]) . "</p>";
        echo "</div>";
    }
} else {
    echo "Nenhuma ONG encontrada.";
}

$conn->close();
?>
</div>
</body>
</html>