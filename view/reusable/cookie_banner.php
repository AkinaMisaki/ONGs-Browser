<?php if (!isset($_COOKIE['lgpd_consent'])): ?>
<div id="cookie-banner" class="cookie-banner" role="dialog" aria-label="Aviso de cookies">
    <div class="cookie-banner__content">
        <p>
            Este site utiliza cookies essenciais para o funcionamento da plataforma.
            Em conformidade com a <strong>LGPD (Lei nº 13.709/2018)</strong>, solicitamos
            seu consentimento para o uso de cookies analíticos e de preferência.
        </p>
    </div>
    <div class="cookie-banner__actions">
        <button class="cookie-banner__btn cookie-banner__btn--recusar" onclick="lgpdResponder('declined')">
            Recusar
        </button>
        <button class="cookie-banner__btn cookie-banner__btn--aceitar" onclick="lgpdResponder('accepted')">
            Aceitar cookies
        </button>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #1a1a2e;
    color: #e0e0e0;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    z-index: 9999;
    box-shadow: 0 -3px 12px rgba(0,0,0,0.35);
    flex-wrap: wrap;
}

.cookie-banner__content p {
    margin: 0;
    font-size: 0.88rem;
    line-height: 1.6;
    max-width: 780px;
}

.cookie-banner__content strong {
    color: #90caf9;
}

.cookie-banner__actions {
    display: flex;
    gap: 0.75rem;
    flex-shrink: 0;
}

.cookie-banner__btn {
    padding: 0.55rem 1.25rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s, transform 0.1s;
}

.cookie-banner__btn:active {
    transform: scale(0.97);
}

.cookie-banner__btn--recusar {
    background: transparent;
    color: #aaa;
    border: 1px solid #555;
}

.cookie-banner__btn--recusar:hover {
    background: #2a2a3e;
    color: #ddd;
}

.cookie-banner__btn--aceitar {
    background: #0d47a1;
    color: #fff;
}

.cookie-banner__btn--aceitar:hover {
    background: #1565c0;
}

@media (max-width: 600px) {
    .cookie-banner {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .cookie-banner__actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
function lgpdResponder(choice) {
    var maxAge = 60 * 60 * 24 * 365;
    document.cookie = 'lgpd_consent=' + choice + '; max-age=' + maxAge + '; path=/; SameSite=Lax';
    document.getElementById('cookie-banner').style.display = 'none';
}
</script>
<?php endif; ?>
