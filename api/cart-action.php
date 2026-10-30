<?php
// api/cart-action.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/cart.php';
require_once __DIR__ . '/../helpers/functions.php';

$action = $_REQUEST['action'] ?? 'get';

if ($action === 'add') {
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $startDate = !empty($_POST['start_date']) ? trim($_POST['start_date']) : null;
    $endDate = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;

    $result = addToCart($variantId, $quantity, $startDate, $endDate);
    $result['totals'] = getCartTotals();
    $result['cart'] = getCart();
    echo json_encode($result);
    exit;
}

if ($action === 'update') {
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $result = updateCartItemQuantity($variantId, $quantity);
    $result['totals'] = getCartTotals();
    echo json_encode($result);
    exit;
}

if ($action === 'remove') {
    $variantId = (int)($_POST['variant_id'] ?? 0);
    removeCartItem($variantId);
    echo json_encode([
        'success' => true,
        'message' => 'Item berhasil dihapus.',
        'totals'  => getCartTotals(),
        'cart'    => getCart()
    ]);
    exit;
}

if ($action === 'set_dates') {
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $result = setCartDates($startDate, $endDate);
    $result['totals'] = getCartTotals();
    echo json_encode($result);
    exit;
}

// default get
echo json_encode([
    'success' => true,
    'cart'    => getCart(),
    'totals'  => getCartTotals()
]);
