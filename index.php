<?php
include __DIR__ . '/../../config/configuni.php';

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

<header class="header">
    <h1>ONGs Browser</h1>
    <nav>
        <a href="view/login.php">Login</a>
        <a href="view/registrar.php">Registrar</a>
    </nav>
</header>

<div class="container">
    <div class="grid">

    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<div class='card'>";
            echo "<h2>" . htmlspecialchars($row['nome_ong']) . "</h2>";
            echo "<p>" . htmlspecialchars($row['descricao']) . "</p>";
            echo "<button>Ver mais</button>";
            echo "</div>";
        }
    } else {
        echo "Nenhuma ONG encontrada.";
    }

    $conn->close();
    ?>

    </div>
</div>

<footer class="footer">
    <p>© 2026 ONGs Browser</p>
</footer>

</body>
</html>