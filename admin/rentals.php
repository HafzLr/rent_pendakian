<?php
// admin/rentals.php - All Rentals Management (FR-43)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireAdmin();
$db = getDB();

$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$filterStart = trim($_GET['start_date'] ?? '');
$filterEnd = trim($_GET['end_date'] ?? '');

$sql = "
    SELECT r.*, u.name as customer_name, u.user_number, u.email as customer_email,
           COUNT(ri.rental_item_id) as total_items,
           COALESCE(SUM(ri.quantity), 0) as total_units
    FROM rentals r
    JOIN users u ON u.user_id = r.user_id
    LEFT JOIN rental_items ri ON ri.rental_id = r.rental_id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.user_number LIKE ? OR r.rental_id = ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $params[] = (int)$searchQuery;
}

if (!empty($filterStart)) {
    $sql .= " AND r.start_date >= ?";
    $params[] = $filterStart;
}

if (!empty($filterEnd)) {
    $sql .= " AND r.end_date <= ?";
    $params[] = $filterEnd;
}

$sql .= " GROUP BY r.rental_id ORDER BY r.rental_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rentals = $stmt->fetchAll();

$adminTitle = 'Kelola Semua Pesanan - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Daftar Semua Pesanan Sewa</h1>
    <p class="text-muted small mb-0">Manajemen komprehensif seluruh transaksi sewa, pemantauan status, dan filter tanggal.</p>
  </div>
  <a href="<?= baseUrl('admin/returns.php') ?>" class="btn btn-sm btn-forest">
    <i class="fas fa-undo-alt me-1"></i> Proses Serah & Pengembalian
  </a>
</div>

<!-- Filters Toolbar -->
<div class="lk-card p-3 mb-4">
  <form action="<?= baseUrl('admin/rentals.php') ?>" method="GET" class="row g-2 align-items-end">
    <!-- Status Filter -->
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Status Pesanan</label>
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua Status</option>
        <option value="pending_payment" <?= $statusFilter === 'pending_payment' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
        <option value="pending_verification" <?= $statusFilter === 'pending_verification' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Siap Diambil</option>
        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Sedang Disewa (Aktif)</option>
        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Selesai</option>
        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
        <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Kedaluwarsa</option>
      </select>
    </div>

    <!-- Search keyword -->
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Cari Nama / No. Order / HP</label>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / No. Order..." value="<?= htmlspecialchars($searchQuery) ?>">
    </div>

    <!-- Start Date -->
    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Dari Tanggal Mulai</label>
      <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterStart) ?>">
    </div>

    <!-- End Date -->
    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
      <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterEnd) ?>">
    </div>

    <!-- Submit & Reset -->
    <div class="col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-forest flex-grow-1" style="height: 48px;">
        <i class="fas fa-filter me-1"></i> Filter
      </button>
      <a href="<?= baseUrl('admin/rentals.php') ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" style="height: 48px; width: 44px;" title="Reset filter">
        <i class="fas fa-sync-alt"></i>
      </a>
    </div>
  </form>
</div>

<!-- Table of Rentals -->
<?php if (empty($rentals)): ?>
  <div class="lk-card p-5 text-center text-muted">
    <i class="fas fa-search fa-3x mb-3 text-secondary"></i>
    <h5 class="font-heading">Tidak Ada Pesanan yang Sesuai</h5>
    <p class="small mb-0">Coba ubah kriteria pencarian atau filter status.</p>
  </div>
<?php else: ?>
  <div class="lk-card p-4">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light small text-muted">
          <tr>
            <th>Order ID</th>
            <th>Penyewa</th>
            <th>Periode Sewa</th>
            <th>Unit Alat</th>
            <th>Total Tagihan</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rentals as $r): 
            $grand = $r['total_rental'] + $r['total_deposit'] + $r['penalty'];
            $durDays = max(1, (int)(new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->days);
          ?>
            <tr>
              <td>
                <div class="fw-bold fs-6">#<?= $r['rental_id'] ?></div>
                <div class="small text-muted"><?= formatDate($r['created_at']) ?></div>
              </td>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($r['customer_name']) ?></div>
                <div class="small text-muted">
                  <a href="<?= generateWhatsAppUrl($r['user_number'], "Halo {$r['customer_name']}, terkait sewa #{$r['rental_id']}...") ?>" target="_blank" class="text-success text-decoration-none">
                    <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($r['user_number']) ?>
                  </a>
                </div>
              </td>
              <td>
                <div class="fw-bold small"><?= formatDate($r['start_date']) ?> &rarr; <?= formatDate($r['end_date']) ?></div>
                <div class="small text-muted"><?= $durDays ?> Hari &bull; <?= $r['payment_scheme'] === 'down_payment' ? 'DP 50%' : 'Lunas' ?></div>
              </td>
              <td>
                <span class="badge bg-light text-dark border"><?= $r['total_units'] ?> unit barang</span>
              </td>
              <td>
                <div class="fw-bold text-forest"><?= formatRupiah($grand) ?></div>
                <?php if ($r['penalty'] > 0): ?>
                  <span class="badge bg-danger small">Denda: <?= formatRupiah($r['penalty']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?= getRentalStatusBadge($r['status'], $r['end_date'], (float)$r['penalty'], $r['return_date']) ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <a href="<?= baseUrl('pages/rental-detail.php?id=' . $r['rental_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Detail Sewa">
                    <i class="fas fa-eye"></i> Detail
                  </a>
                  <?php if (in_array($r['status'], ['approved', 'active'])): ?>
                    <a href="<?= baseUrl('admin/returns.php?id=' . $r['rental_id']) ?>" class="btn btn-sm btn-forest py-1 px-2" title="Kelola Serah / Kembali">
                      <i class="fas fa-exchange-alt"></i> Proses
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
