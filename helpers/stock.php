<?php
// helpers/stock.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

/**
 * Calculates available stock for a specific variant within a given date range.
 * Implements PRD Lampiran A + PRD 8.5 item 5 (active overdue rentals hold stock until returned).
 */
function getAvailableStock(int $variantId, string $startDate, string $endDate, ?int $excludeRentalId = null): int {
    $db = getDB();
    
    // Auto-expire old pending rentals first
    checkAndExpirePendingRentals();

    $sql = "
        SELECT v.total_stock - COALESCE((
            SELECT SUM(ri.quantity)
            FROM rental_items ri
            JOIN rentals r ON r.rental_id = ri.rental_id
            WHERE ri.variant_id = v.variant_id
              AND r.status IN ('pending_payment', 'pending_verification', 'approved', 'active')
              AND (
                  (r.start_date < :end_date AND r.end_date > :start_date)
                  OR (r.status = 'active' AND r.return_date IS NULL AND :start_date_active <= CURDATE())
              )
              AND (:exclude_is_null = 1 OR r.rental_id <> :exclude_rental_id)
        ), 0) AS available_stock
        FROM product_variants v
        WHERE v.variant_id = :variant_id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':end_date'          => $endDate,
        ':start_date'        => $startDate,
        ':start_date_active' => $startDate,
        ':exclude_is_null'   => ($excludeRentalId === null ? 1 : 0),
        ':exclude_rental_id' => ($excludeRentalId ?? 0),
        ':variant_id'        => $variantId
    ]);

    $res = $stmt->fetch();
    return $res ? max(0, (int)$res['available_stock']) : 0;
}

/**
 * Checks if a requested quantity is available.
 */
function isStockAvailable(int $variantId, string $startDate, string $endDate, int $quantity, ?int $excludeRentalId = null): bool {
    $available = getAvailableStock($variantId, $startDate, $endDate, $excludeRentalId);
    return $available >= $quantity;
}

/**
 * Suggests the nearest available dates if requested dates are not available (PRD 5.3 FR-18).
 * Scans ahead day by day for the requested duration.
 */
function suggestAlternativeDates(int $variantId, int $quantity, int $durationDays, string $startingFromDate): array {
    $suggestions = [];
    $currentStart = new DateTime($startingFromDate);
    $today = new DateTime();
    if ($currentStart < $today) {
        $currentStart = clone $today;
    }

    // Look ahead up to 45 days
    for ($i = 1; $i <= 45; $i++) {
        $candidateStart = clone $currentStart;
        $candidateStart->modify("+{$i} days");
        
        $candidateEnd = clone $candidateStart;
        $candidateEnd->modify("+{$durationDays} days");

        $sStr = $candidateStart->format('Y-m-d');
        $eStr = $candidateEnd->format('Y-m-d');

        $avail = getAvailableStock($variantId, $sStr, $eStr);
        if ($avail >= $quantity) {
            $suggestions[] = [
                'start_date' => $sStr,
                'end_date' => $eStr,
                'available_stock' => $avail,
                'duration' => $durationDays
            ];
            if (count($suggestions) >= 3) {
                break;
            }
        }
    }

    return $suggestions;
}

/**
 * Auto expires rentals that passed the payment deadline (PRD 5.4 FR-31 & PRD 6 item 5).
 */
function checkAndExpirePendingRentals(): void {
    static $alreadyChecked = false;
    if ($alreadyChecked) return;
    $alreadyChecked = true;

    try {
        $db = getDB();
        $hours = PAYMENT_DEADLINE_HOURS;
        
        // Find rentals to expire
        $sql = "
            SELECT rental_id FROM rentals 
            WHERE status = 'pending_payment' 
              AND created_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':hours' => $hours]);
        $expiredRentals = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($expiredRentals)) {
            $inList = implode(',', array_map('intval', $expiredRentals));
            $db->exec("UPDATE rentals SET status = 'expired' WHERE rental_id IN ($inList)");
            $db->exec("UPDATE payments SET payment_status = 'expired' WHERE rental_id IN ($inList) AND payment_status = 'pending'");
        }
    } catch (Exception $e) {
        // Silently log or continue
    }
}
