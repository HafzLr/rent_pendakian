<?php
// admin/reports.php - Revenue, Penalties & Best-selling Gear Reports (FR-46)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireAdmin();
$db = getDB();

// 1. Overall Financial Aggregates
$totalCashIn = (float)$db->query("
    SELECT COALESCE(SUM(payment_amount), 0) 
    FROM payments 
    WHERE payment_status = 'success'
")->fetchColumn();

$totalPenalties = (float)$db->query("
    SELECT COALESCE(SUM(penalty), 0) 
    FROM rentals 
    WHERE penalty > 0
")->fetchColumn();

$totalDepositRefunded = (float)$db->query("
    SELECT COALESCE(SUM(deposit_refunded), 0) 
    FROM rentals 
    WHERE deposit_refunded > 0
")->fetchColumn();

$pureRentalRevenue = (float)$db->query("
    SELECT COALESCE(SUM(total_rental), 0) 
    FROM rentals 
    WHERE status IN ('completed', 'active', 'approved')
")->fetchColumn();

// 2. Monthly Revenue Breakdown
$monthlyStats = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS rent_month,
           COUNT(rental_id) AS total_orders,
           SUM(total_rental) AS month_rental,
           SUM(penalty) AS month_penalty
    FROM rentals
    WHERE status NOT IN ('rejected', 'cancelled', 'expired')
    GROUP BY rent_month
    ORDER BY rent_month DESC
    LIMIT 12
")->fetchAll();

// 3. Top Rented Products Ranking
$topProducts = $db->query("
    SELECT p.product_name, c.category_name,
           COUNT(DISTINCT r.rental_id) AS times_rented,
           COALESCE(SUM(ri.quantity), 0) AS total_units_rented,
           COALESCE(SUM(ri.subtotal), 0) AS total_revenue
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    JOIN product_variants pv ON pv.product_id = p.product_id
    JOIN rental_items ri ON ri.variant_id = pv.variant_id
    JOIN rentals r ON r.rental_id = ri.rental_id
    WHERE r.status NOT IN ('rejected', 'cancelled', 'expired')
    GROUP BY p.product_id
    ORDER BY times_rented DESC, total_units_rented DESC
    LIMIT 8
")->fetchAll();

// 4. Penalties Log
$penaltyLogs = $db->query("
    SELECT r.rental_id, r.penalty, r.penalty_notes, r.return_date, u.name as customer_name
    FROM rentals r
    JOIN users u ON u.user_id = r.user_id
    WHERE r.penalty > 0
    ORDER BY r.rental_id DESC
    LIMIT 10
")->fetchAll();

$adminTitle = 'Laporan Keuangan & Produk Terlaris - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Laporan Keuangan & Performa Produk</h1>
    <p class="text-muted small mb-0">Statistik pendapatan sewa, penerimaan denda, deposit refund, dan produk perlengkapan terlaris.</p>
  </div>
</div>

<!-- Aggregates Metric Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card">
      <div class="text-muted small fw-bold mb-1">Total Pembayaran Masuk</div>
      <div class="fs-4 fw-bold text-forest"><?= formatRupiah($totalCashIn) ?></div>
      <span class="small text-muted">Kas mutasi bank & tunai</span>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card">
      <div class="text-muted small fw-bold mb-1">Omset Sewa Murni</div>
      <div class="fs-4 fw-bold text-dark"><?= formatRupiah($pureRentalRevenue) ?></div>
      <span class="small text-muted">Di luar jaminan deposit</span>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card">
      <div class="text-muted small fw-bold mb-1">Total Denda Terkumpul</div>
      <div class="fs-4 fw-bold text-danger"><?= formatRupiah($totalPenalties) ?></div>
      <span class="small text-muted">Keterlambatan / ganti rugi</span>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="lk-admin-stat-card">
      <div class="text-muted small fw-bold mb-1">Uang Deposit Dikembalikan</div>
      <div class="fs-4 fw-bold text-info"><?= formatRupiah($totalDepositRefunded) ?></div>
      <span class="small text-muted">Deposit aman selesai sewa</span>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Top Products Ranking -->
  <div class="col-lg-7">
    <div class="lk-card p-4 h-100">
      <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
        <i class="fas fa-trophy text-warning me-2"></i> Alat Pendakian Terlaris & Terfavorit
      </h5>

      <?php if (empty($topProducts)): ?>
        <p class="text-muted small py-4 text-center">Belum ada data persewaan alat.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead class="table-light text-muted">
              <tr>
                <th>Peringkat & Alat</th>
                <th>Kategori</th>
                <th>Frekuensi Sewa</th>
                <th>Unit Keluar</th>
                <th class="text-end">Total Omset</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topProducts as $rank => $tp): ?>
                <tr>
                  <td>
                    <strong>#<?= $rank + 1 ?>. <?= htmlspecialchars($tp['product_name']) ?></strong>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tp['category_name']) ?></span></td>
                  <td><strong><?= $tp['times_rented'] ?></strong> transaksi</td>
                  <td><strong><?= $tp['total_units_rented'] ?></strong> unit</td>
                  <td class="text-end fw-bold text-forest"><?= formatRupiah($tp['total_revenue']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Monthly Breakdown -->
  <div class="col-lg-5">
    <div class="lk-card p-4 h-100">
      <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
        <i class="fas fa-calendar-alt text-forest me-2"></i> Rekapitulasi Bulanan
      </h5>

      <?php if (empty($monthlyStats)): ?>
        <p class="text-muted small py-4 text-center">Belum ada transaksi bulanan.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead class="table-light text-muted">
              <tr>
                <th>Bulan</th>
                <th>Pesanan</th>
                <th>Omset Sewa</th>
                <th>Denda</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monthlyStats as $ms): ?>
                <tr>
                  <td><strong><?= $ms['rent_month'] ?></strong></td>
                  <td><?= $ms['total_orders'] ?> Order</td>
                  <td class="fw-bold text-forest"><?= formatRupiah($ms['month_rental']) ?></td>
                  <td class="text-danger"><?= formatRupiah($ms['month_penalty']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Denda Logs -->
<?php if (!empty($penaltyLogs)): ?>
  <div class="lk-card p-4 mb-4">
    <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
      <i class="fas fa-file-invoice-dollar text-danger me-2"></i> Log Catatan Denda & Ganti Rugi
    </h5>
    <div class="table-responsive">
      <table class="table align-middle small">
        <thead class="table-light text-muted">
          <tr>
            <th>Order ID</th>
            <th>Penyewa</th>
            <th>Tanggal Kembali</th>
            <th>Nominal Denda</th>
            <th>Keterangan / Alasan Denda</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($penaltyLogs as $pl): ?>
            <tr>
              <td><strong>#<?= $pl['rental_id'] ?></strong></td>
              <td><?= htmlspecialchars($pl['customer_name']) ?></td>
              <td><?= formatDate($pl['return_date']) ?></td>
              <td class="fw-bold text-danger"><?= formatRupiah($pl['penalty']) ?></td>
              <td><?= htmlspecialchars($pl['penalty_notes'] ?: 'Terlambat pengembalian.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
