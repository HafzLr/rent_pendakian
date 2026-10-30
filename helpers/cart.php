<?php
// helpers/cart.php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/stock.php';

function initCart(): void {
    if (!isset($_SESSION['rental_cart'])) {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayAfter = date('Y-m-d', strtotime('+3 days'));
        $_SESSION['rental_cart'] = [
            'start_date' => $tomorrow,
            'end_date'   => $dayAfter,
            'items'      => []
        ];
    }
}

function getCart(): array {
    initCart();
    return $_SESSION['rental_cart'];
}

function setCartDates(string $startDate, string $endDate): array {
    initCart();
    $today = date('Y-m-d');
    if ($startDate < $today) {
        return ['success' => false, 'message' => 'Tanggal mulai tidak boleh di masa lalu.'];
    }
    if ($endDate <= $startDate) {
        return ['success' => false, 'message' => 'Tanggal selesai harus setelah tanggal mulai (minimal 1 hari sewa).'];
    }
    $_SESSION['rental_cart']['start_date'] = $startDate;
    $_SESSION['rental_cart']['end_date'] = $endDate;
    return ['success' => true];
}

function addToCart(int $variantId, int $quantity, ?string $startDate = null, ?string $endDate = null): array {
    initCart();
    if ($quantity <= 0) {
        return ['success' => false, 'message' => 'Jumlah unit harus lebih dari 0.'];
    }

    if ($startDate && $endDate) {
        $dateRes = setCartDates($startDate, $endDate);
        if (!$dateRes['success']) {
            return $dateRes;
        }
    }

    $curStart = $_SESSION['rental_cart']['start_date'];
    $curEnd = $_SESSION['rental_cart']['end_date'];

    $db = getDB();
    $stmt = $db->prepare("
        SELECT pv.variant_id, pv.product_id, pv.variant_name, pv.total_stock,
               p.product_name, p.price_per_day, p.deposit, p.image, p.is_active as p_active, pv.is_active as v_active
        FROM product_variants pv
        JOIN products p ON p.product_id = pv.product_id
        WHERE pv.variant_id = ?
    ");
    $stmt->execute([$variantId]);
    $variant = $stmt->fetch();

    if (!$variant || !$variant['p_active'] || !$variant['v_active']) {
        return ['success' => false, 'message' => 'Varian produk tidak ditemukan atau sedang tidak aktif.'];
    }

    $existingQty = $_SESSION['rental_cart']['items'][$variantId]['quantity'] ?? 0;
    $targetQty = $existingQty + $quantity;

    // Check available stock
    $availableStock = getAvailableStock($variantId, $curStart, $curEnd);
    if ($targetQty > $availableStock) {
        return [
            'success' => false, 
            'message' => "Stok varian {$variant['product_name']} ({$variant['variant_name']}) hanya tersisa {$availableStock} unit untuk tanggal " . formatDate($curStart) . " s.d. " . formatDate($curEnd) . ".",
            'available_stock' => $availableStock
        ];
    }

    $_SESSION['rental_cart']['items'][$variantId] = [
        'variant_id'       => (int)$variant['variant_id'],
        'product_id'       => (int)$variant['product_id'],
        'product_name'     => $variant['product_name'],
        'variant_name'     => $variant['variant_name'],
        'quantity'         => $targetQty,
        'price_per_day'    => (float)$variant['price_per_day'],
        'deposit_per_unit' => (float)$variant['deposit'],
        'image'            => $variant['image']
    ];

    return ['success' => true, 'message' => 'Alat berhasil ditambahkan ke keranjang sewa!'];
}

function updateCartItemQuantity(int $variantId, int $quantity): array {
    initCart();
    if ($quantity <= 0) {
        unset($_SESSION['rental_cart']['items'][$variantId]);
        return ['success' => true, 'message' => 'Item dihapus dari keranjang.'];
    }

    $curStart = $_SESSION['rental_cart']['start_date'];
    $curEnd = $_SESSION['rental_cart']['end_date'];

    $available = getAvailableStock($variantId, $curStart, $curEnd);
    if ($quantity > $available) {
        return [
            'success' => false,
            'message' => "Stok yang tersedia hanya {$available} unit."
        ];
    }

    if (isset($_SESSION['rental_cart']['items'][$variantId])) {
        $_SESSION['rental_cart']['items'][$variantId]['quantity'] = $quantity;
    }

    return ['success' => true];
}

function removeCartItem(int $variantId): void {
    initCart();
    unset($_SESSION['rental_cart']['items'][$variantId]);
}

function clearCart(): void {
    initCart();
    $_SESSION['rental_cart']['items'] = [];
}

function getCartTotals(): array {
    $cart = getCart();
    $start = new DateTime($cart['start_date']);
    $end = new DateTime($cart['end_date']);
    
    // PRD 6 item 1: Durasi = end_date - start_date dalam hari
    $diff = $start->diff($end);
    $durationDays = max(1, (int)$diff->days);

    $itemsCount = 0;
    $totalRental = 0.0;
    $totalDeposit = 0.0;

    foreach ($cart['items'] as $item) {
        $qty = (int)$item['quantity'];
        $itemsCount += $qty;
        $subtotal = $durationDays * (float)$item['price_per_day'] * $qty;
        $deposit = (float)$item['deposit_per_unit'] * $qty;

        $totalRental += $subtotal;
        $totalDeposit += $deposit;
    }

    $grandTotal = $totalRental + $totalDeposit;
    // PRD 14 item 2: DP 50% dihitung dari total tagihan (termasuk deposit)
    $downPaymentAmount = round($grandTotal * DP_PERCENTAGE);
    $remainingAmount = $grandTotal - $downPaymentAmount;

    return [
        'start_date'        => $cart['start_date'],
        'end_date'          => $cart['end_date'],
        'duration_days'     => $durationDays,
        'items_count'       => $itemsCount,
        'unique_items'      => count($cart['items']),
        'total_rental'      => $totalRental,
        'total_deposit'     => $totalDeposit,
        'grand_total'       => $grandTotal,
        'dp_amount'         => $downPaymentAmount,
        'settlement_amount' => $remainingAmount,
    ];
}
