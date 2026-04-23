// Carrossel
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

    // Center a card in the visible area on load
    const visibleWidth = grid.offsetWidth;
    const cardW = cards[0].offsetWidth;
    const centerAdjust = Math.round((visibleWidth / 2) - (cardW / 2));

    let current = total - centerAdjust;
    let target  = current;
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

// Busca
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('searchForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const query = document.getElementById('searchInput').value.trim();

        const isInViewFolder = window.location.pathname.includes('/view/');

        const path = isInViewFolder
            ? `search.php?q=${encodeURIComponent(query)}`
            : `view/search.php?q=${encodeURIComponent(query)}`;

        window.location.href = path;
    });
});

// Navegar para página da ONG
function goToONG(id) {
    window.location.href = `view/visualizar_ong.php?id=${id}`;
}