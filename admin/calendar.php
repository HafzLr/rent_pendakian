<?php
// admin/calendar.php - Gear Availability Calendar per Variant (FR-45)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/stock.php';

requireAdmin();
$db = getDB();

// Fetch variants list for dropdown
$vList = $db->query("
    SELECT pv.variant_id, pv.variant_name, pv.total_stock, p.product_name, c.category_name
    FROM product_variants pv
    JOIN products p ON p.product_id = pv.product_id
    JOIN categories c ON c.category_id = p.category_id
    WHERE pv.is_active = 1 AND p.is_active = 1
    ORDER BY p.product_name ASC, pv.variant_name ASC
")->fetchAll();

$selectedVariantId = (int)($_GET['variant_id'] ?? ($vList[0]['variant_id'] ?? 0));

$selectedVariant = null;
foreach ($vList as $v) {
    if ((int)$v['variant_id'] === $selectedVariantId) {
        $selectedVariant = $v;
        break;
    }
}

// Generate next 30 days
$days = [];
$today = new DateTime();
for ($i = 0; $i < 30; $i++) {
    $cur = clone $today;
    $cur->modify("+{$i} days");
    $dStr = $cur->format('Y-m-d');
    $nextStr = (clone $cur)->modify('+1 day')->format('Y-m-d');

    $avail = 0;
    if ($selectedVariant) {
        $avail = getAvailableStock($selectedVariantId, $dStr, $nextStr);
    }

    $days[] = [
        'date' => $dStr,
        'day_name' => $cur->format('D'),
        'day_num' => $cur->format('j M'),
        'available' => $avail,
        'total' => $selectedVariant ? (int)$selectedVariant['total_stock'] : 0,
        'booked' => $selectedVariant ? max(0, (int)$selectedVariant['total_stock'] - $avail) : 0
    ];
}

$adminTitle = 'Kalender Ketersediaan Stok - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Kalender Ketersediaan Stok Alat</h1>
    <p class="text-muted small mb-0">Inspeksi ketersediaan fisik harian per varian perlengkapan untuk 30 hari ke depan.</p>
  </div>
</div>

<!-- Variant Selector -->
<div class="lk-card p-4 mb-4">
  <form action="<?= baseUrl('admin/calendar.php') ?>" method="GET" class="row g-3 align-items-center">
    <div class="col-md-8">
      <label class="form-label small fw-bold mb-1"><i class="fas fa-layer-group text-forest me-1"></i> Pilih Produk & Varian:</label>
      <select name="variant_id" class="form-select" onchange="this.form.submit()">
        <?php foreach ($vList as $item): ?>
          <option value="<?= $item['variant_id'] ?>" <?= $item['variant_id'] == $selectedVariantId ? 'selected' : '' ?>>
            <?= htmlspecialchars($item['product_name']) ?> &mdash; Varian: <?= htmlspecialchars($item['variant_name']) ?> (Stok Fisik: <?= $item['total_stock'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 text-md-end pt-md-3">
      <?php if ($selectedVariant): ?>
        <span class="badge bg-forest px-3 py-2 fs-6">
          Total Stok Fisik: <?= $selectedVariant['total_stock'] ?> Unit
        </span>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Calendar Timeline Matrix -->
<?php if ($selectedVariant): ?>
  <div class="lk-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="font-heading fs-5 mb-0">
        <?= htmlspecialchars($selectedVariant['product_name']) ?> (<?= htmlspecialchars($selectedVariant['variant_name']) ?>)
      </h5>
      <div class="d-flex gap-3 small text-muted">
        <span><span class="badge bg-success">&nbsp;</span> Tersedia Cukup</span>
        <span><span class="badge bg-warning text-dark">&nbsp;</span> Stok Terbatas (&le;2)</span>
        <span><span class="badge bg-danger">&nbsp;</span> Habis / Full Booking</span>
      </div>
    </div>

    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 row-cols-lg-6 g-3">
      <?php foreach ($days as $d): 
        $badgeClass = 'bg-success';
        if ($d['available'] === 0) {
            $badgeClass = 'bg-danger';
        } elseif ($d['available'] <= 2 && $d['total'] > 2) {
            $badgeClass = 'bg-warning text-dark';
        }
      ?>
        <div class="col">
          <div class="p-3 rounded border text-center h-100 bg-light">
            <div class="small fw-bold text-muted"><?= $d['day_name'] ?></div>
            <div class="fs-6 fw-bold mb-2"><?= $d['day_num'] ?></div>
            
            <div class="badge <?= $badgeClass ?> w-100 py-2 fs-6 mb-2">
              Sisa: <?= $d['available'] ?>
            </div>

            <div class="small text-muted" style="font-size: 11px;">
              Tersewa: <strong><?= $d['booked'] ?></strong> / <?= $d['total'] ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
