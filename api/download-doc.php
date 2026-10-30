<?php
// api/download-doc.php - Secure document viewer for KTP/SIM and Payment Proofs

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin('Anda harus login untuk melihat dokumen.');

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$user = currentUser();
$db = getDB();

$filePath = null;

if ($type === 'idcard') {
    // Check permission: Admin or same user
    if (!isAdmin() && $user['user_id'] !== $id) {
        http_response_code(403);
        die('Akses ditolak: Anda tidak memiliki izin untuk melihat dokumen ini.');
    }

    $stmt = $db->prepare("SELECT idcard_photo FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u && !empty($u['idcard_photo'])) {
        $filePath = __DIR__ . '/../' . ltrim($u['idcard_photo'], '/');
    }
} elseif ($type === 'payment') {
    // Payment proof: check if admin or owner of the rental
    $stmt = $db->prepare("
        SELECT p.proof_image, r.user_id 
        FROM payments p 
        JOIN rentals r ON r.rental_id = p.rental_id 
        WHERE p.payment_id = ?
    ");
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if (!$p) {
        http_response_code(404);
        die('Bukti pembayaran tidak ditemukan.');
    }

    if (!isAdmin() && $user['user_id'] !== (int)$p['user_id']) {
        http_response_code(403);
        die('Akses ditolak: Anda tidak memiliki izin untuk melihat bukti pembayaran ini.');
    }

    if (!empty($p['proof_image'])) {
        $filePath = __DIR__ . '/../' . ltrim($p['proof_image'], '/');
    }
}

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    die('File dokumen tidak ditemukan.');
}

$mime = mime_content_type($filePath);
header('Content-Type: ' . ($mime ?: 'image/jpeg'));
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
