<?php
// auth/logout.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/functions.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['user_id']);
    unset($_SESSION['rental_cart']);
}

setFlash('info', 'Anda telah berhasil keluar dari sistem.');
header('Location: ' . baseUrl());
exit;
