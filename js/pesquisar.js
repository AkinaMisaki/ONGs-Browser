//Mudar para o link de view
function goToONG(id) {
    window.location.href = `visualizar_ong.php?id=${id}`;
}

// Script para lidar com a barra de pesquisa na página de resultados
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('searchForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const query = document.getElementById('searchInput').value.trim();
        window.location.href = `search.php?q=${encodeURIComponent(query)}`;
    });
});