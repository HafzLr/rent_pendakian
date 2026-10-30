<?php
// api/check-stock.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/stock.php';

$variantId = (int)($_REQUEST['variant_id'] ?? 0);
$startDate = trim($_REQUEST['start_date'] ?? '');
$endDate = trim($_REQUEST['end_date'] ?? '');
$quantity = max(1, (int)($_REQUEST['quantity'] ?? 1));

if (!$variantId || empty($startDate) || empty($endDate)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter tidak lengkap (variant_id, start_date, end_date).'
    ]);
    exit;
}

$today = date('Y-m-d');
if ($startDate < $today) {
    echo json_encode([
        'success' => false,
        'message' => 'Tanggal mulai tidak boleh di masa lalu.'
    ]);
    exit;
}

if ($endDate <= $startDate) {
    echo json_encode([
        'success' => false,
        'message' => 'Tanggal selesai harus setelah tanggal mulai.'
    ]);
    exit;
}

$availableStock = getAvailableStock($variantId, $startDate, $endDate);
$isAvailable = $availableStock >= $quantity;

$startObj = new DateTime($startDate);
$endObj = new DateTime($endDate);
$durationDays = max(1, (int)$startObj->diff($endObj)->days);

$suggestions = [];
if (!$isAvailable) {
    // Generate suggested dates (PRD 5.3 FR-18)
    $suggestions = suggestAlternativeDates($variantId, $quantity, $durationDays, $startDate);
}

echo json_encode([
    'success'         => true,
    'variant_id'      => $variantId,
    'start_date'      => $startDate,
    'end_date'        => $endDate,
    'duration_days'   => $durationDays,
    'quantity'        => $quantity,
    'available_stock' => $availableStock,
    'is_available'    => $isAvailable,
    'message'         => $isAvailable 
        ? "Stok tersedia ({$availableStock} unit siap disewa)." 
        : "Stok tidak tersedia pada tanggal ini (sisa {$availableStock} unit).",
    'suggestions'     => $suggestions
]);
