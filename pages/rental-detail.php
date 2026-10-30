<?php
// pages/rental-detail.php - Full Order Details for Renters (FR-33, FR-34, FR-35)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireLogin('Silakan login untuk melihat detail sewa.');
$user = currentUser();
$rentalId = (int)($_GET['id'] ?? 0);

if (!$rentalId) {
    header('Location: ' . baseUrl('pages/my-rentals.php'));
    exit;
}

$db = getDB();

// Fetch rental and verify owner
$stmt = $db->prepare("SELECT * FROM rentals WHERE rental_id = ?");
$stmt->execute([$rentalId]);
$rental = $stmt->fetch();

if (!$rental || (!isAdmin() && $rental['user_id'] != $user['user_id'])) {
    setFlash('error', 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
    header('Location: ' . baseUrl('pages/my-rentals.php'));
    exit;
}

// Handle Order Cancellation (FR-34)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_rental') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        setFlash('error', 'Sesi tidak valid, silakan coba lagi.');
    } elseif (!in_array($rental['status'], ['pending_payment', 'pending_verification'])) {
        setFlash('error', 'Pesanan ini sudah tidak dapat dibatalkan secara mandiri.');
    } else {
        $db->prepare("UPDATE rentals SET status = 'cancelled' WHERE rental_id = ?")->execute([$rentalId]);
        setFlash('info', 'Pesanan #' . $rentalId . ' berhasil dibatalkan. Stok alat telah dilepaskan.');
        header('Location: ' . baseUrl('pages/rental-detail.php?id=' . $rentalId));
        exit;
    }
}

