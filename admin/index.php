<?php
// admin/index.php - Admin Dashboard Overview
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/stock.php';

requireAdmin();
$db = getDB();

// Auto expire old pending rentals
checkAndExpirePendingRentals();

// Metrics calculations
$pendingUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND verification_status = 'pending'")->fetchColumn();
$pendingPayments = (int)$db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn();
$activeRentals = (int)$db->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
$overdueRentals = (int)$db->query("SELECT COUNT(*) FROM rentals WHERE status = 'active' AND end_date < CURDATE() AND return_date IS NULL")->fetchColumn();
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(payment_amount), 0) FROM payments WHERE payment_status = 'success'")->fetchColumn();
$totalRentalsCount = (int)$db->query("SELECT COUNT(*) FROM rentals")->fetchColumn();

// Recent pending users
$recentUsers = $db->query("
    SELECT user_id, name, email, user_number, idcard_photo, created_at 
    FROM users 
    WHERE role = 'user' AND verification_status = 'pending'
    ORDER BY user_id ASC 
    LIMIT 5
")->fetchAll();

// Recent pending payments
$recentPayments = $db->query("
    SELECT p.*, r.user_id, r.start_date, r.end_date, u.name as customer_name
    FROM payments p
    JOIN rentals r ON r.rental_id = p.rental_id
    JOIN users u ON u.user_id = r.user_id
    WHERE p.payment_status = 'pending'
    ORDER BY p.payment_id ASC
    LIMIT 5
")->fetchAll();

// Ready for pickup today or active
$readyPickup = $db->query("
    SELECT r.*, u.name as customer_name, u.user_number
    FROM rentals r
    JOIN users u ON u.user_id = r.user_id
    WHERE r.status = 'approved' AND r.start_date <= CURDATE()
    ORDER BY r.start_date ASC
    LIMIT 5
")->fetchAll();

$adminTitle = 'Dashboard Ringkasan - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Dashboard Administrator</h1>
    <p class="text-muted small mb-0">Pemantauan operasional sewa, antrean verifikasi KTP, pembayaran, dan stok alat.</p>
  </div>
  <div class="btn-toolbar mb-2 mb-md-0 gap-2">
    <a href="<?= baseUrl('admin/calendar.php') ?>" class="btn btn-sm btn-outline-forest">
      <i class="fas fa-calendar-alt me-1"></i> Kalender Ketersediaan
    </a>
    <a href="<?= baseUrl('admin/reports.php') ?>" class="btn btn-sm btn-forest">
      <i class="fas fa-file-invoice-dollar me-1"></i> Laporan Keuangan
    </a>
  </div>
</div>

<!-- Key Stat Cards -->
<div class="row g-3 mb-4">
  <!-- Card 1: Pending Users -->
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card d-flex align-items-center justify-content-between">
      <div>
        <div class="text-muted small fw-bold mb-1">Verifikasi KTP Pending</div>
        <div class="fs-3 fw-bold text-dark"><?= $pendingUsers ?></div>
        <a href="<?= baseUrl('admin/users.php') ?>" class="small text-forest text-decoration-none">
          Buka Antrean <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #FFF8E1; color: var(--lk-warning);">
        <i class="fas fa-user-clock fa-lg"></i>
      </div>
    </div>
  </div>

  <!-- Card 2: Pending Payments -->
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card d-flex align-items-center justify-content-between">
      <div>
        <div class="text-muted small fw-bold mb-1">Verifikasi Bayar Pending</div>
        <div class="fs-3 fw-bold text-dark"><?= $pendingPayments ?></div>
        <a href="<?= baseUrl('admin/payments.php') ?>" class="small text-forest text-decoration-none">
          Lihat Bukti Bayar <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #E1F5FE; color: var(--lk-info);">
        <i class="fas fa-money-check-alt fa-lg"></i>
      </div>
    </div>
  </div>

  <!-- Card 3: Active & Overdue -->
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card d-flex align-items-center justify-content-between">
      <div>
        <div class="text-muted small fw-bold mb-1">Sedang Disewa (Aktif)</div>
        <div class="fs-3 fw-bold text-dark"><?= $activeRentals ?></div>
        <?php if ($overdueRentals > 0): ?>
          <span class="badge bg-danger small"><?= $overdueRentals ?> Terlambat</span>
        <?php else: ?>
          <span class="small text-muted">Semua tepat waktu</span>
        <?php endif; ?>
      </div>
      <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: #E8F5E9; color: var(--lk-success);">
        <i class="fas fa-hiking fa-lg"></i>
      </div>
    </div>
  </div>

  <!-- Card 4: Total Revenue -->
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card d-flex align-items-center justify-content-between">
      <div>
        <div class="text-muted small fw-bold mb-1">Total Kas Masuk</div>
        <div class="fs-4 fw-bold text-forest"><?= formatRupiah($totalRevenue) ?></div>
        <span class="small text-muted"><?= $totalRentalsCount ?> Total Pesanan Sewa</span>
      </div>
      <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: var(--lk-primary-tint); color: var(--lk-forest);">
        <i class="fas fa-wallet fa-lg"></i>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Left Column: Pending Payments Queue (FR-37) -->
  <div class="col-lg-6">
    <div class="lk-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-heading fs-5 mb-0">
          <i class="fas fa-money-bill-wave text-forest me-2"></i> Antrean Verifikasi Pembayaran
        </h5>
        <a href="<?= baseUrl('admin/payments.php') ?>" class="btn btn-outline-forest btn-sm">Semua Pembayaran</a>
      </div>

      <?php if (empty($recentPayments)): ?>
        <div class="text-center py-4 text-muted small">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
          Tidak ada bukti pembayaran yang menunggu verifikasi saat ini.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead class="table-light">
              <tr>
                <th>Order</th>
                <th>Penyewa</th>
                <th>Nominal</th>
                <th>Metode</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentPayments as $rp): ?>
                <tr>
                  <td><strong>#<?= $rp['rental_id'] ?></strong></td>
                  <td><?= htmlspecialchars($rp['customer_name']) ?></td>
                  <td class="fw-bold text-forest"><?= formatRupiah($rp['payment_amount']) ?></td>
                  <td class="text-capitalize"><?= str_replace('_', ' ', $rp['payment_method']) ?></td>
                  <td>
                    <a href="<?= baseUrl('admin/payments.php#pay-' . $rp['payment_id']) ?>" class="btn btn-forest btn-sm py-1 px-2">
                      <i class="fas fa-check-double me-1"></i> Periksa
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Pending Users Queue (FR-36) -->
  <div class="col-lg-6">
    <div class="lk-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-heading fs-5 mb-0">
          <i class="fas fa-user-check text-forest me-2"></i> Antrean Verifikasi Identitas (KTP/SIM)
        </h5>
        <a href="<?= baseUrl('admin/users.php') ?>" class="btn btn-outline-forest btn-sm">Semua Pengguna</a>
      </div>

      <?php if (empty($recentUsers)): ?>
        <div class="text-center py-4 text-muted small">
          <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
          Tidak ada pengguna baru yang menunggu verifikasi KTP.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead class="table-light">
              <tr>
                <th>Nama</th>
                <th>Email / WA</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentUsers as $ru): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($ru['name']) ?></strong></td>
                  <td>
                    <?= htmlspecialchars($ru['email']) ?><br>
                    <span class="text-muted"><?= htmlspecialchars($ru['user_number']) ?></span>
                  </td>
                  <td><?= formatDate($ru['created_at']) ?></td>
                  <td>
                    <a href="<?= baseUrl('admin/users.php#user-' . $ru['user_id']) ?>" class="btn btn-forest btn-sm py-1 px-2">
                      <i class="fas fa-id-card me-1"></i> Verifikasi KTP
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Ready for Pickup Today Table -->
<div class="lk-card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="font-heading fs-5 mb-0">
      <i class="fas fa-handshake text-forest me-2"></i> Pesanan Siap Diambil di Toko (Hari Ini / Mendatang)
    </h5>
    <a href="<?= baseUrl('admin/rentals.php?status=approved') ?>" class="btn btn-outline-forest btn-sm">Lihat Semua</a>
  </div>

  <?php if (empty($readyPickup)): ?>
    <p class="text-muted small mb-0 text-center py-3">Tidak ada pesanan berstatus Siap Diambil saat ini.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle small">
        <thead class="table-light">
          <tr>
            <th>Order ID</th>
            <th>Penyewa</th>
            <th>WhatsApp</th>
            <th>Mulai Sewa</th>
            <th>Kembali</th>
            <th>Skema</th>
            <th>Total Tagihan</th>
            <th>Aksi Serah Terima</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($readyPickup as $rk): ?>
            <tr>
              <td><strong>#<?= $rk['rental_id'] ?></strong></td>
              <td><strong><?= htmlspecialchars($rk['customer_name']) ?></strong></td>
              <td>
                <a href="<?= generateWhatsAppUrl($rk['user_number'], "Halo {$rk['customer_name']}, pesanan sewa #{$rk['rental_id']} Anda di RentGear sudah siap diambil di toko.") ?>" target="_blank" class="text-success text-decoration-none">
                  <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($rk['user_number']) ?>
                </a>
              </td>
              <td><?= formatDate($rk['start_date']) ?></td>
              <td><?= formatDate($rk['end_date']) ?></td>
              <td><?= $rk['payment_scheme'] === 'down_payment' ? '<span class="badge bg-warning text-dark">DP 50%</span>' : '<span class="badge bg-success">Lunas</span>' ?></td>
              <td class="fw-bold text-forest"><?= formatRupiah($rk['total_rental'] + $rk['total_deposit']) ?></td>
              <td>
                <a href="<?= baseUrl('admin/returns.php?id=' . $rk['rental_id']) ?>" class="btn btn-sm btn-forest">
                  <i class="fas fa-dolly me-1"></i> Proses Penyerahan
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
