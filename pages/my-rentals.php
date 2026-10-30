<?php
// pages/my-rentals.php - Order list and status monitoring for renters (FR-32)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/stock.php';

requireLogin('Silakan login untuk melihat riwayat sewa Anda.');
$user = currentUser();
$db = getDB();

// Auto check for expired rentals
checkAndExpirePendingRentals();

$statusFilter = trim($_GET['status'] ?? 'all');

$sql = "
    SELECT r.*, 
           COUNT(ri.rental_item_id) as total_items,
           COALESCE(SUM(ri.quantity), 0) as total_units
    FROM rentals r
    LEFT JOIN rental_items ri ON ri.rental_id = r.rental_id
    WHERE r.user_id = ?
";
$params = [$user['user_id']];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$sql .= " GROUP BY r.rental_id ORDER BY r.rental_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

$pageTitle = 'Sewa Saya - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sewa Saya</li>
      </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h1 class="font-heading fs-2 mb-1">Daftar & Monitoring Sewa Saya</h1>
        <p class="text-muted small mb-0">Pantau status verifikasi, jadwal pengambilan, dan riwayat pesanan alat pendakian Anda.</p>
      </div>
      <div>
        <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest btn-sm">
          <i class="fas fa-plus me-1"></i> Sewa Alat Baru
        </a>
      </div>
    </div>
  </div>

  <!-- Status Filter Tabs (PRD 5.5 FR-32) -->
  <div class="d-flex gap-2 overflow-auto pb-2 mb-4 text-nowrap">
    <a href="<?= baseUrl('pages/my-rentals.php') ?>" class="lk-category-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">
      Semua Status
    </a>
    <a href="<?= baseUrl('pages/my-rentals.php?status=pending_payment') ?>" class="lk-category-pill <?= $statusFilter === 'pending_payment' ? 'active' : '' ?>">
      Menunggu Bayar
    </a>
    <a href="<?= baseUrl('pages/my-rentals.php?status=pending_verification') ?>" class="lk-category-pill <?= $statusFilter === 'pending_verification' ? 'active' : '' ?>">
      Menunggu Verifikasi
    </a>
    <a href="<?= baseUrl('pages/my-rentals.php?status=approved') ?>" class="lk-category-pill <?= $statusFilter === 'approved' ? 'active' : '' ?>">
      Siap Diambil
    </a>
    <a href="<?= baseUrl('pages/my-rentals.php?status=active') ?>" class="lk-category-pill <?= $statusFilter === 'active' ? 'active' : '' ?>">
      Sedang Disewa
    </a>
    <a href="<?= baseUrl('pages/my-rentals.php?status=completed') ?>" class="lk-category-pill <?= $statusFilter === 'completed' ? 'active' : '' ?>">
      Selesai
    </a>
    <a href="<?= baseUrl('pages/my-rentals.php?status=expired') ?>" class="lk-category-pill <?= $statusFilter === 'expired' ? 'active' : '' ?>">
      Kedaluwarsa
    </a>
  </div>

  <!-- Rentals List -->
  <?php if (empty($rentals)): ?>
    <div class="lk-card text-center py-5">
      <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
      <h4 class="font-heading fs-4 mb-2">Tidak Ada Pesanan Ditemukan</h4>
      <p class="text-muted small mb-4">Anda belum memiliki pesanan dengan filter status yang dipilih.</p>
      <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-outline-forest btn-sm">
        Jelajahi Katalog Perlengkapan
      </a>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-3">
      <?php foreach ($rentals as $r): 
        $grandTotal = $r['total_rental'] + $r['total_deposit'] + $r['penalty'];
        $durDays = max(1, (int)(new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->days);
      ?>
        <div class="lk-card p-4">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center gap-3">
              <span class="fs-5 fw-bold font-heading text-forest">#<?= $r['rental_id'] ?></span>
              <span class="small text-muted"><i class="far fa-clock me-1"></i> Dibuat: <?= formatDateTime($r['created_at']) ?></span>
            </div>
            <div>
              <?= getRentalStatusBadge($r['status'], $r['end_date'], (float)$r['penalty'], $r['return_date']) ?>
            </div>
          </div>

          <div class="row g-3 align-items-center">
            <!-- Rental Dates & Items -->
            <div class="col-md-5">
              <div class="small text-muted mb-1">Periode Penyewaan (<?= $durDays ?> Hari):</div>
              <div class="fw-bold text-dark fs-6 mb-1">
                <i class="far fa-calendar-alt text-forest me-1"></i> <?= formatDate($r['start_date']) ?> &rarr; <?= formatDate($r['end_date']) ?>
              </div>
              <div class="small text-muted">
                Jumlah: <strong><?= $r['total_units'] ?> unit</strong> barang &bull; Skema: <strong><?= $r['payment_scheme'] === 'down_payment' ? 'DP 50%' : 'Lunas 100%' ?></strong>
              </div>
            </div>

            <!-- Cost summary -->
            <div class="col-md-4">
              <div class="small text-muted mb-1">Total Tagihan (Sewa + Deposit):</div>
              <div class="fs-5 fw-bold text-forest">
                <?= formatRupiah($grandTotal) ?>
              </div>
              <?php if ($r['penalty'] > 0): ?>
                <div class="small text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Ada Denda: <?= formatRupiah($r['penalty']) ?></div>
              <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="col-md-3 text-md-end">
              <div class="d-flex flex-column gap-2">
                <a href="<?= baseUrl('pages/rental-detail.php?id=' . $r['rental_id']) ?>" class="btn btn-outline-forest btn-sm">
                  <i class="fas fa-eye me-1"></i> Lihat Detail Pesanan
                </a>
                
                <?php if (in_array($r['status'], ['pending_payment', 'pending_verification'])): ?>
                  <a href="<?= baseUrl('pages/payment.php?id=' . $r['rental_id']) ?>" class="btn btn-forest btn-sm">
                    <i class="fas fa-wallet me-1"></i> <?= $r['status'] === 'pending_payment' ? 'Bayar / Upload Bukti' : 'Lihat Status Bukti' ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
