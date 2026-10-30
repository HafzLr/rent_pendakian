<?php
// config/app.php

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// App Settings
define('APP_NAME', 'RentGear Outdoor');
define('APP_TAGLINE', 'Rental Alat Pendakian & Camping Terpercaya');
define('STORE_PHONE', '6281234567890');
define('STORE_EMAIL', 'halo@rentgear.id');
define('STORE_ADDRESS', 'Jl. Kaliurang KM 12.5, Sleman, D.I. Yogyakarta');
define('STORE_HOURS', 'Senin - Minggu: 08:00 - 21:00 WIB');

// Bank & Payment Settings
define('BANK_NAME', 'Bank Central Asia (BCA)');
define('BANK_ACCOUNT', '8720-1928-3341');
define('BANK_HOLDER', 'RENTGEAR OUTDOOR INDONESIA');

define('BANK_2_NAME', 'Bank Mandiri');
define('BANK_2_ACCOUNT', '137-00-1928384-1');
define('BANK_2_HOLDER', 'RENTGEAR OUTDOOR INDONESIA');

define('QRIS_NMID', 'ID1020394857281');

// Business Rules Settings (as in PRD)
define('DP_PERCENTAGE', 0.50); // 50% Down Payment
define('PAYMENT_DEADLINE_HOURS', 24); // 24 hours to pay pending_payment
define('DAILY_PENALTY_RATE', 20000); // Rp 20.000 / day default late penalty

// Base URL calculation
function baseUrl(string $path = ''): string {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // Adjust if in a subfolder like /rent_pedakian2/
    // We normalize scriptDir to the root of rent_pedakian2
    $pos = strpos($_SERVER['REQUEST_URI'] ?? '', '/rent_pedakian2');
    $base = ($pos !== false) ? '/rent_pedakian2' : '';
    
    $cleanPath = ltrim($path, '/');
    return ($base === '' ? '' : $base) . ($cleanPath !== '' ? '/' . $cleanPath : '');
}
