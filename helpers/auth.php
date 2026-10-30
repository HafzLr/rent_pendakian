<?php
// helpers/auth.php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    static $cachedUser = null;
    if ($cachedUser === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id, name, email, user_number, role, idcard_photo, verification_status, verification_notes, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cachedUser = $stmt->fetch() ?: null;
        if (!$cachedUser) {
            unset($_SESSION['user_id']);
        }
    }
    return $cachedUser;
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function isAdmin(): bool {
    $user = currentUser();
    return $user !== null && $user['role'] === 'admin';
}

function isVerifiedUser(): bool {
    $user = currentUser();
    return $user !== null && $user['verification_status'] === 'verified';
}

function requireLogin(string $redirectMessage = 'Silakan login terlebih dahulu untuk mengakses halaman ini.'): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = $redirectMessage;
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . baseUrl('auth/login.php'));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin('Silakan login sebagai admin.');
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Akses ditolak. Halaman ini hanya untuk Administrator.';
        header('Location: ' . baseUrl());
        exit;
    }
}

function requireVerified(string $redirectMessage = 'Akun Anda belum terverifikasi. Mohon tunggu verifikasi KTP/SIM oleh admin untuk melanjutkan sewa.'): void {
    requireLogin();
    $user = currentUser();
    if ($user['role'] === 'admin') {
        return; // admin bypass
    }
    if ($user['verification_status'] !== 'verified') {
        $_SESSION['flash_warning'] = $redirectMessage;
        header('Location: ' . baseUrl('pages/profile.php'));
        exit;
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
