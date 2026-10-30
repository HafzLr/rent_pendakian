<?php
// admin/returns.php - Handover (Pickup), Return, and Penalty Management (FR-39, FR-40, FR-41)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireAdmin();
$adminUser = currentUser();
$db = getDB();

$today = date('Y-m-d');
$selectedRentalId = (int)($_GET['id'] ?? 0);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rentalId = (int)($_POST['rental_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrf)) {
        setFlash('error', 'Sesi tidak valid.');
    } elseif ($rentalId > 0) {
        $stmt = $db->prepare("SELECT * FROM rentals WHERE rental_id = ?");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();

        if ($rental) {
            // ACTION 1: Handover Gear (Set status to 'active') - FR-39
            if ($action === 'handover_gear') {
                // If scheme is DP, record settlement payment if checked
                $settleAmount = (float)($_POST['settlement_amount'] ?? 0);
                $settleMethod = $_POST['settlement_method'] ?? 'cash';

                if ($rental['payment_scheme'] === 'down_payment' && $settleAmount > 0) {
                    $insertPay = $db->prepare("
                        INSERT INTO payments (
                            rental_id, payment_type, payment_amount, payment_method,
                            payment_status, paid_at, verified_by, created_at
                        ) VALUES (?, 'settlement', ?, ?, 'success', NOW(), ?, NOW())
                    ");
                    $insertPay->execute([$rentalId, $settleAmount, $settleMethod, $adminUser['user_id']]);
                }

                $db->prepare("UPDATE rentals SET status = 'active' WHERE rental_id = ?")->execute([$rentalId]);
                setFlash('success', "Perlengkapan untuk Pesanan #{$rentalId} berhasil diserahkan ke penyewa! Status pesanan kini Sedang Disewa (Aktif).");
            }

            // ACTION 2: Process Return & Penalty & Refund Deposit - FR-40 & FR-41
            elseif ($action === 'process_return') {
                $penaltyAmount = max(0, (float)($_POST['penalty_amount'] ?? 0));
                $penaltyNotes = trim($_POST['penalty_notes'] ?? '');
                $returnDate = !empty($_POST['return_date']) ? trim($_POST['return_date']) : date('Y-m-d');

                // If penalty > 0, record penalty payment line if collected (FR-30)
                if ($penaltyAmount > 0) {
                    $penaltyMethod = $_POST['penalty_method'] ?? 'cash';
                    $insertPenPay = $db->prepare("
                        INSERT INTO payments (
                            rental_id, payment_type, payment_amount, payment_method,
                            payment_status, paid_at, verified_by, created_at
                        ) VALUES (?, 'penalty', ?, ?, 'success', NOW(), ?, NOW())
                    ");
                    $insertPenPay->execute([$rentalId, $penaltyAmount, $penaltyMethod, $adminUser['user_id']]);
                }

                // Calculate deposit refund (deposit minus penalty if not paid separately)
                $depositRefunded = max(0, (float)$rental['total_deposit']);
                
                $updateRental = $db->prepare("
                    UPDATE rentals 
                    SET status = 'completed', 
                        return_date = ?, 
                        penalty = ?, 
                        penalty_notes = ?,
                        deposit_refunded = ?,
                        deposit_refunded_at = NOW()
                    WHERE rental_id = ?
                ");
                $updateRental->execute([$returnDate, $penaltyAmount, $penaltyNotes, $depositRefunded, $rentalId]);

                setFlash('success', "Pengembalian barang untuk Pesanan #{$rentalId} selesai dicatat! Uang jaminan deposit " . formatRupiah($depositRefunded) . " telah dikembalikan ke penyewa. Status: Selesai.");
            }
        }
    }
    header('Location: ' . baseUrl('admin/returns.php'));
    exit;
}

// Fetch single rental if selected
$selectedRental = null;
$selectedItems = [];
$selectedPayments = [];
if ($selectedRentalId > 0) {
    $sStmt = $db->prepare("
        SELECT r.*, u.name as customer_name, u.user_number, u.email as customer_email 
        FROM rentals r
        JOIN users u ON u.user_id = r.user_id
        WHERE r.rental_id = ?
    ");
    $sStmt->execute([$selectedRentalId]);
    $selectedRental = $sStmt->fetch();

    if ($selectedRental) {
        $iStmt = $db->prepare("
            SELECT ri.*, pv.variant_name, p.product_name, p.image
            FROM rental_items ri
            JOIN product_variants pv ON pv.variant_id = ri.variant_id
            JOIN products p ON p.product_id = pv.product_id
            WHERE ri.rental_id = ?
        ");
        $iStmt->execute([$selectedRentalId]);
        $selectedItems = $iStmt->fetchAll();

        $pStmt = $db->prepare("SELECT * FROM payments WHERE rental_id = ?");
        $pStmt->execute([$selectedRentalId]);
        $selectedPayments = $pStmt->fetchAll();
    }
}

// Fetch lists:
// 1. Approved rentals waiting for pickup
$approvedList = $db->query("
    SELECT r.*, u.name as customer_name, u.user_number
    FROM rentals r
    JOIN users u ON u.user_id = r.user_id
    WHERE r.status = 'approved'
    ORDER BY r.start_date ASC
")->fetchAll();

// 2. Active rentals currently with customer
$activeList = $db->query("
    SELECT r.*, u.name as customer_name, u.user_number
    FROM rentals r
    JOIN users u ON u.user_id = r.user_id
    WHERE r.status = 'active'
    ORDER BY (r.end_date < CURDATE()) DESC, r.end_date ASC
")->fetchAll();

$adminTitle = 'Serah Terima & Pengembalian - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Operasional Serah Terima & Pengembalian Alat</h1>
    <p class="text-muted small mb-0">Catat penyerahan barang saat diambil penyewa, inspeksi pengembalian barang, hitung denda, dan catat refund deposit.</p>
  </div>
</div>

<!-- If a specific rental is selected for action -->
<?php if ($selectedRental): 
  $isOverdue = ($selectedRental['status'] === 'active' && $today > $selectedRental['end_date']);
  $overdueDays = 0;
  if ($isOverdue) {
      $endDt = new DateTime($selectedRental['end_date']);
      $todayDt = new DateTime($today);
      $overdueDays = (int)$endDt->diff($todayDt)->days;
  }
  $suggestedPenalty = $overdueDays * DAILY_PENALTY_RATE;

  // Calculate paid amount so far
  $paidAmount = 0.0;
  foreach ($selectedPayments as $sp) {
      if ($sp['payment_status'] === 'success') {
          $paidAmount += (float)$sp['payment_amount'];
      }
  }
  $totalBill = (float)($selectedRental['total_rental'] + $selectedRental['total_deposit']);
  $remainingSettlement = max(0, $totalBill - $paidAmount);
?>
  <div class="lk-card p-4 mb-4 border-start border-4 border-success">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
      <div>
        <span class="badge bg-dark mb-1">Fokus Transaksi</span>
        <h4 class="font-heading fs-4 mb-0">Pesanan Sewa #<?= $selectedRental['rental_id'] ?> &bull; <?= htmlspecialchars($selectedRental['customer_name']) ?></h4>
      </div>
      <div>
        <?= getRentalStatusBadge($selectedRental['status'], $selectedRental['end_date'], (float)$selectedRental['penalty'], $selectedRental['return_date']) ?>
      </div>
    </div>

    <div class="row g-4">
      <!-- Items Summary -->
      <div class="col-md-5 border-end">
        <h6 class="fw-bold mb-2 small text-muted text-uppercase">Kelengkapan Alat Sewa:</h6>
        <ul class="list-group list-group-flush mb-3 small">
          <?php foreach ($selectedItems as $si): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-bold"><?= htmlspecialchars($si['product_name']) ?></div>
                <span class="text-muted">Varian: <?= htmlspecialchars($si['variant_name']) ?></span>
              </div>
              <span class="badge bg-forest px-2 py-1"><?= $si['quantity'] ?> unit</span>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="small text-muted">
          <div><strong>Jadwal Sewa:</strong> <?= formatDate($selectedRental['start_date']) ?> s.d. <?= formatDate($selectedRental['end_date']) ?></div>
          <div><strong>Total Tagihan:</strong> <?= formatRupiah($totalBill) ?></div>
          <div><strong>Total Terbayar:</strong> <?= formatRupiah($paidAmount) ?></div>
        </div>
      </div>

      <!-- Action Form Column -->
      <div class="col-md-7">
        <?php if ($selectedRental['status'] === 'approved'): ?>
          <!-- Handover Form (FR-39) -->
          <h5 class="font-heading fs-5 text-forest mb-2">
            <i class="fas fa-handshake me-1"></i> Penyerahan Alat ke Penyewa
          </h5>
          <p class="small text-muted mb-3">
            Pastikan penyewa menunjukkan KTP fisik, dan cek kelengkapan barang bersama sebelum diserahkan.
          </p>

          <form action="<?= baseUrl('admin/returns.php') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="handover_gear">
            <input type="hidden" name="rental_id" value="<?= $selectedRental['rental_id'] ?>">

            <?php if ($selectedRental['payment_scheme'] === 'down_payment' && $remainingSettlement > 0): ?>
              <div class="p-3 rounded bg-warning-subtle border border-warning mb-3">
                <div class="fw-bold text-dark mb-1">Pelunasan Skema DP 50%:</div>
                <div class="fs-5 fw-bold text-forest mb-2">Sisa Tagihan: <?= formatRupiah($remainingSettlement) ?></div>
                <div class="row g-2">
                  <div class="col-sm-6">
                    <label class="form-label small mb-1">Nominal Pelunasan:</label>
                    <input type="number" name="settlement_amount" class="form-control form-control-sm" value="<?= $remainingSettlement ?>" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label small mb-1">Metode Pelunasan:</label>
                    <select name="settlement_method" class="form-select form-select-sm">
                      <option value="cash">Tunai di Kasir (Cash)</option>
                      <option value="qris">QRIS Toko</option>
                      <option value="bank_transfer">Transfer Bank</option>
                    </select>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-forest py-2 w-100" onclick="return confirm('Serahkan perlengkapan kepada penyewa dan aktifkan status sewa?')">
              <i class="fas fa-check-circle me-1"></i> Konfirmasi Penyerahan (Aktifkan Sewa)
            </button>
          </form>

        <?php elseif ($selectedRental['status'] === 'active'): ?>
          <!-- Return & Penalty Form (FR-40 & FR-41) -->
          <h5 class="font-heading fs-5 text-forest mb-2">
            <i class="fas fa-undo me-1"></i> Catat Pengembalian Barang & Denda
          </h5>

          <?php if ($isOverdue): ?>
            <div class="alert alert-danger py-2 small mb-3">
              <i class="fas fa-exclamation-triangle me-1"></i> <strong>Barang Terlambat <?= $overdueDays ?> Hari!</strong> (Batas: <?= formatDate($selectedRental['end_date']) ?>). Saran denda otomatis: <strong><?= formatRupiah($suggestedPenalty) ?></strong> (Rp 20.000/hari).
            </div>
          <?php endif; ?>

          <form action="<?= baseUrl('admin/returns.php') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="process_return">
            <input type="hidden" name="rental_id" value="<?= $selectedRental['rental_id'] ?>">

            <div class="row g-2 mb-3">
              <div class="col-sm-6">
                <label class="form-label small fw-bold">Tanggal Pengembalian Fisik:</label>
                <input type="date" name="return_date" class="form-control form-control-sm" value="<?= $today ?>" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-bold">Nominal Denda (Jika Ada):</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">Rp</span>
                  <input type="number" name="penalty_amount" class="form-control" value="<?= $suggestedPenalty ?>" min="0">
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Catatan Kerusakan / Keterlambatan:</label>
              <textarea name="penalty_notes" class="form-control form-control-sm" rows="2" placeholder="Contoh: Terlambat 1 hari, pasak tenda hilang 2 buah diganti Rp 20.000..."></textarea>
            </div>

            <div class="p-3 rounded bg-light border small mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span>Total Uang Deposit Penyewa:</span>
                <span class="fw-bold"><?= formatRupiah($selectedRental['total_deposit']) ?></span>
              </div>
              <div class="d-flex justify-content-between fw-bold text-success">
                <span>Pengembalian Deposit Penuh:</span>
                <span><?= formatRupiah($selectedRental['total_deposit']) ?></span>
              </div>
              <div class="small text-muted mt-1">* Jika ada denda/kerusakan, biaya dapat dipotong dari deposit atau dibayar tunai.</div>
            </div>

            <button type="submit" class="btn btn-forest py-2 w-100" onclick="return confirm('Selesaikan pengembalian barang dan kembalikan deposit?')">
              <i class="fas fa-check-double me-1"></i> Selesaikan Pengembalian & Refund Deposit
            </button>
          </form>
        <?php else: ?>
          <div class="alert alert-info small mb-0">
            Pesanan ini berstatus <strong><?= htmlspecialchars($selectedRental['status']) ?></strong> dan tidak memerlukan tindakan serah-terima operasional.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Two Operasional Queues Row -->
<div class="row g-4">
  <!-- Queue 1: Ready to Pickup -->
  <div class="col-lg-6">
    <div class="lk-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-heading fs-5 mb-0">
          <i class="fas fa-dolly text-forest me-2"></i> 1. Siap Diambil di Toko (<?= count($approvedList) ?>)
        </h5>
      </div>

      <?php if (empty($approvedList)): ?>
        <p class="text-muted small py-4 text-center">Tidak ada pesanan menunggu pengambilan.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead class="table-light">
              <tr>
                <th>Order</th>
                <th>Penyewa</th>
                <th>Tanggal Ambil</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($approvedList as $ap): ?>
                <tr>
                  <td><strong>#<?= $ap['rental_id'] ?></strong></td>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($ap['customer_name']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($ap['user_number']) ?></div>
                  </td>
                  <td>
                    <?= formatDate($ap['start_date']) ?><br>
                    <?= $ap['start_date'] <= $today ? '<span class="badge bg-success">Hari Ini</span>' : '<span class="badge bg-light text-dark border">Mendatang</span>' ?>
                  </td>
                  <td>
                    <a href="<?= baseUrl('admin/returns.php?id=' . $ap['rental_id']) ?>" class="btn btn-sm btn-forest py-1 px-2">
                      <i class="fas fa-box-open me-1"></i> Serahkan
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

  <!-- Queue 2: Active & Returning -->
  <div class="col-lg-6">
    <div class="lk-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-heading fs-5 mb-0">
          <i class="fas fa-undo-alt text-forest me-2"></i> 2. Sedang Disewa & Pengembalian (<?= count($activeList) ?>)
        </h5>
      </div>

      <?php if (empty($activeList)): ?>
        <p class="text-muted small py-4 text-center">Tidak ada perlengkapan yang sedang dipinjam saat ini.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead class="table-light">
              <tr>
                <th>Order</th>
                <th>Penyewa</th>
                <th>Jadwal Kembali</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($activeList as $ac): 
                $late = ($today > $ac['end_date']);
              ?>
                <tr class="<?= $late ? 'table-danger' : '' ?>">
                  <td><strong>#<?= $ac['rental_id'] ?></strong></td>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($ac['customer_name']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($ac['user_number']) ?></div>
                  </td>
                  <td>
                    <?= formatDate($ac['end_date']) ?><br>
                    <?= $late ? '<span class="badge bg-danger">Terlambat!</span>' : '<span class="badge bg-info">Aktif</span>' ?>
                  </td>
                  <td>
                    <a href="<?= baseUrl('admin/returns.php?id=' . $ac['rental_id']) ?>" class="btn btn-sm btn-forest py-1 px-2">
                      <i class="fas fa-clipboard-check me-1"></i> Cek Kembali
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

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
