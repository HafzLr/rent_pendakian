<?php
// pages/payment.php - Payment Instructions & Proof Upload
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireLogin();
$user = currentUser();
$rentalId = (int)($_GET['id'] ?? 0);

if (!$rentalId) {
    header('Location: ' . baseUrl('pages/my-rentals.php'));
    exit;
}

$db = getDB();

// Fetch rental and verify owner (or admin)
$stmt = $db->prepare("SELECT * FROM rentals WHERE rental_id = ?");
$stmt->execute([$rentalId]);
$rental = $stmt->fetch();

if (!$rental) {
    setFlash('error', 'Pesanan tidak ditemukan.');
    header('Location: ' . baseUrl('pages/my-rentals.php'));
    exit;
}

if (!isAdmin() && $rental['user_id'] != $user['user_id']) {
    setFlash('error', 'Akses ditolak ke pesanan ini.');
    header('Location: ' . baseUrl('pages/my-rentals.php'));
    exit;
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

// Fetch active payment
$payStmt = $db->prepare("
    SELECT * FROM payments 
    WHERE rental_id = ? 
    ORDER BY payment_id DESC 
    LIMIT 1
");
$payStmt->execute([$rentalId]);
$payment = $payStmt->fetch();

$uploadErrors = [];

// Handle payment proof upload (FR-26 & FR-27 & FR-28)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_proof') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $uploadErrors[] = 'Sesi tidak valid, silakan refresh halaman.';
    } elseif ($rental['status'] === 'expired') {
        $uploadErrors[] = 'Pesanan ini sudah kedaluwarsa, tidak dapat mengunggah bukti pembayaran.';
    } elseif ($rental['status'] === 'cancelled' || $rental['status'] === 'rejected') {
        $uploadErrors[] = 'Pesanan tidak aktif.';
    } else {
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['proof_image'];
            if ($file['size'] > 5 * 1024 * 1024) {
                $uploadErrors[] = 'Ukuran bukti transfer maksimal 5 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                    $uploadErrors[] = 'Format bukti harus JPG, PNG, atau WEBP.';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFileName = 'proof_' . $rentalId . '_' . bin2hex(random_bytes(10)) . '.' . strtolower($ext);
                    $targetDir = __DIR__ . '/../uploads/payments/';
                    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                    $targetFile = $targetDir . $newFileName;

                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        $proofPath = 'uploads/payments/' . $newFileName;
                        
                        // Update payment and rental status (FR-27)
                        $updatePay = $db->prepare("
                            UPDATE payments 
                            SET proof_image = ?, payment_status = 'pending', rejection_reason = NULL 
                            WHERE payment_id = ?
                        ");
                        $updatePay->execute([$proofPath, $payment['payment_id']]);

                        $updateRental = $db->prepare("
                            UPDATE rentals 
                            SET status = 'pending_verification' 
                            WHERE rental_id = ?
                        ");
                        $updateRental->execute([$rentalId]);

                        setFlash('success', 'Bukti pembayaran berhasil diunggah! Status pesanan kini Menunggu Verifikasi Admin.');
                        header('Location: ' . baseUrl('pages/payment.php?id=' . $rentalId));
                        exit;
                    } else {
                        $uploadErrors[] = 'Gagal menyimpan file bukti di server.';
                    }
                }
            }
        } else {
            $uploadErrors[] = 'Pilih file foto struk/screenshot bukti transfer.';
        }
    }
}

// Calculate deadline: created_at + 24 hours (PRD 6 item 5)
$createdAtTs = strtotime($rental['created_at']);
$deadlineTs = $createdAtTs + (PAYMENT_DEADLINE_HOURS * 3600);
$nowTs = time();
$isExpired = ($nowTs > $deadlineTs && $rental['status'] === 'pending_payment');

if ($isExpired && $rental['status'] === 'pending_payment') {
    $db->prepare("UPDATE rentals SET status = 'expired' WHERE rental_id = ?")->execute([$rentalId]);
    $db->prepare("UPDATE payments SET payment_status = 'expired' WHERE rental_id = ? AND payment_status = 'pending'")->execute([$rentalId]);
    $rental['status'] = 'expired';
}

