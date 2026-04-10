<?php
/**
 * Guarda de sessão do admin — inclua no topo de toda página protegida.
 * Se o usuário não estiver autenticado, redireciona pro login automaticamente.
 */

// Configura cookies de sessão mais seguros antes de iniciar
// httponly: impede acesso pelo JavaScript (proteção contra XSS)
// samesite Strict: bloqueia envio do cookie em requisições de outros sites (proteção CSRF)
// use_strict_mode: rejeita IDs de sessão não iniciados pelo servidor
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tempo máximo de inatividade antes de expirar a sessão (30 minutos)
define('ADMIN_IDLE_TIMEOUT', 30 * 60); //Arrumar timeout

// ── Logout ─────────────────────────────────────────────────────────────────
// Quando o usuário clica em "Sair", destrói a sessão completamente e volta pro login
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// ── Não autenticado ────────────────────────────────────────────────────────
// Se não tiver a flag de login na sessão, bloqueia o acesso
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// ── Timeout por inatividade ────────────────────────────────────────────────
// Verifica quanto tempo passou desde a última atividade do usuário.
// Se passou do limite, encerra a sessão e manda pro login com aviso.
if (isset($_SESSION['admin_last_activity'])) {
    if ((time() - $_SESSION['admin_last_activity']) > ADMIN_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: admin_login.php?timeout=1');
        exit;
    }
}

// Atualiza o timestamp a cada carregamento de página pra manter a sessão ativa
$_SESSION['admin_last_activity'] = time();
