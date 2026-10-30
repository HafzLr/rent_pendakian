<?php
// pages/catalog.php - Product Catalog with category filtering, search, and sorting
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

$db = getDB();

// Query categories
$catStmt = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $catStmt->fetchAll();

// Filters
$selectedCat = (int)($_GET['category_id'] ?? 0);
$catNameParam = trim($_GET['category'] ?? '');
if (!$selectedCat && !empty($catNameParam)) {
    foreach ($categories as $c) {
        if (stripos($c['category_name'], $catNameParam) !== false) {
            $selectedCat = (int)$c['category_id'];
            break;
        }
    }
}

$searchQuery = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$sql = "
    SELECT p.product_id, p.category_id, p.product_name, p.description, p.price_per_day, p.deposit, p.image,
           c.category_name,
           COALESCE(SUM(pv.total_stock), 0) AS total_physical_stock
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    LEFT JOIN product_variants pv ON pv.product_id = p.product_id AND pv.is_active = 1
    WHERE p.is_active = 1
";

$params = [];

if ($selectedCat > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $selectedCat;
}

if (!empty($searchQuery)) {
    $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$sql .= " GROUP BY p.product_id";

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price_per_day ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price_per_day DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.product_name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.product_id DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Katalog Alat Pendakian - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <!-- Breadcrumb & Title -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Katalog Sewa</li>
      </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h1 class="font-heading fs-2 mb-1">Katalog Alat Pendakian</h1>
        <p class="text-muted small mb-0">Pilihan tenda, carrier, sleeping bag, dan alat masak berkualitas tinggi untuk pendakian Anda.</p>
      </div>
      <div class="text-muted small">
        Menampilkan <strong><?= count($products) ?></strong> produk alat
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="lk-card p-3 mb-4">
    <form action="<?= baseUrl('pages/catalog.php') ?>" method="GET" class="row g-2 align-items-center">
      <!-- Category dropdown -->
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Kategori</label>
        <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="0">Semua Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>" <?= $selectedCat === (int)$cat['category_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['category_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Search keyword -->
      <div class="col-md-5">
        <label class="form-label small text-muted mb-1">Cari Nama / Deskripsi</label>
        <div class="input-group input-group-sm">
          <input type="text" name="q" class="form-control" placeholder="Contoh: Naturehike, Osprey, Kompor..." value="<?= htmlspecialchars($searchQuery) ?>">
          <button class="btn btn-outline-forest" type="submit"><i class="fas fa-search me-1"></i> Cari</button>
        </div>
      </div>

      <!-- Sorting -->
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Urutkan</label>
        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga: Terendah &rarr; Tertinggi</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga: Tertinggi &rarr; Terendah</option>
          <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nama (A &rarr; Z)</option>
        </select>
      </div>

      <div class="col-md-1 d-flex align-items-end">
        <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-outline-secondary btn-sm w-100" title="Reset filter" style="height: 48px; display: inline-flex; align-items: center; justify-content: center;">
          <i class="fas fa-sync-alt"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- Category Pills Bar -->
  <div class="d-flex gap-2 overflow-auto pb-3 mb-4 text-nowrap">
    <a href="<?= baseUrl('pages/catalog.php') ?>" class="lk-category-pill <?= $selectedCat === 0 ? 'active' : '' ?>">
      <i class="fas fa-th-large"></i> Semua
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?= baseUrl('pages/catalog.php?category_id=' . $cat['category_id']) ?>" 
         class="lk-category-pill <?= $selectedCat === (int)$cat['category_id'] ? 'active' : '' ?>">
        <?= htmlspecialchars($cat['category_name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Product Grid -->
  <?php if (empty($products)): ?>
    <div class="lk-card text-center py-5">
      <i class="fas fa-mountain fa-3x mb-3 text-muted"></i>
      <h4 class="font-heading">Tidak Ada Produk yang Ditemukan</h4>
      <p class="text-muted small">Coba ganti kata kunci pencarian atau pilih kategori yang berbeda.</p>
      <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest btn-sm mt-2">Reset Pencarian</a>
    </div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php foreach ($products as $prod): 
        $stock = (int)$prod['total_physical_stock'];
        $defaultImg = 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=700&auto=format&fit=crop&q=80';
        $imgUrl = !empty($prod['image']) ? $prod['image'] : $defaultImg;
      ?>
        <div class="col">
          <div class="lk-card h-100 d-flex flex-column">
            <!-- Image Wrapper with 1:1 Aspect Ratio -->
            <div class="lk-product-img-wrapper">
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['product_name']) ?>" loading="lazy">
              
              <!-- Stock Badge -->
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

            <!-- Card Content -->
            <div class="p-3 d-flex flex-column flex-grow-1">
              <div class="small text-muted mb-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
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
                  <span class="lk-deposit-tag" title="Deposit jaminan alat">
                    Dep: <?= formatRupiah($prod['deposit']) ?>
                  </span>
                </div>

                <a href="<?= baseUrl('pages/product.php?id=' . $prod['product_id']) ?>" class="btn btn-outline-forest w-100 btn-sm">
                  <i class="fas fa-calendar-check me-1"></i> Cek Stok & Sewa
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
