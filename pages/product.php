<?php
// pages/product.php - Product Detail & Rental Booking Form
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/stock.php';
require_once __DIR__ . '/../helpers/cart.php';

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) {
    header('Location: ' . baseUrl('pages/catalog.php'));
    exit;
}

$db = getDB();

// Fetch product & category
$stmt = $db->prepare("
    SELECT p.*, c.category_name 
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    WHERE p.product_id = ? AND p.is_active = 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan atau sedang tidak aktif.');
    header('Location: ' . baseUrl('pages/catalog.php'));
    exit;
}

// Fetch active variants
$vStmt = $db->prepare("
    SELECT variant_id, variant_name, total_stock 
    FROM product_variants 
    WHERE product_id = ? AND is_active = 1
    ORDER BY variant_name ASC
");
$vStmt->execute([$productId]);
$variants = $vStmt->fetchAll();

$currentUser = currentUser();
$isGuest = ($currentUser === null);

// Pre-fill dates from cart if available, else tomorrow + 2 days
$cart = getCart();
$defaultStartDate = $cart['start_date'] ?: date('Y-m-d', strtotime('+1 day'));
$defaultEndDate = $cart['end_date'] ?: date('Y-m-d', strtotime('+3 days'));
$minDate = date('Y-m-d');

$pageTitle = htmlspecialchars($product['product_name']) . ' - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <!-- Breadcrumbs -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb small text-muted">
      <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
      <li class="breadcrumb-item"><a href="<?= baseUrl('pages/catalog.php') ?>">Katalog</a></li>
      <li class="breadcrumb-item"><a href="<?= baseUrl('pages/catalog.php?category_id=' . $product['category_id']) ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['product_name']) ?></li>
    </ol>
  </nav>

  <div class="row g-4 mb-5">
    <!-- Product Gallery Left Column -->
    <div class="col-lg-6">
      <div class="lk-card p-3">
        <div class="position-relative overflow-hidden rounded" style="padding-top: 85%; background: #F8F6F4;">
          <img src="<?= htmlspecialchars($product['image'] ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=800') ?>" 
               alt="<?= htmlspecialchars($product['product_name']) ?>" 
               class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover;" id="mainProductImg">
        </div>
        <div class="small text-muted text-center mt-3">
          <i class="fas fa-camera me-1"></i> Foto aktual perlengkapan dalam kondisi bersih dan siap pakai
        </div>
      </div>
    </div>

    <!-- Product Info & Booking Form Right Column -->
    <div class="col-lg-6">
      <div class="lk-card p-4 p-md-5">
        <div class="badge bg-light text-muted border mb-2 text-uppercase" style="letter-spacing: 0.05em;">
          <?= htmlspecialchars($product['category_name']) ?>
        </div>
        <h1 class="font-heading fs-2 mb-3"><?= htmlspecialchars($product['product_name']) ?></h1>

        <!-- Price & Deposit -->
        <div class="d-flex align-items-baseline gap-3 mb-4 pb-3 border-bottom">
          <div>
            <span class="lk-price fs-3"><?= formatRupiah($product['price_per_day']) ?></span>
            <span class="text-muted small">/ hari</span>
          </div>
          <div class="vr"></div>
          <div>
            <span class="text-muted small">Uang Jaminan / Deposit:</span>
            <strong class="text-dark d-block"><?= formatRupiah($product['deposit']) ?> <small class="text-muted">(per unit)</small></strong>
          </div>
        </div>

        <!-- Rent Form (Alur 2 & FR-15 to FR-19) -->
        <form id="rentalForm">
          <input type="hidden" id="pricePerDay" value="<?= (float)$product['price_per_day'] ?>">
          <input type="hidden" id="depositPerUnit" value="<?= (float)$product['deposit'] ?>">

          <!-- Variant Selector -->
          <div class="mb-3">
            <label class="form-label fw-bold" for="variantSelect">
              <i class="fas fa-layer-group text-forest me-1"></i> Pilih Varian / Kapasitas / Warna <span class="text-danger">*</span>
            </label>
            <?php if (empty($variants)): ?>
              <div class="alert alert-warning py-2 small">Belum ada varian produk tersedia.</div>
            <?php else: ?>
              <select class="form-select" id="variantSelect" required>
                <?php foreach ($variants as $idx => $v): ?>
                  <option value="<?= $v['variant_id'] ?>" data-stock="<?= $v['total_stock'] ?>" <?= $idx === 0 ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v['variant_name']) ?> (Stok fisik: <?= $v['total_stock'] ?> unit)
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

          <!-- Date Range Picker -->
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label fw-bold" for="startDate">
                <i class="far fa-calendar-alt text-forest me-1"></i> Tanggal Ambil (Mulai) <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control" id="startDate" value="<?= htmlspecialchars($defaultStartDate) ?>" min="<?= $minDate ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-bold" for="endDate">
                <i class="far fa-calendar-check text-forest me-1"></i> Tanggal Kembali (Selesai) <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control" id="endDate" value="<?= htmlspecialchars($defaultEndDate) ?>" min="<?= $minDate ?>" required>
            </div>
          </div>

          <!-- Quantity Stepper -->
          <div class="mb-4">
            <label class="form-label fw-bold d-block">
              <i class="fas fa-cubes text-forest me-1"></i> Jumlah Unit Sewa <span class="text-danger">*</span>
            </label>
            <div class="d-flex align-items-center gap-3">
              <div class="lk-qty-stepper">
                <button type="button" class="btn-minus" aria-label="Kurang">&minus;</button>
                <input type="number" id="rentalQuantity" value="1" min="1" max="99">
                <button type="button" class="btn-plus" aria-label="Tambah">&plus;</button>
              </div>
              <span class="small text-muted" id="selectedVariantStockInfo">Pilih tanggal untuk mengecek ketersediaan</span>
            </div>
          </div>

          <!-- Check Stock Button (FR-17 & Guest Intercept FR-06) -->
          <div class="mb-3">
            <button type="button" class="btn btn-outline-forest w-100" id="btnCheckStock">
              <i class="fas fa-search-plus me-1"></i> Cek Ketersediaan Stok
            </button>
          </div>

          <!-- Availability & Price Calculation Card (FR-18 & FR-19) -->
          <div id="stockResultBox" class="p-3 mb-4 rounded d-none" style="background-color: var(--lk-blush); border: 1px solid var(--lk-sage);">
            <div id="stockStatusText" class="fw-bold mb-2"></div>
            
            <!-- Calculation Breakdown -->
            <div id="calcBreakdown" class="border-top pt-2 small">
              <div class="d-flex justify-content-between mb-1">
                <span>Durasi Sewa:</span>
                <span class="fw-bold" id="resDuration">0 Hari</span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Biaya Sewa (<span id="resQtyDays"></span>):</span>
                <span class="fw-bold text-forest" id="resSubtotal">Rp 0</span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Total Deposit Jaminan:</span>
                <span class="fw-bold" id="resDeposit">Rp 0</span>
              </div>
              <div class="d-flex justify-content-between border-top pt-2 mt-2 fw-bold fs-6">
                <span>Total Tagihan:</span>
                <span class="text-forest" id="resGrandTotal">Rp 0</span>
              </div>
            </div>

            <!-- Alternative Suggestions Box if Out of Stock (FR-18) -->
            <div id="alternativeDatesBox" class="mt-3 pt-3 border-top d-none">
              <div class="small fw-bold text-danger mb-2">
                <i class="fas fa-calendar-times me-1"></i> Saran Tanggal Terdekat yang Tersedia:
              </div>
              <div id="suggestionsList" class="d-flex flex-column gap-1"></div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="row g-2">
            <div class="col-sm-6">
              <button type="button" class="btn btn-outline-forest w-100 py-3" id="btnAddToCart">
                <i class="fas fa-cart-plus me-1"></i> Tambah Keranjang
              </button>
            </div>
            <div class="col-sm-6">
              <button type="button" class="btn btn-forest w-100 py-3" id="btnRentNow">
                <i class="fas fa-hiking me-1"></i> Sewa Sekarang
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- Product Detail Tabs (PRD 9.5 & Design.md 8.8) -->
  <div class="lk-card p-4 p-md-5 mb-5">
    <ul class="nav nav-tabs border-bottom mb-4" id="productTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold px-4 py-2" id="desc-tab" data-bs-toggle="tab" data-bs-target="#descContent" type="button" role="tab">
          <i class="fas fa-align-left me-1"></i> Deskripsi Alat
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2" id="spec-tab" data-bs-toggle="tab" data-bs-target="#specContent" type="button" role="tab">
          <i class="fas fa-list-check me-1"></i> Spesifikasi & Kelengkapan
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-4 py-2" id="terms-tab" data-bs-toggle="tab" data-bs-target="#termsContent" type="button" role="tab">
          <i class="fas fa-shield-alt me-1"></i> Ketentuan & Jaminan Sewa
        </button>
      </li>
    </ul>

    <div class="tab-content" id="productTabsContent">
      <!-- Deskripsi -->
      <div class="tab-pane fade show active" id="descContent" role="tabpanel">
        <h4 class="font-heading fs-5 mb-3">Tentang <?= htmlspecialchars($product['product_name']) ?></h4>
        <div class="lh-lg" style="color: var(--lk-ink);">
          <?= nl2br(htmlspecialchars($product['description'] ?: 'Peralatan pendakian berkualitas tinggi dari RentGear, dicek berkala dan dipastikan dalam kondisi prima untuk menunjang keamanan dan kenyamanan aktivitas alam bebas Anda.')) ?>
        </div>
      </div>

      <!-- Spesifikasi -->
      <div class="tab-pane fade" id="specContent" role="tabpanel">
        <h4 class="font-heading fs-5 mb-3">Kelengkapan Standar</h4>
        <ul class="list-unstyled d-flex flex-column gap-2 small">
          <li><i class="fas fa-check text-success me-2"></i> 1x Unit Utama (<?= htmlspecialchars($product['product_name']) ?>)</li>
          <li><i class="fas fa-check text-success me-2"></i> Pouch / Cover / Stuff Sack Pelindung</li>
          <li><i class="fas fa-check text-success me-2"></i> Petunjuk pemasangan dan panduan keselamatan ringkas</li>
          <li><i class="fas fa-check text-success me-2"></i> Diinspeksi kebersihan dan fungsinya sebelum diserahkan</li>
        </ul>
      </div>

      <!-- Ketentuan Sewa -->
      <div class="tab-pane fade" id="termsContent" role="tabpanel">
        <h4 class="font-heading fs-5 mb-3">Ketentuan Sewa & Deposit</h4>
        <div class="small lh-lg">
          <ol class="ps-3 mb-3">
            <li><strong>Pengambilan:</strong> Dilakukan langsung di toko fisik RentGear pada tanggal mulai sewa dengan menunjukkan bukti pesanan.</li>
            <li><strong>Uang Deposit:</strong> Deposit sebesar <?= formatRupiah($product['deposit']) ?> per unit wajib dibayarkan saat transaksi dan akan dikembalikan 100% setelah alat kembali dalam kondisi baik.</li>
            <li><strong>Keterlambatan:</strong> Keterlambatan pengembalian melewati batas tanggal selesai dikenakan biaya denda harian sesuai ketentuan toko.</li>
            <li><strong>Kerusakan:</strong> Kerusakan atau kehilangan bagian perlengkapan akan dipotong dari uang deposit sesuai biaya perbaikan atau penggantian.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const isGuestUser = <?= $isGuest ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', () => {
    const btnCheck = document.getElementById('btnCheckStock');
    const btnAddToCart = document.getElementById('btnAddToCart');
    const btnRentNow = document.getElementById('btnRentNow');
    const variantSelect = document.getElementById('variantSelect');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const qtyInput = document.getElementById('rentalQuantity');
    const pricePerDay = parseFloat(document.getElementById('pricePerDay').value) || 0;
    const depositPerUnit = parseFloat(document.getElementById('depositPerUnit').value) || 0;

    const resultBox = document.getElementById('stockResultBox');
    const statusText = document.getElementById('stockStatusText');
    const resDuration = document.getElementById('resDuration');
    const resQtyDays = document.getElementById('resQtyDays');
    const resSubtotal = document.getElementById('resSubtotal');
    const resDeposit = document.getElementById('resDeposit');
    const resGrandTotal = document.getElementById('resGrandTotal');
    const altBox = document.getElementById('alternativeDatesBox');
    const suggestionsList = document.getElementById('suggestionsList');

    function checkAvailability(onSuccessCallback) {
        if (isGuestUser) {
            triggerGuestIntercept('Wajib login untuk mengecek ketersediaan stok atau melakukan penyewaan alat pendakian.');
            return;
        }

        const variantId = variantSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const qty = parseInt(qtyInput.value) || 1;

        if (!startDate || !endDate) {
            alert('Silakan pilih tanggal mulai dan tanggal selesai sewa.');
            return;
        }

        if (endDate <= startDate) {
            alert('Tanggal selesai harus setelah tanggal mulai sewa.');
            return;
        }

        btnCheck.disabled = true;
        btnCheck.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memeriksa stok...';

        fetch('<?= baseUrl('api/check-stock.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `variant_id=${variantId}&start_date=${startDate}&end_date=${endDate}&quantity=${qty}`
        })
        .then(r => r.json())
        .then(data => {
            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-search-plus me-1"></i> Cek Ketersediaan Stok';

            resultBox.classList.remove('d-none');
            
            if (data.is_available) {
                statusText.className = 'fw-bold mb-2 text-success';
                statusText.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${data.message}`;
                altBox.classList.add('d-none');

                // Calculation breakdown
                const dur = data.duration_days;
                const subtotal = dur * pricePerDay * qty;
                const deposit = depositPerUnit * qty;
                const grand = subtotal + deposit;

                resDuration.textContent = `${dur} Hari (${startDate} s.d. ${endDate})`;
                resQtyDays.textContent = `${qty} unit × ${dur} hari × Rp ${pricePerDay.toLocaleString('id-ID')}`;
                resSubtotal.textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
                resDeposit.textContent = `Rp ${deposit.toLocaleString('id-ID')}`;
                resGrandTotal.textContent = `Rp ${grand.toLocaleString('id-ID')}`;

                if (typeof onSuccessCallback === 'function') {
                    onSuccessCallback();
                }
            } else {
                statusText.className = 'fw-bold mb-2 text-danger';
                statusText.innerHTML = `<i class="fas fa-times-circle me-1"></i> ${data.message}`;

                // Show alternative dates (FR-18)
                if (data.suggestions && data.suggestions.length > 0) {
                    altBox.classList.remove('d-none');
                    suggestionsList.innerHTML = '';
                    data.suggestions.forEach(sug => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-outline-forest text-start py-1 px-2 my-1';
                        btn.innerHTML = `<i class="far fa-calendar-check me-1"></i> <strong>${sug.start_date} s.d. ${sug.end_date}</strong> (Tersedia ${sug.available_stock} unit)`;
                        btn.onclick = () => {
                            startDateInput.value = sug.start_date;
                            endDateInput.value = sug.end_date;
                            checkAvailability();
                        };
                        suggestionsList.appendChild(btn);
                    });
                } else {
                    altBox.classList.add('d-none');
                }
            }
        })
        .catch(err => {
            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-search-plus me-1"></i> Cek Ketersediaan Stok';
            alert('Terjadi kesalahan saat memeriksa stok.');
        });
    }

    btnCheck.addEventListener('click', () => checkAvailability());

    function addProductToCart(redirectAfter = false) {
        if (isGuestUser) {
            triggerGuestIntercept('Wajib login untuk menambahkan alat ke keranjang sewa.');
            return;
        }

        const variantId = variantSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const qty = parseInt(qtyInput.value) || 1;

        fetch('<?= baseUrl('api/cart-action.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&variant_id=${variantId}&quantity=${qty}&start_date=${startDate}&end_date=${endDate}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (redirectAfter) {
                    window.location.href = '<?= baseUrl('pages/checkout.php') ?>';
                } else {
                    const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCart'));
                    offcanvas.show();
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Gagal menambahkan ke keranjang.');
            }
        });
    }

    btnAddToCart.addEventListener('click', () => {
        checkAvailability(() => addProductToCart(false));
    });

    btnRentNow.addEventListener('click', () => {
        checkAvailability(() => addProductToCart(true));
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
