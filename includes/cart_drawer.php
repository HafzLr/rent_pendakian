<?php
// includes/cart_drawer.php - Off-canvas Cart Summary
require_once __DIR__ . '/../helpers/cart.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/auth.php';

$cart = getCart();
$totals = getCartTotals();
$currentUser = currentUser();
?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCart" aria-labelledby="offcanvasCartLabel" style="width: 400px; max-width: 90vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title font-heading" id="offcanvasCartLabel">
      <i class="fas fa-shopping-bag text-success me-2"></i> Keranjang Sewa
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
  </div>
  
  <div class="offcanvas-body d-flex flex-column p-3">
    <!-- Date summary banner -->
    <div class="p-3 mb-3 rounded" style="background-color: var(--lk-blush); border: 1px solid var(--lk-sage);">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small text-muted"><i class="far fa-calendar-alt me-1"></i> Rentang Sewa</span>
        <span class="badge bg-dark"><?= $totals['duration_days'] ?> Hari</span>
      </div>
      <div class="fw-bold" style="font-size: 13px; color: var(--lk-forest);">
        <?= formatDate($totals['start_date']) ?> &rarr; <?= formatDate($totals['end_date']) ?>
      </div>
    </div>

    <!-- Items list -->
    <div class="flex-grow-1 overflow-auto" id="drawerCartItems">
      <?php if (empty($cart['items'])): ?>
        <div class="text-center py-5">
          <i class="fas fa-campground fa-3x mb-3" style="color: var(--lk-sage);"></i>
          <p class="text-muted mb-3">Keranjang sewa Anda masih kosong.</p>
          <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-outline-forest btn-sm">Jelajahi Alat</a>
        </div>
      <?php else: ?>
        <?php foreach ($cart['items'] as $item): 
          $itemSubtotal = $totals['duration_days'] * $item['price_per_day'] * $item['quantity'];
        ?>
          <div class="lk-cart-item position-relative" id="cart-row-<?= $item['variant_id'] ?>">
            <img src="<?= htmlspecialchars($item['image'] ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=200') ?>" 
                 alt="<?= htmlspecialchars($item['product_name']) ?>" class="lk-cart-item-img">
            <div class="flex-grow-1 pe-3">
              <div class="fw-bold text-truncate" style="max-width: 190px; font-size: 14px;"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="small text-muted mb-1">Varian: <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['variant_name']) ?></span></div>
              <div class="d-flex align-items-center justify-content-between">
                <span class="small text-muted"><?= $item['quantity'] ?> × <?= formatRupiah($item['price_per_day']) ?>/hari</span>
                <span class="fw-bold text-forest" style="font-size: 13px;"><?= formatRupiah($itemSubtotal) ?></span>
              </div>
            </div>
            <button type="button" class="btn btn-sm text-danger p-0" title="Hapus" onclick="removeDrawerItem(<?= $item['variant_id'] ?>)">
              <i class="fas fa-times"></i>
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Totals summary -->
    <?php if (!empty($cart['items'])): ?>
      <div class="border-top pt-3 mt-2">
        <div class="d-flex justify-content-between small text-muted mb-1">
          <span>Total Sewa (<?= $totals['duration_days'] ?> hari):</span>
          <span><?= formatRupiah($totals['total_rental']) ?></span>
        </div>
        <div class="d-flex justify-content-between small text-muted mb-2">
          <span>Total Deposit (Jaminan):</span>
          <span><?= formatRupiah($totals['total_deposit']) ?></span>
        </div>
        <div class="d-flex justify-content-between fw-bold fs-6 mb-3 border-top pt-2" style="color: var(--lk-ink);">
          <span>Perkiraan Total:</span>
          <span style="color: var(--lk-forest);"><?= formatRupiah($totals['grand_total']) ?></span>
        </div>

        <div class="d-grid gap-2">
          <?php if (!$currentUser): ?>
            <button type="button" class="btn btn-forest py-2" onclick="triggerGuestIntercept('Silakan login terlebih dahulu untuk melanjutkan ke proses checkout sewa.');">
              <i class="fas fa-lock me-1"></i> Checkout (Wajib Login)
            </button>
          <?php elseif ($currentUser['verification_status'] !== 'verified' && $currentUser['role'] !== 'admin'): ?>
            <a href="<?= baseUrl('pages/profile.php') ?>" class="btn btn-warning py-2 text-dark fw-bold">
              <i class="fas fa-exclamation-triangle me-1"></i> Verifikasi KTP untuk Checkout
            </a>
          <?php else: ?>
            <a href="<?= baseUrl('pages/checkout.php') ?>" class="btn btn-forest py-2">
              <i class="fas fa-credit-card me-1"></i> Lanjut ke Checkout
            </a>
          <?php endif; ?>
          <a href="<?= baseUrl('pages/cart.php') ?>" class="btn btn-outline-forest py-2">
            Lihat Keranjang Lengkap
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function removeDrawerItem(variantId) {
    fetch('<?= baseUrl('api/cart-action.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove&variant_id=' + variantId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}
</script>
