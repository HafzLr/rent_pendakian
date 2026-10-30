<?php
// index.php - Homepage for RentGear Outdoor
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/functions.php';

$db = getDB();

// Fetch categories with product counts
$catStmt = $db->query("
    SELECT c.category_id, c.category_name, COUNT(p.product_id) as total_products
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.category_id AND p.is_active = 1
    GROUP BY c.category_id
    ORDER BY total_products DESC
    LIMIT 6
");
$popularCategories = $catStmt->fetchAll();

// Fetch featured popular products with stock sum
$prodStmt = $db->query("
    SELECT p.product_id, p.product_name, p.price_per_day, p.deposit, p.image, c.category_name,
           COALESCE(SUM(pv.total_stock), 0) AS total_physical_stock
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    LEFT JOIN product_variants pv ON pv.product_id = p.product_id AND pv.is_active = 1
    WHERE p.is_active = 1
    GROUP BY p.product_id
    ORDER BY p.product_id ASC
    LIMIT 8
");
$featuredProducts = $prodStmt->fetchAll();

$pageTitle = APP_NAME . ' - ' . APP_TAGLINE;
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section (PRD 9.5 & Design.md 8.5) -->
<section class="lk-hero d-flex align-items-center">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8 col-xl-7">
        <span class="badge px-3 py-2 mb-3 text-uppercase fw-bold" style="background-color: rgba(149, 162, 100, 0.3); color: #EAECE0; border: 1px solid var(--lk-leaf); letter-spacing: 0.08em;">
          <i class="fas fa-mountain me-1"></i> Sewa Alat Pendakian Modern
        </span>
        <h1 class="display-4 font-heading fw-bold mb-3">
          Jelajahi Puncak Impian Tanpa Khawatir Stok Bentrok
        </h1>
        <p class="fs-5 mb-4 text-light opacity-90" style="max-width: 600px;">
          Sewa tenda ultralight, carrier ergonomis, sleeping bag hangat, dan kompor gunung terawat. Cek ketersediaan real-time per tanggal dengan jaminan tanpa dobel booking.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest py-3 px-4">
            <i class="fas fa-compass me-2"></i> Jelajahi Katalog Alat
          </a>
          <a href="<?= baseUrl('pages/how-it-works.php') ?>" class="btn btn-outline-light py-3 px-4">
            <i class="fas fa-play-circle me-2"></i> Cara & Alur Sewa
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Feature Strip -->
<section class="py-4 bg-white border-bottom shadow-sm">
  <div class="container">
    <div class="row g-4 text-center text-md-start">
      <div class="col-md-3 d-flex align-items-center justify-content-center justify-content-md-start gap-3">
        <i class="fas fa-calendar-check fa-2x text-forest"></i>
        <div>
          <div class="fw-bold fs-6">Cek Stok Per Tanggal</div>
          <div class="small text-muted">Sistem otomatis anti-bentrok</div>
        </div>
      </div>
      <div class="col-md-3 d-flex align-items-center justify-content-center justify-content-md-start gap-3">
        <i class="fas fa-shield-alt fa-2x text-forest"></i>
        <div>
          <div class="fw-bold fs-6">Verifikasi KTP Transparan</div>
          <div class="small text-muted">Identitas aman & terlindungi</div>
        </div>
      </div>
      <div class="col-md-3 d-flex align-items-center justify-content-center justify-content-md-start gap-3">
        <i class="fas fa-percentage fa-2x text-forest"></i>
        <div>
          <div class="fw-bold fs-6">Opsi DP 50% atau Lunas</div>
          <div class="small text-muted">Fleksibel bayar transfer / tunai</div>
        </div>
      </div>
      <div class="col-md-3 d-flex align-items-center justify-content-center justify-content-md-start gap-3">
        <i class="fas fa-spray-can-sparkles fa-2x text-forest"></i>
        <div>
          <div class="fw-bold fs-6">Bersih & Siap Pakai</div>
          <div class="small text-muted">Steril dan diuji sebelum serah</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Category Highlights -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="text-uppercase small fw-bold" style="color: var(--lk-leaf); letter-spacing: 0.05em;">Kategori Perlengkapan</span>
        <h2 class="font-heading fs-2 mb-0">Pilihan Kategori Alat Pendakian</h2>
      </div>
      <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-outline-forest btn-sm d-none d-md-inline-flex">
        Lihat Semua Alat <i class="fas fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-3">
      <?php foreach ($popularCategories as $cat): ?>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="<?= baseUrl('pages/catalog.php?category_id=' . $cat['category_id']) ?>" class="lk-card p-3 text-center text-dark text-decoration-none h-100 d-flex flex-column align-items-center justify-content-center">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 52px; height: 52px; background-color: var(--lk-primary-tint); color: var(--lk-forest);">
              <i class="fas fa-campground fa-lg"></i>
            </div>
            <div class="fw-bold small mb-1"><?= htmlspecialchars($cat['category_name']) ?></div>
            <span class="text-muted" style="font-size: 11px;"><?= $cat['total_products'] ?> Produk</span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Popular Gear Grid -->
<section class="py-5" style="background-color: #EFE6E7;">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="text-uppercase small fw-bold" style="color: var(--lk-leaf); letter-spacing: 0.05em;">Paling Banyak Disewa</span>
        <h2 class="font-heading fs-2 mb-0">Peralatan Populer & Unggulan</h2>
      </div>
      <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest btn-sm">
        Buka Katalog Lengkap <i class="fas fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php foreach ($featuredProducts as $prod): 
        $stock = (int)$prod['total_physical_stock'];
        $defaultImg = 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=700&auto=format&fit=crop&q=80';
        $imgUrl = !empty($prod['image']) ? $prod['image'] : $defaultImg;
      ?>
        <div class="col">
          <div class="lk-card h-100 d-flex flex-column">
            <!-- 1:1 Aspect ratio Image -->
            <div class="lk-product-img-wrapper">
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['product_name']) ?>" loading="lazy">
              <div class="lk-product-badge">
                <?php if ($stock > 3): ?>
                  <span class="lk-badge lk-badge-success"><i class="fas fa-check me-1"></i> Tersedia</span>
                <?php elseif ($stock > 0): ?>
                  <span class="lk-badge lk-badge-warning"><i class="fas fa-exclamation me-1"></i> Sisa <?= $stock ?> unit</span>
                <?php else: ?>
                  <span class="lk-badge lk-badge-danger"><i class="fas fa-times me-1"></i> Habis</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Content -->
            <div class="p-3 d-flex flex-column flex-grow-1">
              <div class="small text-muted mb-1 text-uppercase" style="font-size: 11px;">
                <?= htmlspecialchars($prod['category_name']) ?>
              </div>
              <h5 class="fs-6 mb-2" style="font-weight: 600; min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                <a href="<?= baseUrl('pages/product.php?id=' . $prod['product_id']) ?>" class="text-dark">
                  <?= htmlspecialchars($prod['product_name']) ?>
                </a>
              </h5>

              <div class="mt-auto pt-2 border-top">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                  <div>
                    <span class="lk-price"><?= formatRupiah($prod['price_per_day']) ?></span>
                    <span class="small text-muted">/hari</span>
                  </div>
                  <span class="lk-deposit-tag">
                    Dep: <?= formatRupiah($prod['deposit']) ?>
                  </span>
                </div>

                <a href="<?= baseUrl('pages/product.php?id=' . $prod['product_id']) ?>" class="btn btn-outline-forest w-100 btn-sm">
                  <i class="fas fa-calendar-alt me-1"></i> Cek Stok & Sewa
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- How it Works Summary -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5" style="max-width: 650px; margin: 0 auto;">
      <span class="text-uppercase small fw-bold" style="color: var(--lk-leaf); letter-spacing: 0.05em;">Alur Sewa Praktis</span>
      <h2 class="font-heading fs-2 mb-2">4 Langkah Cepat Menyewa di RentGear</h2>
      <p class="text-muted small">Tanpa perlu tanya admin berkali-kali via chat untuk cek ketersediaan barang.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-3">
        <div class="lk-card p-4 text-center h-100">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 24px;">
            <i class="fas fa-user-plus"></i>
          </div>
          <h5 class="font-heading fs-5 mb-2">1. Register Akun</h5>
          <p class="small text-muted mb-0">Daftarkan akun Anda dan upload dokumen KTP/SIM untuk verifikasi cepat identitas sewa.</p>
        </div>
      </div>

      <div class="col-md-3">
        <div class="lk-card p-4 text-center h-100">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 24px;">
            <i class="fas fa-calendar-check"></i>
          </div>
          <h5 class="font-heading fs-5 mb-2">2. Cek Tanggal & Stok</h5>
          <p class="small text-muted mb-0">Pilih rentang tanggal mulai s.d. selesai. Sistem langsung menghitung durasi dan biaya.</p>
        </div>
      </div>

      <div class="col-md-3">
        <div class="lk-card p-4 text-center h-100">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 24px;">
            <i class="fas fa-wallet"></i>
          </div>
          <h5 class="font-heading fs-5 mb-2">3. Bayar DP / Lunas</h5>
          <p class="small text-muted mb-0">Pilih opsi DP 50% atau langsung 100%. Upload bukti bayar transfer bank atau QRIS.</p>
        </div>
      </div>

      <div class="col-md-3">
        <div class="lk-card p-4 text-center h-100">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 24px;">
            <i class="fas fa-store"></i>
          </div>
          <h5 class="font-heading fs-5 mb-2">4. Ambil di Toko</h5>
          <p class="small text-muted mb-0">Ambil peralatan di outlet toko fisik RentGear saat tanggal mulai sewa tiba.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Call to action Banner -->
<section class="py-5" style="background: linear-gradient(rgba(18, 25, 12, 0.85), rgba(18, 25, 12, 0.9)), url('https://images.unsplash.com/photo-1478860409698-8707f313ee8b?w=1920&auto=format&fit=crop&q=80') center/cover; color: #FFF;">
  <div class="container text-center py-4">
    <h2 class="font-heading display-6 mb-3 text-white">Siap Menaklukkan Puncak Impian Anda?</h2>
    <p class="fs-5 text-light opacity-90 mb-4 mx-auto" style="max-width: 650px;">
      Jangan biarkan kendala perlengkapan menghalangi petualangan Anda. Booking alat pendakian sekarang sebelum kehabisan tanggal favorit!
    </p>
    <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest py-3 px-5 fs-6">
      <i class="fas fa-campground me-2"></i> Mulai Sewa Alat Sekarang
    </a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
