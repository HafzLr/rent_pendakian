<?php
// pages/checkout.php - Secure Rental Checkout Flow
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/stock.php';
require_once __DIR__ . '/../helpers/cart.php';

// Only verified accounts can checkout (PRD 5.3 FR-21)
requireVerified();

$user = currentUser();
$cart = getCart();
$totals = getCartTotals();

if (empty($cart['items'])) {
    setFlash('warning', 'Keranjang sewa Anda kosong.');
    header('Location: ' . baseUrl('pages/catalog.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $scheme = $_POST['payment_scheme'] ?? 'full'; // 'down_payment' or 'full'
    $method = $_POST['payment_method'] ?? 'bank_transfer'; // 'bank_transfer', 'qris', 'cash'

    if (!validateCsrfToken($csrf)) {
        $errors[] = 'Sesi tidak valid, silakan coba kembali.';
    }

    if (!in_array($scheme, ['down_payment', 'full'])) {
        $errors[] = 'Skema pembayaran tidak valid.';
    }

    if (!in_array($method, ['bank_transfer', 'qris', 'cash'])) {
        $errors[] = 'Metode pembayaran tidak valid.';
    }

    if (empty($errors)) {
        $db = getDB();
        try {
            // PRD 8.4: Atomic DB transaction with SELECT ... FOR UPDATE
            $db->beginTransaction();

            $startDate = $totals['start_date'];
            $endDate = $totals['end_date'];
            $durationDays = $totals['duration_days'];

            // Lock variants to avoid race condition double bookings
            $variantIds = array_keys($cart['items']);
            $inList = implode(',', array_map('intval', $variantIds));
            
            $lockStmt = $db->query("SELECT variant_id, total_stock FROM product_variants WHERE variant_id IN ($inList) FOR UPDATE");
            $lockedVariants = $lockStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Re-verify availability for all items under the lock
            foreach ($cart['items'] as $item) {
                $varId = (int)$item['variant_id'];
                $reqQty = (int)$item['quantity'];

                $avail = getAvailableStock($varId, $startDate, $endDate);
                if ($avail < $reqQty) {
                    throw new Exception("Stok untuk alat {$item['product_name']} ({$item['variant_name']}) baru saja terpesan oleh pelanggan lain. Sisa tersedia: {$avail} unit.");
                }
            }

            // Calculate exact server-side amounts (PRD 8.3 & 6 item 2)
            $serverRentalTotal = 0.0;
            $serverDepositTotal = 0.0;

            foreach ($cart['items'] as $item) {
                $subtotal = $durationDays * (float)$item['price_per_day'] * (int)$item['quantity'];
                $deposit = (float)$item['deposit_per_unit'] * (int)$item['quantity'];
                $serverRentalTotal += $subtotal;
                $serverDepositTotal += $deposit;
            }

            $serverGrandTotal = $serverRentalTotal + $serverDepositTotal;

            // Determine initial payment amount
            $paymentType = ($scheme === 'down_payment') ? 'down_payment' : 'full';
            $initialPayAmount = ($scheme === 'down_payment') 
                ? round($serverGrandTotal * DP_PERCENTAGE) 
                : $serverGrandTotal;

            // Initial status: pending_payment
            $insertRental = $db->prepare("
                INSERT INTO rentals (
                    user_id, start_date, end_date, payment_scheme,
                    total_rental, total_deposit, penalty, status, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, 0.00, 'pending_payment', NOW()
                )
            ");
            $insertRental->execute([
                $user['user_id'],
                $startDate,
                $endDate,
                $scheme,
                $serverRentalTotal,
                $serverDepositTotal
            ]);
            $rentalId = (int)$db->lastInsertId();

            // Insert rental_items (copy price_per_day - PRD 5.3 FR-23)
            $insertItem = $db->prepare("
                INSERT INTO rental_items (
                    rental_id, variant_id, quantity, price_per_day, subtotal
                ) VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cart['items'] as $item) {
                $subtotal = $durationDays * (float)$item['price_per_day'] * (int)$item['quantity'];
                $insertItem->execute([
                    $rentalId,
                    $item['variant_id'],
                    $item['quantity'],
                    $item['price_per_day'],
                    $subtotal
                ]);
            }

            // Insert initial payment entry
            $insertPayment = $db->prepare("
                INSERT INTO payments (
                    rental_id, payment_type, payment_amount, payment_method,
                    payment_status, created_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $insertPayment->execute([
                $rentalId,
                $paymentType,
                $initialPayAmount,
                $method
            ]);

            $db->commit();

            // Clear rental cart
            clearCart();

            setFlash('success', 'Pesanan sewa berhasil dibuat! Silakan lakukan pembayaran sesuai instruksi.');
            header('Location: ' . baseUrl('pages/payment.php?id=' . $rentalId));
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout Sewa - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item"><a href="<?= baseUrl('pages/cart.php') ?>">Keranjang Sewa</a></li>
        <li class="breadcrumb-item active" aria-current="page">Checkout</li>
      </ol>
    </nav>
    <h1 class="font-heading fs-2 mb-1">Konfirmasi & Pembayaran Sewa</h1>
    <p class="text-muted small">Pilih skema dan metode pembayaran untuk menyelesaikan penyewaan perlengkapan pendakian Anda.</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger py-2 small mb-4">
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="<?= baseUrl('pages/checkout.php') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="row g-4">
      <!-- Left Column: Order Options -->
      <div class="col-lg-7">
        
        <!-- Customer & Pickup Info -->
        <div class="lk-card p-4 mb-4">
          <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
            <i class="fas fa-store text-forest me-2"></i> Metode Pengambilan Alat
          </h5>
          <div class="alert alert-info small mb-3">
            <i class="fas fa-info-circle me-1"></i> <strong>Ambil Mandiri di Toko Fisik (Self-Pickup)</strong><br>
            Sesuai ketentuan, seluruh perlengkapan diambil dan dicek kondisinya bersama di toko kami saat hari pertama sewa.
          </div>
          <div class="p-3 rounded bg-light border small">
            <div class="fw-bold text-dark mb-1"><i class="fas fa-map-marker-alt text-danger me-1"></i> Lokasi Toko:</div>
            <div class="text-muted"><?= STORE_ADDRESS ?></div>
            <div class="text-muted"><i class="far fa-clock me-1"></i> Jam Buka: <?= STORE_HOURS ?></div>
          </div>

          <div class="mt-3 pt-3 border-top small text-muted">
            <div><strong>Penyewa Terdaftar:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['user_number']) ?>)</div>
            <div><strong>Status Identitas:</strong> <?= getUserVerificationBadge($user['verification_status']) ?></div>
          </div>
        </div>

        <!-- Payment Scheme Selection (FR-22) -->
        <div class="lk-card p-4 mb-4">
          <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
            <i class="fas fa-file-invoice-dollar text-forest me-2"></i> Pilih Skema Pembayaran
          </h5>
          <div class="row g-3">
            <!-- Full Payment -->
            <div class="col-md-6">
              <label class="d-block p-3 rounded border h-100 cursor-pointer scheme-card selected-scheme" style="background: var(--lk-blush); border-color: var(--lk-forest) !important;">
                <input type="radio" name="payment_scheme" value="full" checked class="form-check-input me-2" onchange="updateSchemeSummary('full')">
                <span class="fw-bold d-inline-block">Pembayaran Lunas (100%)</span>
                <p class="small text-muted mt-2 mb-1">Bayar lunas seluruh sewa dan deposit di awal. Tinggal ambil alat saat tanggal sewa.</p>
                <div class="fw-bold fs-6 text-forest mt-2"><?= formatRupiah($totals['grand_total']) ?></div>
              </label>
            </div>

            <!-- Down Payment (DP 50%) -->
            <div class="col-md-6">
              <label class="d-block p-3 rounded border h-100 cursor-pointer scheme-card">
                <input type="radio" name="payment_scheme" value="down_payment" class="form-check-input me-2" onchange="updateSchemeSummary('down_payment')">
                <span class="fw-bold d-inline-block">Uang Muka (DP 50%)</span>
                <p class="small text-muted mt-2 mb-1">Bayar 50% untuk mengamankan stok. Pelunasan 50% sisanya dibayar saat pengambilan alat.</p>
                <div class="fw-bold fs-6 text-forest mt-2"><?= formatRupiah($totals['dp_amount']) ?> <small class="text-muted fw-normal">(Tagihan Awal)</small></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Payment Method Selection (FR-25) -->
        <div class="lk-card p-4 mb-4">
          <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">
            <i class="fas fa-wallet text-forest me-2"></i> Pilih Metode Pembayaran
          </h5>
          
          <div class="d-flex flex-column gap-3">
            <label class="d-flex align-items-center justify-content-between p-3 rounded border cursor-pointer method-card">
              <div class="d-flex align-items-center gap-3">
                <input type="radio" name="payment_method" value="bank_transfer" checked class="form-check-input">
                <div>
                  <div class="fw-bold">Transfer Bank (BCA / Mandiri)</div>
                  <div class="small text-muted">Upload bukti transfer setelah pembayaran dilakukan</div>
                </div>
              </div>
              <span class="badge bg-light text-dark border"><i class="fas fa-university"></i> Bank</span>
            </label>

            <label class="d-flex align-items-center justify-content-between p-3 rounded border cursor-pointer method-card">
              <div class="d-flex align-items-center gap-3">
                <input type="radio" name="payment_method" value="qris" class="form-check-input">
                <div>
                  <div class="fw-bold">QRIS (GoPay, OVO, ShopeePay, Dana, BCA Mobile)</div>
                  <div class="small text-muted">Scan kode QR interaktif dan upload bukti pembayaran</div>
                </div>
              </div>
              <span class="badge bg-light text-dark border"><i class="fas fa-qrcode"></i> QRIS</span>
            </label>

            <label class="d-flex align-items-center justify-content-between p-3 rounded border cursor-pointer method-card">
              <div class="d-flex align-items-center gap-3">
                <input type="radio" name="payment_method" value="cash" class="form-check-input">
                <div>
                  <div class="fw-bold">Bayar Tunai di Toko (Cash)</div>
                  <div class="small text-muted">Bayar langsung di kasir toko saat pengambilan alat</div>
                </div>
              </div>
              <span class="badge bg-light text-dark border"><i class="fas fa-money-bill-wave"></i> Tunai</span>
            </label>
          </div>
        </div>

      </div>

      <!-- Right Column: Order Summary -->
      <div class="col-lg-5">
        <div class="lk-card p-4 position-sticky" style="top: 90px;">
          <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">Ringkasan Pesanan</h5>

          <!-- Date banner -->
          <div class="p-2 mb-3 rounded bg-light border small">
            <div class="d-flex justify-content-between">
              <span class="text-muted">Tanggal Sewa:</span>
              <strong class="text-forest"><?= $totals['duration_days'] ?> Hari</strong>
            </div>
            <div class="fw-bold text-dark mt-1">
              <?= formatDate($totals['start_date']) ?> &rarr; <?= formatDate($totals['end_date']) ?>
            </div>
          </div>

          <!-- Items list -->
          <div class="border-bottom pb-3 mb-3" style="max-height: 220px; overflow-y: auto;">
            <?php foreach ($cart['items'] as $item): 
              $sub = $totals['duration_days'] * $item['price_per_day'] * $item['quantity'];
            ?>
              <div class="d-flex justify-content-between align-items-center mb-2 small">
                <div>
                  <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                  <span class="text-muted"><?= $item['quantity'] ?> × <?= htmlspecialchars($item['variant_name']) ?></span>
                </div>
                <div class="fw-bold text-forest"><?= formatRupiah($sub) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Totals Breakdown -->
          <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Total Sewa:</span>
            <span class="text-dark fw-bold"><?= formatRupiah($totals['total_rental']) ?></span>
          </div>
          <div class="d-flex justify-content-between small text-muted mb-3">
            <span>Total Uang Jaminan (Deposit):</span>
            <span class="text-dark fw-bold"><?= formatRupiah($totals['total_deposit']) ?></span>
          </div>

          <div class="d-flex justify-content-between fs-6 fw-bold border-top pt-2 mb-3">
            <span>Total Biaya Keseluruhan:</span>
            <span><?= formatRupiah($totals['grand_total']) ?></span>
          </div>

          <!-- Dynamic Initial Payment Card -->
          <div class="p-3 rounded mb-4" style="background-color: var(--lk-primary-tint); border: 1px solid var(--lk-forest);">
            <div class="small text-forest mb-1 fw-bold">TOTAL TAGIHAN PEMBAYARAN AWAL:</div>
            <div class="fs-4 fw-bold text-forest" id="initialPayDisplay"><?= formatRupiah($totals['grand_total']) ?></div>
            <div class="small text-muted mt-1" id="remainingPayDisplay">Status: Pembayaran Lunas di Awal</div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-forest py-3">
              <i class="fas fa-lock me-1"></i> Buat Pesanan Sekarang
            </button>
          </div>

          <div class="small text-muted text-center" style="font-size: 12px;">
            <i class="fas fa-hourglass-half me-1"></i> Batas waktu pembayaran 24 jam setelah pesanan dibuat sebelum stok dilepas otomatis.
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
const fullAmount = <?= (float)$totals['grand_total'] ?>;
const dpAmount = <?= (float)$totals['dp_amount'] ?>;
const settlementAmount = <?= (float)$totals['settlement_amount'] ?>;

function updateSchemeSummary(scheme) {
    const initialDisplay = document.getElementById('initialPayDisplay');
    const remainingDisplay = document.getElementById('remainingPayDisplay');

    if (scheme === 'down_payment') {
        initialDisplay.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
        remainingDisplay.innerHTML = 'Sisa pelunasan saat ambil barang: <strong>Rp ' + settlementAmount.toLocaleString('id-ID') + '</strong>';
    } else {
        initialDisplay.textContent = 'Rp ' + fullAmount.toLocaleString('id-ID');
        remainingDisplay.textContent = 'Status: Pembayaran Lunas di Awal';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
