<?php
// pages/cart.php - Full Rental Cart Page
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/cart.php';

$cart = getCart();
$totals = getCartTotals();
$currentUser = currentUser();
$minDate = date('Y-m-d');

// Handle form updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_dates') {
        $start = trim($_POST['start_date'] ?? '');
        $end = trim($_POST['end_date'] ?? '');
        $res = setCartDates($start, $end);
        if ($res['success']) {
            setFlash('success', 'Rentang tanggal sewa berhasil diperbarui.');
        } else {
            setFlash('error', $res['message']);
        }
        header('Location: ' . baseUrl('pages/cart.php'));
        exit;
    }
}

$pageTitle = 'Keranjang Sewa - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Keranjang Sewa</li>
      </ol>
    </nav>
    <h1 class="font-heading fs-2 mb-1">Keranjang Sewa Alat</h1>
    <p class="text-muted small">Periksa kembali daftar perlengkapan dan tanggal sewa sebelum melanjutkan ke pembayaran.</p>
  </div>

  <?php if (empty($cart['items'])): ?>
    <div class="lk-card text-center py-5">
      <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background-color: var(--lk-primary-tint); color: var(--lk-forest);">
        <i class="fas fa-shopping-bag fa-3x"></i>
      </div>
      <h3 class="font-heading fs-4 mb-2">Keranjang Sewa Anda Kosong</h3>
      <p class="text-muted small mb-4">Anda belum menambahkan perlengkapan pendakian apa pun ke keranjang.</p>
      <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest py-2">
        <i class="fas fa-compass me-1"></i> Mulai Pilih Alat Pendakian
      </a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <!-- Items & Dates Column -->
      <div class="col-lg-8">
        <!-- Date Adjustment Card -->
        <div class="lk-card p-4 mb-4" style="background-color: var(--lk-blush); border: 1px solid var(--lk-sage);">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="font-heading fs-6 mb-0 text-forest">
              <i class="far fa-calendar-alt me-2"></i> Jadwal Rentang Penyewaan
            </h5>
            <span class="badge bg-dark px-3 py-2"><?= $totals['duration_days'] ?> Hari Sewa</span>
          </div>

          <form action="<?= baseUrl('pages/cart.php') ?>" method="POST" class="row g-2 align-items-end">
            <input type="hidden" name="action" value="update_dates">
            <div class="col-sm-5">
              <label class="form-label small text-muted mb-1">Tanggal Ambil (Mulai):</label>
              <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($totals['start_date']) ?>" min="<?= $minDate ?>" required>
            </div>
            <div class="col-sm-5">
              <label class="form-label small text-muted mb-1">Tanggal Kembali (Selesai):</label>
              <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($totals['end_date']) ?>" min="<?= $minDate ?>" required>
            </div>
            <div class="col-sm-2">
              <button type="submit" class="btn btn-forest btn-sm w-100" style="height: 48px;">
                <i class="fas fa-sync-alt me-1"></i> Update
              </button>
            </div>
          </form>
          <div class="small text-muted mt-2" style="font-size: 12px;">
            * Pengambilan dan pengembalian dilakukan di toko fisik RentGear. Hari pengembalian dapat digunakan oleh penyewa berikutnya.
          </div>
        </div>

        <!-- Table of Items -->
        <div class="lk-card p-4">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead class="table-light">
                <tr class="small text-muted">
                  <th>Alat Pendakian</th>
                  <th>Harga/Hari</th>
                  <th>Deposit/Unit</th>
                  <th>Jumlah</th>
                  <th class="text-end">Subtotal Sewa</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cart['items'] as $item): 
                  $itemSubtotal = $totals['duration_days'] * $item['price_per_day'] * $item['quantity'];
                ?>
                  <tr id="cart-item-row-<?= $item['variant_id'] ?>">
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <img src="<?= htmlspecialchars($item['image'] ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=200') ?>" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>" 
                             class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                        <div>
                          <a href="<?= baseUrl('pages/product.php?id=' . $item['product_id']) ?>" class="fw-bold text-dark text-decoration-none">
                            <?= htmlspecialchars($item['product_name']) ?>
                          </a>
                          <div class="small text-muted">Varian: <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['variant_name']) ?></span></div>
                        </div>
                      </div>
                    </td>
                    <td class="text-forest fw-bold"><?= formatRupiah($item['price_per_day']) ?></td>
                    <td class="small text-muted"><?= formatRupiah($item['deposit_per_unit']) ?></td>
                    <td>
                      <div class="lk-qty-stepper">
                        <button type="button" class="btn-minus" onclick="changeQty(<?= $item['variant_id'] ?>, -1)">&minus;</button>
                        <input type="number" id="qty-input-<?= $item['variant_id'] ?>" value="<?= $item['quantity'] ?>" min="1" readonly>
                        <button type="button" class="btn-plus" onclick="changeQty(<?= $item['variant_id'] ?>, 1)">&plus;</button>
                      </div>
                    </td>
                    <td class="text-end fw-bold text-forest">
                      <?= formatRupiah($itemSubtotal) ?>
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus dari keranjang" onclick="deleteItem(<?= $item['variant_id'] ?>)">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between mt-3">
            <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-outline-forest btn-sm">
              <i class="fas fa-arrow-left me-1"></i> Tambah Alat Lain
            </a>
          </div>
        </div>
      </div>

      <!-- Totals & Checkout Summary Right Column -->
      <div class="col-lg-4">
        <div class="lk-card p-4">
          <h5 class="font-heading fs-5 mb-3 border-bottom pb-2">Ringkasan Biaya</h5>

          <div class="d-flex justify-content-between small mb-2 text-muted">
            <span>Total Item:</span>
            <span><?= $totals['items_count'] ?> unit (<?= $totals['unique_items'] ?> jenis)</span>
          </div>

          <div class="d-flex justify-content-between small mb-2 text-muted">
            <span>Durasi Sewa:</span>
            <span><?= $totals['duration_days'] ?> Hari</span>
          </div>

          <div class="d-flex justify-content-between small mb-2 text-muted">
            <span>Subtotal Sewa:</span>
            <span class="text-dark fw-bold"><?= formatRupiah($totals['total_rental']) ?></span>
          </div>

          <div class="d-flex justify-content-between small mb-3 text-muted">
            <span>Total Deposit (Jaminan):</span>
            <span class="text-dark fw-bold"><?= formatRupiah($totals['total_deposit']) ?></span>
          </div>

          <div class="alert alert-light border small py-2 mb-3">
            <div class="d-flex justify-content-between text-muted mb-1">
              <span>Opsi DP 50%:</span>
              <strong><?= formatRupiah($totals['dp_amount']) ?></strong>
            </div>
            <div class="d-flex justify-content-between text-muted">
              <span>Opsi Lunas 100%:</span>
              <strong><?= formatRupiah($totals['grand_total']) ?></strong>
            </div>
          </div>

          <div class="d-flex justify-content-between fw-bold fs-5 mb-4 border-top pt-3 text-forest">
            <span>Total Tagihan:</span>
            <span><?= formatRupiah($totals['grand_total']) ?></span>
          </div>

          <!-- Checkout Flow Intercepts (Alur 1, Alur 2, FR-21) -->
          <div class="d-grid gap-2">
            <?php if (!$currentUser): ?>
              <button type="button" class="btn btn-forest py-3" onclick="triggerGuestIntercept('Silakan login terlebih dahulu untuk melanjutkan ke halaman pembayaran.')">
                <i class="fas fa-lock me-1"></i> Checkout (Wajib Login)
              </button>
            <?php elseif ($currentUser['verification_status'] === 'pending'): ?>
              <div class="alert alert-warning small p-2 mb-0">
                <i class="fas fa-clock me-1"></i> Akun Anda masih <strong>menunggu verifikasi KTP</strong> oleh admin. Checkout hanya dapat dilakukan setelah status akun terverifikasi.
              </div>
              <a href="<?= baseUrl('pages/profile.php') ?>" class="btn btn-outline-forest py-2">
                Lihat Status Verifikasi
              </a>
            <?php elseif ($currentUser['verification_status'] === 'rejected'): ?>
              <div class="alert alert-danger small p-2 mb-0">
                <i class="fas fa-times-circle me-1"></i> Verifikasi identitas Anda ditolak. Silakan unggah ulang KTP/SIM di halaman profil.
              </div>
              <a href="<?= baseUrl('pages/profile.php') ?>" class="btn btn-danger py-2">
                Unggah Ulang KTP
              </a>
            <?php else: ?>
              <a href="<?= baseUrl('pages/checkout.php') ?>" class="btn btn-forest py-3">
                <i class="fas fa-arrow-right me-1"></i> Lanjut ke Pembayaran
              </a>
            <?php endif; ?>
          </div>

          <div class="text-center small text-muted mt-3">
            <i class="fas fa-shield-alt text-success me-1"></i> Pembayaran aman & verifikasi admin transparan
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function changeQty(variantId, delta) {
    const input = document.getElementById('qty-input-' + variantId);
    let val = parseInt(input.value) || 1;
    let target = val + delta;
    if (target < 1) target = 1;

    fetch('<?= baseUrl('api/cart-action.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update&variant_id=${variantId}&quantity=${target}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Stok tidak mencukupi.');
        }
    });
}

function deleteItem(variantId) {
    if (confirm('Yakin ingin menghapus item ini dari keranjang?')) {
        fetch('<?= baseUrl('api/cart-action.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&variant_id=${variantId}`
        })
        .then(r => r.json())
        .then(data => {
            window.location.reload();
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
