<?php
require_once __DIR__ . '/../controller/searchController.php';

$query = $_GET['q'] ?? '';
$ongs = searchONGs($query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buscar ONGs</title>
<link rel="stylesheet" href="css/pesquisar.css">
</head>
<body>

<?php include __DIR__ . '/reusable/header.php'; ?>

<div class="search-container">
    <form id="searchForm">
        <input type="text" id="searchInput" value="<?php echo htmlspecialchars($query); ?>"placeholder="Buscar ONGs..." required
        >
        <button type="submit">Buscar</button>
    </form>
</div>

<div class="search-info">
    <p>Resultados para: <strong><?php echo '"' .htmlspecialchars($query) . '"'; echo " (" . htmlspecialchars(count($ongs)) . ")"; ?></strong></p>
</div>

<div class="results-grid">

<?php if (!empty($ongs)): ?>
    <?php foreach ($ongs as $ong): ?>
        <div class="card">
            <h2><?php echo htmlspecialchars($ong['nome_ong']); ?></h2>
            <p><?php echo htmlspecialchars($ong['descricao']); ?></p>
            <button onclick="goToONG(<?php echo $ong['id_ong']; ?>)">
                Ver mais
            </button>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="no-results">Nenhuma ONG encontrada.</p>
<?php endif; ?>

</div>

<script src="../js/pesquisar.js"></script>
</body>
</html>