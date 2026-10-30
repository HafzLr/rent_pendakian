<?php
// helpers/functions.php

require_once __DIR__ . '/../config/app.php';

function formatRupiah($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function formatDate(?string $dateStr): string {
    if (!$dateStr) return '-';
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $timestamp = strtotime($dateStr);
    $d = date('j', $timestamp);
    $m = (int)date('n', $timestamp);
    $y = date('Y', $timestamp);
    return "{$d} {$months[$m]} {$y}";
}

function formatDateTime(?string $datetimeStr): string {
    if (!$datetimeStr) return '-';
    $datePart = formatDate(substr($datetimeStr, 0, 10));
    $timePart = substr($datetimeStr, 11, 5) . ' WIB';
    return "{$datePart}, {$timePart}";
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(): array {
    $flashes = [];
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (!empty($_SESSION['flash_' . $type])) {
            $flashes[] = [
                'type' => $type === 'error' ? 'danger' : $type,
                'message' => $_SESSION['flash_' . $type]
            ];
            unset($_SESSION['flash_' . $type]);
        }
    }
    return $flashes;
}

/**
 * Returns HTML badge string for order status according to PRD 9.6:
 * pending_payment => Peringatan / Warning
 * pending_verification => Info
 * approved => Sukses
 * active => Forest
 * active + overdue => Bahaya / Danger
 * active + penalty > 0 => Bahaya / Danger
 * completed => Abu-abu
 * rejected / cancelled / expired => Bahaya outline / Grey outline
 */
function getRentalStatusBadge(string $status, ?string $endDate = null, float $penalty = 0, ?string $returnDate = null): string {
    $isOverdue = false;
    $today = date('Y-m-d');
    if ($status === 'active' && $endDate && $today > $endDate && empty($returnDate)) {
        $isOverdue = true;
    }

    if ($status === 'active' && $isOverdue) {
        return '<span class="lk-badge lk-badge-danger"><i class="fas fa-exclamation-triangle me-1"></i> Terlambat</span>';
    }

    if ($status === 'active' && $penalty > 0) {
        return '<span class="lk-badge lk-badge-danger"><i class="fas fa-file-invoice-dollar me-1"></i> Denda</span>';
    }

    switch ($status) {
        case 'pending_payment':
            return '<span class="lk-badge lk-badge-warning"><i class="fas fa-clock me-1"></i> Menunggu Pembayaran</span>';
        case 'pending_verification':
            return '<span class="lk-badge lk-badge-info"><i class="fas fa-search me-1"></i> Menunggu Verifikasi Admin</span>';
        case 'approved':
            return '<span class="lk-badge lk-badge-success"><i class="fas fa-check-circle me-1"></i> Siap Diambil</span>';
        case 'active':
            return '<span class="lk-badge lk-badge-forest"><i class="fas fa-hiking me-1"></i> Sedang Disewa</span>';
        case 'completed':
            return '<span class="lk-badge lk-badge-muted"><i class="fas fa-check-double me-1"></i> Selesai</span>';
        case 'rejected':
            return '<span class="lk-badge lk-badge-danger-outline"><i class="fas fa-times-circle me-1"></i> Ditolak</span>';
        case 'cancelled':
            return '<span class="lk-badge lk-badge-secondary-outline"><i class="fas fa-ban me-1"></i> Dibatalkan</span>';
        case 'expired':
            return '<span class="lk-badge lk-badge-secondary-outline"><i class="fas fa-hourglass-end me-1"></i> Kedaluwarsa</span>';
        default:
            return '<span class="lk-badge lk-badge-muted">' . htmlspecialchars($status) . '</span>';
    }
}

function getUserVerificationBadge(string $status): string {
    switch ($status) {
        case 'verified':
            return '<span class="lk-badge lk-badge-success"><i class="fas fa-user-check me-1"></i> Terverifikasi</span>';
        case 'pending':
            return '<span class="lk-badge lk-badge-warning"><i class="fas fa-user-clock me-1"></i> Menunggu Verifikasi KTP/SIM</span>';
        case 'rejected':
            return '<span class="lk-badge lk-badge-danger"><i class="fas fa-user-times me-1"></i> Verifikasi Ditolak</span>';
        default:
            return '<span class="lk-badge lk-badge-muted">' . htmlspecialchars($status) . '</span>';
    }
}

function generateWhatsAppUrl(string $phone, string $message): string {
    // Sanitize phone number to start with 62
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($cleanPhone, '0')) {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    }
    return "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);
}
