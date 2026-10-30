<?php
// admin/payments.php - Payment Verification Queue (FR-37, FR-38, FR-44)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireAdmin();
$adminUser = currentUser();
$db = getDB();

// Handle Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrf)) {
        setFlash('error', 'Sesi tidak valid.');
    } elseif ($paymentId > 0) {
        $pStmt = $db->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $pStmt->execute([$paymentId]);
        $payment = $pStmt->fetch();

        if ($payment) {
            $rentalId = (int)$payment['rental_id'];

            if ($action === 'approve') {
                // Update payment to success with paid_at and verified_by (FR-37)
                $db->prepare("
                    UPDATE payments 
                    SET payment_status = 'success', paid_at = NOW(), verified_by = ?, rejection_reason = NULL 
                    WHERE payment_id = ?
                ")->execute([$adminUser['user_id'], $paymentId]);

                // Update rental status to 'approved' (FR-38)
                $db->prepare("UPDATE rentals SET status = 'approved' WHERE rental_id = ?")->execute([$rentalId]);

                setFlash('success', "Pembayaran untuk Pesanan #{$rentalId} sebesar " . formatRupiah($payment['payment_amount']) . " berhasil diverifikasi! Status pesanan kini Siap Diambil.");
            } elseif ($action === 'reject') {
                $reason = trim($_POST['rejection_reason'] ?? '');
                if (empty($reason)) {
                    setFlash('error', 'Alasan penolakan bukti pembayaran wajib diisi.');
                } else {
                    // Update payment to failed with rejection_reason (FR-37)
                    $db->prepare("
                        UPDATE payments 
                        SET payment_status = 'failed', rejection_reason = ?, verified_by = ? 
                        WHERE payment_id = ?
                    ")->execute([$reason, $adminUser['user_id'], $paymentId]);

                    // Return rental to 'pending_payment' so user can re-upload (PRD 7)
                    $db->prepare("UPDATE rentals SET status = 'pending_payment' WHERE rental_id = ?")->execute([$rentalId]);

                    setFlash('warning', "Pembayaran untuk Pesanan #{$rentalId} ditolak. Pesanan dikembalikan ke status Menunggu Pembayaran agar pelanggan dapat mengunggah bukti baru.");
                }
            }
        }
    }
    header('Location: ' . baseUrl('admin/payments.php'));
    exit;
}

$statusFilter = $_GET['status'] ?? 'pending';

$sql = "
    SELECT p.*, r.user_id, r.start_date, r.end_date, r.payment_scheme, r.total_rental, r.total_deposit, r.status as rental_status,
           u.name as customer_name, u.user_number, u.email as customer_email,
           v.name as verifier_name
    FROM payments p
    JOIN rentals r ON r.rental_id = p.rental_id
    JOIN users u ON u.user_id = r.user_id
    LEFT JOIN users v ON v.user_id = p.verified_by
";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " WHERE p.payment_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY (p.payment_status = 'pending') DESC, p.payment_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$adminTitle = 'Verifikasi Pembayaran - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Verifikasi Pembayaran Manual</h1>
    <p class="text-muted small mb-0">Periksa bukti transfer bank dan QRIS dari penyewa untuk mengubah status pesanan menjadi Siap Diambil.</p>
  </div>
</div>

<!-- Status Filters -->
<div class="d-flex gap-2 mb-4">
  <a href="<?= baseUrl('admin/payments.php?status=pending') ?>" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning fw-bold' : 'btn-outline-secondary' ?>">
    <i class="fas fa-clock me-1"></i> Menunggu Verifikasi
  </a>
  <a href="<?= baseUrl('admin/payments.php?status=success') ?>" class="btn btn-sm <?= $statusFilter === 'success' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
    <i class="fas fa-check-circle me-1"></i> Berhasil / Lunas
  </a>
  <a href="<?= baseUrl('admin/payments.php?status=failed') ?>" class="btn btn-sm <?= $statusFilter === 'failed' ? 'btn-danger fw-bold' : 'btn-outline-secondary' ?>">
    <i class="fas fa-times-circle me-1"></i> Ditolak
  </a>
  <a href="<?= baseUrl('admin/payments.php?status=all') ?>" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>">
    Semua Transaksi
  </a>
</div>

<?php if (empty($payments)): ?>
  <div class="lk-card p-5 text-center text-muted">
    <i class="fas fa-receipt fa-3x mb-3 text-secondary"></i>
    <h5 class="font-heading">Tidak Ada Pembayaran dalam Kategori Ini</h5>
    <p class="small mb-0">Semua bukti pembayaran telah diverifikasi.</p>
  </div>
<?php else: ?>
  <div class="lk-card p-4">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light small text-muted">
          <tr>
            <th>ID / Order</th>
            <th>Penyewa</th>
            <th>Tipe & Skema</th>
            <th>Metode</th>
            <th>Nominal Tagihan</th>
            <th>Bukti Bayar</th>
            <th>Status</th>
            <th class="text-end">Aksi Verifikasi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr id="pay-<?= $p['payment_id'] ?>">
              <td>
                <div class="fw-bold">Bayar #<?= $p['payment_id'] ?></div>
                <div class="small"><a href="<?= baseUrl('pages/rental-detail.php?id=' . $p['rental_id']) ?>" target="_blank">Order #<?= $p['rental_id'] ?></a></div>
              </td>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($p['customer_name']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($p['user_number']) ?></div>
              </td>
              <td>
                <span class="badge bg-dark text-capitalize"><?= str_replace('_', ' ', $p['payment_type']) ?></span>
                <div class="small text-muted mt-1"><?= $p['payment_scheme'] === 'down_payment' ? 'Skema DP 50%' : 'Skema Lunas' ?></div>
              </td>
              <td class="text-capitalize">
                <span class="badge bg-light text-dark border"><?= str_replace('_', ' ', $p['payment_method']) ?></span>
              </td>
              <td class="fw-bold text-forest fs-6">
                <?= formatRupiah($p['payment_amount']) ?>
              </td>
              <td>
                <?php if (!empty($p['proof_image'])): ?>
                  <button type="button" class="btn btn-sm btn-outline-forest py-1 px-2" data-bs-toggle="modal" data-bs-target="#proofModal-<?= $p['payment_id'] ?>">
                    <i class="fas fa-file-invoice me-1"></i> Lihat Bukti
                  </button>

                  <!-- Proof Lightbox Modal -->
                  <div class="modal fade" id="proofModal-<?= $p['payment_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title font-heading fs-6">Bukti Bayar: Order #<?= $p['rental_id'] ?> - <?= formatRupiah($p['payment_amount']) ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-3">
                          <img src="<?= baseUrl('api/download-doc.php?type=payment&id=' . $p['payment_id']) ?>" alt="Bukti Transfer" class="img-fluid rounded shadow-sm" style="max-height: 500px;">
                        </div>
                        <div class="modal-footer">
                          <a href="<?= baseUrl('api/download-doc.php?type=payment&id=' . $p['payment_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i> Buka Gambar Asli
                          </a>
                          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php elseif ($p['payment_method'] === 'cash'): ?>
                  <span class="badge bg-info text-dark">Tunai di Toko</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Belum Upload</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['payment_status'] === 'success'): ?>
                  <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Berhasil</span>
                  <div class="small text-muted mt-1" style="font-size: 11px;">Oleh: <?= htmlspecialchars($p['verifier_name'] ?? 'Admin') ?></div>
                <?php elseif ($p['payment_status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>
                <?php elseif ($p['payment_status'] === 'failed'): ?>
                  <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Ditolak</span>
                  <?php if (!empty($p['rejection_reason'])): ?>
                    <div class="small text-danger mt-1" style="font-size: 11px;"><?= htmlspecialchars($p['rejection_reason']) ?></div>
                  <?php endif; ?>
                <?php elseif ($p['payment_status'] === 'expired'): ?>
                  <span class="badge bg-secondary">Kedaluwarsa</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($p['payment_status'] === 'pending' || $p['payment_method'] === 'cash'): ?>
                  <div class="d-inline-flex gap-1">
                    <!-- Approve Button -->
                    <form action="<?= baseUrl('admin/payments.php') ?>" method="POST" onsubmit="return confirm('Konfirmasi pembayaran <?= formatRupiah($p['payment_amount']) ?> untuk Order #<?= $p['rental_id'] ?> valid?')">
                      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                      <input type="hidden" name="action" value="approve">
                      <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success py-1 px-2" title="Setujui Pembayaran">
                        <i class="fas fa-check"></i> Setujui
                      </button>
                    </form>

                    <!-- Reject Button -->
                    <?php if ($p['payment_method'] !== 'cash'): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#rejectPayModal-<?= $p['payment_id'] ?>" title="Tolak Bukti">
                        <i class="fas fa-times"></i> Tolak
                      </button>

                      <!-- Reject Modal -->
                      <div class="modal fade" id="rejectPayModal-<?= $p['payment_id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content text-start">
                            <form action="<?= baseUrl('admin/payments.php') ?>" method="POST">
                              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                              <input type="hidden" name="action" value="reject">
                              <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">

                              <div class="modal-header">
                                <h5 class="modal-title font-heading fs-6">Tolak Bukti Pembayaran #<?= $p['payment_id'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <label class="form-label small fw-bold">Alasan Penolakan Bukti <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Contoh: Nominal transfer kurang dari tagihan, bukti transfer palsu/buram, atau dana belum masuk mutasi bank." required></textarea>
                                <div class="form-text small">Penyewa akan menerima notifikasi alasan ini dan dapat mengunggah bukti ulang.</div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger btn-sm">Tolak Pembayaran</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
