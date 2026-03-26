<?php
include __DIR__ . '/../../config/configuni.php';

$sql = "SELECT id_ong, nome_ong, descricao FROM ong ORDER BY RAND() LIMIT 20";
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
    <div class="site-description">
        <p>O <strong>ONGs Browser</strong> é uma plataforma brasileira dedicada a dar visibilidade a organizações não governamentais, apresentando-as em uma interface moderna e intuitiva. O site exibe uma seleção de ONGs diretamente do banco de dados, permitindo que os visitantes naveguem rapidamente pelos nomes e descrições das organizações. Com um design em tons de azul, cards animados e navegação simplificada, o ONGs Browser torna a descoberta de causas sociais uma experiência acessível e agradável. Com acesso rápido a login e cadastro, a plataforma convida os usuários a irem além da navegação e se tornarem participantes ativos no ecossistema de impacto social.</p>
    </div>

    <div class="carousel-wrapper">
        <button class="carousel-btn left" onclick="scrollCardsLeft()">◀</button>
        <div class="grid">

        <?php
        $delay = 0;

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<div class='card' style='animation-delay: {$delay}s'>";
                echo "<h2>" . htmlspecialchars($row['nome_ong']) . "</h2>";
                echo "<p>" . htmlspecialchars($row['descricao']) . "</p>";
                echo "<button>Ver mais</button>";
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.querySelector('.grid');
    if (!grid) return;

    const cards = Array.from(grid.children);
    if (cards.length === 0) return;

    const gap = 20;
    const cardWidth = cards[0].offsetWidth + gap;
    const total = cardWidth * cards.length;

    function makeClones(nodes) {
        return nodes.map(card => {
            const clone = card.cloneNode(true);
            clone.style.animation = 'none';
            clone.style.opacity = '1';
            return clone;
        });
    }

    const leftFrag = document.createDocumentFragment();
    makeClones(cards).forEach(c => leftFrag.appendChild(c));
    grid.insertBefore(leftFrag, grid.firstChild);

    makeClones(cards).forEach(c => grid.appendChild(c));

    grid.style.overflow = 'hidden';
    grid.style.scrollBehavior = 'auto';

    let current = total;
    let target  = total;
    grid.scrollLeft = current;

    let rafId = null;

    function tick() {
        const diff = target - current;

        if (Math.abs(diff) < 0.5) {
            current = target;
            rafId = null;
        } else {
            current += diff * 0.12;
            rafId = requestAnimationFrame(tick);
        }

        if (current < total) {
            current += total;
            target  += total;
        } else if (current >= total * 2) {
            current -= total;
            target  -= total;
        }

        grid.scrollLeft = current;
    }

    function scrollBy(amount) {
        target += amount;
        if (!rafId) rafId = requestAnimationFrame(tick);
    }

    window.scrollCardsLeft  = () => scrollBy(-cardWidth);
    window.scrollCardsRight = () => scrollBy( cardWidth);
});
</script>
</html>