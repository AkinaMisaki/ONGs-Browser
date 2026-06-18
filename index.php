<?php
session_start();
include __DIR__ . '/config.php';

$sql = "SELECT id_ong, nome_ong, descricao, caminho_arquivo FROM ong ORDER BY RAND() LIMIT 20";
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

<?php include 'view/reusable/header.php'; ?>

<div class="container">
    <div class="site-description">
        <p>O <strong>ONGs Browser</strong> é uma plataforma brasileira dedicada a dar visibilidade a organizações não governamentais, apresentando-as em uma interface moderna e intuitiva. O site exibe uma seleção de ONGs diretamente do banco de dados, permitindo que os visitantes naveguem rapidamente pelos nomes e descrições das organizações. Com um design em tons de azul, cards animados e navegação simplificada, o ONGs Browser torna a descoberta de causas sociais uma experiência acessível e agradável. Com acesso rápido a login e cadastro, a plataforma convida os usuários a irem além da navegação e se tornarem participantes ativos no ecossistema de impacto social.</p>
    </div>

    <div class="search-container">
    <form id="searchForm">
        <input 
            type="text" 
            id="searchInput" 
            placeholder="Buscar ONGs..." 
        >
        <button type="submit">Buscar</button>
    </form>
    </div>
    <div class="carousel-wrapper">
        <button class="carousel-btn left" onclick="scrollCardsLeft()">◀</button>
        <div class="grid">

        <?php
        $delay = 0;

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $caminho = $row['caminho_arquivo'] ?? '';
                if (empty($caminho)) {
                    $src = 'uploads/placeholder.png';
                } elseif (preg_match('#^https?://#i', $caminho)) {
                    $src = htmlspecialchars($caminho);
                } else {
                    $src = htmlspecialchars(ltrim($caminho, '/'));
                }
                $alt = htmlspecialchars($row['nome_ong']);
                $imgHtml = "<img class=\"card-thumb\" src=\"{$src}\" alt=\"{$alt}\">";

                echo "<div class='card' style='animation-delay: {$delay}s'>";
                echo $imgHtml;
                echo "<div class='card-corpo'>";
                echo "<div class='card-texto'>";
                echo "<h2>" . htmlspecialchars($row['nome_ong']) . "</h2>";
                echo "<p>" . htmlspecialchars($row['descricao']) . "</p>";
                echo "</div>";
                echo "<button onclick=\"goToONG(" . $row['id_ong'] . ")\">Ver mais</button>";
                echo "</div>";
                echo "</div>";

                $delay += 0.1;
            }
        } else {
            echo "Nenhuma ONG encontrada.";
        }

        $conn->close();
        ?>

        </div>
        <button class="carousel-btn right" onclick="scrollCardsRight()">▶</button>
</div>

<footer class="footer">
    <p>© 2026 ONGs Browser</p>
</footer>

</body>
<script src="js/index.js"></script>
</html>