// Fetch items
$itemStmt = $db->prepare("
    SELECT ri.*, pv.variant_name, p.product_name, p.image, p.deposit
    FROM rental_items ri
    JOIN product_variants pv ON pv.variant_id = ri.variant_id
    JOIN products p ON p.product_id = pv.product_id
    WHERE ri.rental_id = ?
");
$itemStmt->execute([$rentalId]);
$items = $itemStmt->fetchAll();

// Fetch payments history
$payStmt = $db->prepare("
    SELECT p.*, u.name as verifier_name
    FROM payments p
    LEFT JOIN users u ON u.user_id = p.verified_by
    WHERE p.rental_id = ?
    ORDER BY p.payment_id ASC
");
$payStmt->execute([$rentalId]);
$payments = $payStmt->fetchAll();

$durDays = max(1, (int)(new DateTime($rental['start_date']))->diff(new DateTime($rental['end_date']))->days);

$pageTitle = 'Detail Pesanan Sewa #' . $rentalId . ' - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item"><a href="<?= baseUrl('pages/my-rentals.php') ?>">Sewa Saya</a></li>
        <li class="breadcrumb-item active" aria-current="page">Pesanan #<?= $rentalId ?></li>
      </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h1 class="font-heading fs-2 mb-1">Pesanan Sewa #<?= $rentalId ?></h1>
        <p class="text-muted small mb-0">Dibuat pada: <?= formatDateTime($rental['created_at']) ?></p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <?= getRentalStatusBadge($rental['status'], $rental['end_date'], (float)$rental['penalty'], $rental['return_date']) ?>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Items, Status & Penalty -->
    <div class="col-lg-8">
      
      <!-- Overdue or Penalty Banner if active -->
      <?php if ($rental['penalty'] > 0): ?>
        <div class="alert alert-danger p-3 mb-4">
          <div class="d-flex align-items-start gap-3">
            <i class="fas fa-exclamation-triangle fa-2x mt-1 text-danger"></i>
            <div>
              <h6 class="fw-bold mb-1">Terdapat Catatan Denda & Biaya Kerusakan/Keterlambatan</h6>
              <div class="fs-5 fw-bold text-danger mb-1"><?= formatRupiah($rental['penalty']) ?></div>
              <?php if (!empty($rental['penalty_notes'])): ?>
                <div class="small bg-white p-2 rounded border text-dark">
                  <strong>Keterangan Admin:</strong> <?= htmlspecialchars($rental['penalty_notes']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Return date banner -->
      <?php if (!empty($rental['return_date'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-4">
          <i class="fas fa-check-double fa-2x text-success"></i>
          <div>
            <div class="fw-bold">Barang Telah Dikembalikan ke Toko</div>
            <div class="small">Tanggal pengembalian tercatat: <strong><?= formatDate($rental['return_date']) ?></strong>.</div>
            <?php if ($rental['deposit_refunded'] > 0): ?>
              <div class="small text-muted mt-1">Uang jaminan deposit sebesar <strong><?= formatRupiah($rental['deposit_refunded']) ?></strong> telah dikembalikan ke penyewa.</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Items Table -->
      <div class="lk-card p-4 mb-4">
        <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
          <i class="fas fa-campground text-forest me-2"></i> Daftar Alat yang Disewa
        </h5>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light small text-muted">
              <tr>
                <th>Perlengkapan</th>
                <th>Harga/Hari</th>
                <th>Deposit/Unit</th>
                <th>Jumlah</th>
                <th class="text-end">Subtotal (<?= $durDays ?> Hari)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <img src="<?= htmlspecialchars($it['image'] ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=200') ?>" 
                           alt="<?= htmlspecialchars($it['product_name']) ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                      <div>
                        <div class="fw-bold"><?= htmlspecialchars($it['product_name']) ?></div>
                        <span class="badge bg-light text-dark border small">Varian: <?= htmlspecialchars($it['variant_name']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="text-forest fw-bold"><?= formatRupiah($it['price_per_day']) ?></td>
                  <td class="small text-muted"><?= formatRupiah($it['deposit']) ?></td>
                  <td class="fw-bold"><?= $it['quantity'] ?></td>
                  <td class="text-end fw-bold text-forest"><?= formatRupiah($it['subtotal']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payment History Table (FR-33) -->
      <div class="lk-card p-4 mb-4">
        <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
          <i class="fas fa-history text-forest me-2"></i> Riwayat Pembayaran & Verifikasi
        </h5>

        <?php if (empty($payments)): ?>
          <p class="small text-muted">Belum ada catatan pembayaran.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle small">
              <thead class="table-light text-muted">
                <tr>
                  <th>Jenis Pembayaran</th>
                  <th>Metode</th>
                  <th>Nominal</th>
                  <th>Status</th>
                  <th>Waktu Konfirmasi</th>
                  <th>Bukti</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                  <tr>
                    <td>
                      <span class="badge bg-dark text-capitalize">
                        <?= str_replace('_', ' ', $p['payment_type']) ?>
                      </span>
                    </td>
                    <td class="text-capitalize"><?= str_replace('_', ' ', $p['payment_method']) ?></td>
                    <td class="fw-bold text-forest"><?= formatRupiah($p['payment_amount']) ?></td>
                    <td>
                      <?php if ($p['payment_status'] === 'success'): ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Terverifikasi</span>
                      <?php elseif ($p['payment_status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Menunggu Verifikasi</span>
                      <?php elseif ($p['payment_status'] === 'failed'): ?>
                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Ditolak</span>
                      <?php elseif ($p['payment_status'] === 'expired'): ?>
                        <span class="badge bg-secondary">Kedaluwarsa</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $p['paid_at'] ? formatDateTime($p['paid_at']) : '-' ?></td>
                    <td>
                      <?php if (!empty($p['proof_image'])): ?>
                        <a href="<?= baseUrl('api/download-doc.php?type=payment&id=' . $p['payment_id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2">
                          <i class="fas fa-image me-1"></i> Lihat
                        </a>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php if (!empty($p['rejection_reason'])): ?>
                    <tr class="table-danger">
                      <td colspan="6" class="small py-2 text-danger">
                        <strong>Alasan Penolakan:</strong> <?= htmlspecialchars($p['rejection_reason']) ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <?php if (in_array($rental['status'], ['pending_payment', 'pending_verification'])): ?>
          <div class="mt-3 text-end">
            <a href="<?= baseUrl('pages/payment.php?id=' . $rentalId) ?>" class="btn btn-forest btn-sm">
              <i class="fas fa-upload me-1"></i> Buka Halaman Pembayaran / Upload Bukti
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Right Column: Summary & Cancellation Button -->
    <div class="col-lg-4">
      <div class="lk-card p-4 mb-4">
        <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">Informasi Jadwal</h5>

        <div class="small mb-3">
          <div class="text-muted">Tanggal Mulai Ambil:</div>
          <div class="fw-bold text-dark fs-6"><?= formatDate($rental['start_date']) ?></div>
        </div>

        <div class="small mb-3">
          <div class="text-muted">Tanggal Selesai Kembali:</div>
          <div class="fw-bold text-dark fs-6"><?= formatDate($rental['end_date']) ?></div>
        </div>

        <div class="small mb-3">
          <div class="text-muted">Durasi Sewa:</div>
          <div class="fw-bold text-forest"><?= $durDays ?> Hari Kalender</div>
        </div>

        <div class="small mb-3">
          <div class="text-muted">Skema Pembayaran:</div>
          <div class="fw-bold text-dark"><?= $rental['payment_scheme'] === 'down_payment' ? 'Uang Muka (DP 50%)' : 'Pembayaran Lunas (100%)' ?></div>
        </div>

        <div class="border-top pt-3 mb-3 small">
          <div class="d-flex justify-content-between mb-1 text-muted">
            <span>Biaya Sewa Alat:</span>
            <span class="fw-bold text-dark"><?= formatRupiah($rental['total_rental']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-1 text-muted">
            <span>Uang Jaminan (Deposit):</span>
            <span class="fw-bold text-dark"><?= formatRupiah($rental['total_deposit']) ?></span>
          </div>
          <?php if ($rental['penalty'] > 0): ?>
            <div class="d-flex justify-content-between mb-1 text-danger">
              <span>Denda / Kerusakan:</span>
              <span class="fw-bold"><?= formatRupiah($rental['penalty']) ?></span>
            </div>
          <?php endif; ?>
          <div class="d-flex justify-content-between border-top pt-2 fw-bold fs-5 text-forest">
            <span>Total Tagihan:</span>
            <span><?= formatRupiah($rental['total_rental'] + $rental['total_deposit'] + $rental['penalty']) ?></span>
          </div>
        </div>

        <!-- WhatsApp Store Button with Pre-filled Message (FR-35) -->
        <div class="d-grid mb-3">
          <?php 
            $waMsg = "Halo Admin RentGear, saya ingin menanyakan pesanan sewa #{$rentalId} atas nama {$user['name']} (Periode: {$rental['start_date']} s.d. {$rental['end_date']}).";
          ?>
          <a href="<?= generateWhatsAppUrl(STORE_PHONE, $waMsg) ?>" target="_blank" class="btn btn-outline-success py-2">
            <i class="fab fa-whatsapp me-1"></i> Hubungi Toko via WhatsApp
          </a>
        </div>

        <!-- Cancellation Form (FR-34) -->
        <?php if (in_array($rental['status'], ['pending_payment', 'pending_verification'])): ?>
          <div class="border-top pt-3">
            <form action="<?= baseUrl('pages/rental-detail.php?id=' . $rentalId) ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan sewa ini? Tindakan ini akan melepas stok alat yang telah ditahan.')">
              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
              <input type="hidden" name="action" value="cancel_rental">
              <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                <i class="fas fa-ban me-1"></i> Batalkan Pesanan Ini
              </button>
            </form>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