$pageTitle = 'Instruksi Pembayaran Sewa #' . $rentalId . ' - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item"><a href="<?= baseUrl('pages/my-rentals.php') ?>">Sewa Saya</a></li>
        <li class="breadcrumb-item active" aria-current="page">Pembayaran #<?= $rentalId ?></li>
      </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h1 class="font-heading fs-2 mb-1">Pembayaran Pesanan Sewa #<?= $rentalId ?></h1>
        <p class="text-muted small mb-0">Dibuat pada: <?= formatDateTime($rental['created_at']) ?></p>
      </div>
      <div>
        <?= getRentalStatusBadge($rental['status'], $rental['end_date'], (float)$rental['penalty'], $rental['return_date']) ?>
      </div>
    </div>
  </div>

  <?php if (!empty($uploadErrors)): ?>
    <div class="alert alert-danger py-2 small mb-4">
      <ul class="mb-0 ps-3">
        <?php foreach ($uploadErrors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Left Column: Payment Details & Upload -->
    <div class="col-lg-7">
      
      <!-- Deadline Timer Banner -->
      <?php if ($rental['status'] === 'pending_payment'): ?>
        <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4">
          <div class="d-flex align-items-center gap-3">
            <i class="fas fa-hourglass-half fa-2x text-warning"></i>
            <div>
              <div class="fw-bold">Batas Waktu Pembayaran</div>
              <div class="small text-muted">Selesaikan pembayaran sebelum: <strong><?= date('d M Y, H:i', $deadlineTs) ?> WIB</strong></div>
            </div>
          </div>
          <span class="badge bg-warning text-dark px-3 py-2">24 Jam</span>
        </div>
      <?php elseif ($rental['status'] === 'pending_verification'): ?>
        <div class="alert alert-info d-flex align-items-center gap-3 p-3 mb-4">
          <i class="fas fa-search fa-2x text-info"></i>
          <div>
            <div class="fw-bold">Bukti Pembayaran Sedang Diverifikasi Admin</div>
            <div class="small">Admin toko RentGear sedang memeriksa mutasi rekening Anda. Pesanan akan disetujui dalam 12 jam kerja.</div>
          </div>
        </div>
      <?php elseif ($rental['status'] === 'approved'): ?>
        <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-4">
          <i class="fas fa-check-circle fa-2x text-success"></i>
          <div>
            <div class="fw-bold">Pembayaran Telah Diverifikasi & Disetujui!</div>
            <div class="small">Perlengkapan siap diambil di toko pada tanggal mulai sewa: <strong><?= formatDate($rental['start_date']) ?></strong>.</div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Rejection notice if payment rejected -->
      <?php if ($payment && $payment['payment_status'] === 'failed'): ?>
        <div class="alert alert-danger p-3 mb-4">
          <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> Bukti Pembayaran Sebelumnya Ditolak Admin</div>
          <div class="small mb-2"><strong>Alasan:</strong> <?= htmlspecialchars($payment['rejection_reason'] ?: 'Nominal tidak sesuai atau bukti mutasi buram.') ?></div>
          <div class="small">Silakan periksa kembali dan unggah ulang bukti pembayaran yang benar sebelum batas waktu berakhir.</div>
        </div>
      <?php endif; ?>

      <!-- Payment Amount Card -->
      <div class="lk-card p-4 mb-4">
        <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
          <i class="fas fa-receipt text-forest me-2"></i> Rincian Tagihan Saat Ini
        </h5>
        
        <div class="p-3 rounded mb-3" style="background-color: var(--lk-primary-tint); border: 1px solid var(--lk-forest);">
          <div class="small text-muted mb-1">
            <?= $rental['payment_scheme'] === 'down_payment' ? 'TAGIHAN AWAL (UANG MUKA / DP 50%):' : 'TAGIHAN PEMBAYARAN LUNAS (100%):' ?>
          </div>
          <div class="fs-3 fw-bold text-forest">
            <?= formatRupiah($payment['payment_amount'] ?? ($rental['total_rental'] + $rental['total_deposit'])) ?>
          </div>
          <?php if ($rental['payment_scheme'] === 'down_payment'): ?>
            <div class="small text-muted mt-1">
              * Sisa pelunasan sebesar <strong><?= formatRupiah(($rental['total_rental'] + $rental['total_deposit']) - ($payment['payment_amount'] ?? 0)) ?></strong> dibayarkan saat mengambil alat di toko.
            </div>
          <?php endif; ?>
        </div>

        <!-- Instructions per Method (FR-25) -->
        <?php if ($payment['payment_method'] === 'bank_transfer'): ?>
          <div class="mb-3">
            <h6 class="fw-bold mb-2"><i class="fas fa-university text-forest me-1"></i> Rekening Transfer Bank Resmi</h6>
            <div class="p-3 rounded border bg-light mb-2">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold fs-6"><?= BANK_NAME ?></div>
                  <div class="fs-5 font-monospace text-forest fw-bold my-1" id="bankAccNo"><?= BANK_ACCOUNT ?></div>
                  <div class="small text-muted">a.n. <?= BANK_HOLDER ?></div>
                </div>
                <button type="button" class="btn btn-outline-forest btn-sm" onclick="copyText('<?= BANK_ACCOUNT ?>', this)">
                  <i class="far fa-copy me-1"></i> Salin No. Rek
                </button>
              </div>
            </div>

            <div class="p-3 rounded border bg-light">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold fs-6"><?= BANK_2_NAME ?></div>
                  <div class="fs-5 font-monospace text-forest fw-bold my-1"><?= BANK_2_ACCOUNT ?></div>
                  <div class="small text-muted">a.n. <?= BANK_2_HOLDER ?></div>
                </div>
                <button type="button" class="btn btn-outline-forest btn-sm" onclick="copyText('<?= BANK_2_ACCOUNT ?>', this)">
                  <i class="far fa-copy me-1"></i> Salin No. Rek
                </button>
              </div>
            </div>
          </div>
        <?php elseif ($payment['payment_method'] === 'qris'): ?>
          <div class="text-center p-3 rounded border bg-light mb-3">
            <h6 class="fw-bold mb-2"><i class="fas fa-qrcode text-forest me-1"></i> Scan Kode QRIS RentGear</h6>
            <p class="small text-muted mb-3">Buka aplikasi mobile banking atau e-wallet (BCA, GoPay, OVO, Dana, ShopeePay) dan scan kode QR di bawah:</p>
            <div class="d-inline-block p-3 bg-white rounded shadow-sm border mb-2">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=RENTGEAR_ORDER_<?= $rentalId ?>_AMOUNT_<?= $payment['payment_amount'] ?>" alt="Kode QRIS Pembayaran" class="img-fluid" style="width: 200px; height: 200px;">
            </div>
            <div class="small text-muted">NMID: <?= QRIS_NMID ?> &bull; RENTGEAR OUTDOOR</div>
          </div>
        <?php elseif ($payment['payment_method'] === 'cash'): ?>
          <div class="alert alert-info p-3 mb-3">
            <h6 class="fw-bold mb-1"><i class="fas fa-hand-holding-usd me-1"></i> Pembayaran Tunai di Toko (Cash)</h6>
            <p class="small mb-2">
              Silakan datang langsung ke toko RentGear pada tanggal mulai sewa (<strong><?= formatDate($rental['start_date']) ?></strong>) untuk melakukan pembayaran tunai dan serah terima perlengkapan.
            </p>
            <div class="small text-muted">Tidak perlu mengunggah bukti transfer untuk metode pembayaran tunai. Admin akan memvalidasi saat Anda tiba.</div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Upload Proof Form (FR-26 & FR-28) -->
      <?php if ($payment['payment_method'] !== 'cash' && in_array($rental['status'], ['pending_payment', 'pending_verification'])): ?>
        <div class="lk-card p-4">
          <h5 class="font-heading fs-5 mb-2">
            <i class="fas fa-cloud-upload-alt text-forest me-2"></i> Unggah Bukti Pembayaran
          </h5>
          <p class="small text-muted mb-3">
            Unggah foto struk transfer ATM, tangkapan layar m-Banking, atau struk QRIS yang menampilkan nominal dan tanggal transaksi.
          </p>

          <form action="<?= baseUrl('pages/payment.php?id=' . $rentalId) ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="upload_proof">

            <div class="mb-3">
              <input type="file" class="form-control image-preview-input" name="proof_image" accept="image/jpeg,image/png,image/webp" data-preview-target="proofPreview" required>
            </div>

            <div class="mb-3 text-center">
              <img id="proofPreview" src="#" alt="Pratinjau Bukti" class="img-thumbnail d-none" style="max-height: 220px;">
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-forest py-2">
                <i class="fas fa-paper-plane me-1"></i> Kirim Bukti Pembayaran
              </button>
            </div>
          </form>
        </div>
      <?php elseif (!empty($payment['proof_image'])): ?>
        <div class="lk-card p-4">
          <h5 class="font-heading fs-5 mb-3"><i class="fas fa-image text-forest me-2"></i> Bukti Pembayaran Terunggah</h5>
          <div class="text-center p-3 rounded border bg-light">
            <img src="<?= baseUrl('api/download-doc.php?type=payment&id=' . $payment['payment_id']) ?>" alt="Bukti Pembayaran" class="img-fluid rounded shadow-sm" style="max-height: 280px;">
            <div class="small text-muted mt-2">Diunggah untuk Verifikasi Admin</div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- Right Column: Order Items Summary -->
    <div class="col-lg-5">
      <div class="lk-card p-4">
        <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">Rincian Sewa #<?= $rentalId ?></h5>

        <div class="mb-3 small">
          <div class="text-muted">Periode Sewa:</div>
          <div class="fw-bold text-dark fs-6"><?= formatDate($rental['start_date']) ?> &rarr; <?= formatDate($rental['end_date']) ?></div>
          <div class="text-muted">Metode Pengambilan: <span class="badge bg-light text-dark border">Ambil di Toko</span></div>
        </div>

        <div class="border-top border-bottom py-3 mb-3">
          <div class="small fw-bold text-muted mb-2">Item Perlengkapan:</div>
          <?php foreach ($items as $it): ?>
            <div class="d-flex justify-content-between align-items-center mb-2 small">
              <div>
                <div class="fw-bold"><?= htmlspecialchars($it['product_name']) ?></div>
                <div class="text-muted"><?= $it['quantity'] ?> × <?= htmlspecialchars($it['variant_name']) ?></div>
              </div>
              <div class="fw-bold text-forest"><?= formatRupiah($it['subtotal']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between small text-muted mb-2">
          <span>Subtotal Sewa:</span>
          <span class="fw-bold text-dark"><?= formatRupiah($rental['total_rental']) ?></span>
        </div>
        <div class="d-flex justify-content-between small text-muted mb-2">
          <span>Uang Jaminan (Deposit):</span>
          <span class="fw-bold text-dark"><?= formatRupiah($rental['total_deposit']) ?></span>
        </div>
        <div class="d-flex justify-content-between fs-6 fw-bold border-top pt-2 mb-4">
          <span>Total Keseluruhan:</span>
          <span class="text-forest"><?= formatRupiah($rental['total_rental'] + $rental['total_deposit']) ?></span>
        </div>

        <!-- WhatsApp Store Quick Contact (FR-35) -->
        <div class="p-3 rounded bg-light border text-center mb-3">
          <div class="small text-muted mb-2">Butuh bantuan konfirmasi cepat?</div>
          <?php 
            $waMsg = "Halo Admin RentGear, saya sudah membuat pesanan sewa #{$rentalId} atas nama {$user['name']}. Mohon bantuan konfirmasi pembayarannya.";
          ?>
          <a href="<?= generateWhatsAppUrl(STORE_PHONE, $waMsg) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100">
            <i class="fab fa-whatsapp me-1"></i> Konfirmasi via WhatsApp Toko
          </a>
        </div>

        <div class="d-grid">
          <a href="<?= baseUrl('pages/my-rentals.php') ?>" class="btn btn-outline-forest py-2">
            <i class="fas fa-list-alt me-1"></i> Lihat Daftar Sewa Saya
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Tersalin!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
