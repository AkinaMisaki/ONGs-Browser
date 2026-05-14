<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../../config.php';
// Disponibiliza $CAPTCHA_SITE pra view
