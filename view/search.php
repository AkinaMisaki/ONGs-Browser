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
        <input type="text" id="searchInput" value="<?php echo htmlspecialchars($query); ?>" placeholder="Buscar ONGs...">
        <button type="submit">Buscar</button>
    </form>
</div>

<div class="search-info">
    <?php if ($query !== ''): ?>
        <p>Resultados para: <strong>"<?php echo htmlspecialchars($query); ?>"</strong> (<?php echo count($ongs); ?>)</p>
    <?php else: ?>
        <p>Exibindo todas as ONGs (<?php echo count($ongs); ?>)</p>
    <?php endif; ?>
</div>

<div class="results-grid">

<?php if (!empty($ongs)): ?>
    <?php foreach ($ongs as $ong): ?>
        <?php
            $caminho = $ong['caminho_arquivo'] ?? '';
            if (empty($caminho)) {
                $src = '../uploads/placeholder.png';
            } elseif (preg_match('#^https?://#i', $caminho)) {
                $src = $caminho;
            } else {
                $src = '../' . ltrim($caminho, '/');
            }
        ?>
        <div class="card">
            <img class="card-thumb"
                 src="<?php echo htmlspecialchars($src); ?>"
                 alt="<?php echo htmlspecialchars($ong['nome_ong']); ?>">
            <div class="card-corpo">
                <div class="card-texto">
                    <h2><?php echo htmlspecialchars($ong['nome_ong']); ?></h2>
                    <p><?php echo htmlspecialchars($ong['descricao']); ?></p>
                </div>
                <button onclick="goToONG(<?php echo $ong['id_ong']; ?>)">Ver mais</button>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="no-results">Nenhuma ONG encontrada.</p>
<?php endif; ?>

</div>

<script src="../js/pesquisar.js"></script>
</body>
</html>