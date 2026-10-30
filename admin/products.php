<?php
// admin/products.php - Products and Variants Management (FR-42)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireAdmin();
$db = getDB();

$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrf)) {
        $errors[] = 'Sesi tidak valid.';
    } else {
        // 1. Create Product
        if ($action === 'create_product') {
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['product_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price_per_day'] ?? 0);
            $deposit = (float)($_POST['deposit'] ?? 0);
            $imgUrl = trim($_POST['image_url'] ?? '');
            $varName = trim($_POST['variant_name'] ?? 'Standar');
            $stock = max(1, (int)($_POST['total_stock'] ?? 1));

            // Optional image upload
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
                $newName = 'prod_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                $targetDir = __DIR__ . '/../uploads/products/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetDir . $newName)) {
                    $imgUrl = baseUrl('uploads/products/' . $newName);
                }
            }

            if (empty($name) || $catId <= 0 || $price <= 0) {
                $errors[] = 'Nama produk, kategori, dan harga sewa harian wajib diisi.';
            } else {
                $ins = $db->prepare("
                    INSERT INTO products (category_id, product_name, description, price_per_day, deposit, image, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $ins->execute([$catId, $name, $desc, $price, $deposit, $imgUrl]);
                $newProdId = (int)$db->lastInsertId();

                // Insert initial variant
                $insVar = $db->prepare("
                    INSERT INTO product_variants (product_id, variant_name, total_stock, is_active)
                    VALUES (?, ?, ?, 1)
                ");
                $insVar->execute([$newProdId, $varName, $stock]);

                setFlash('success', 'Produk dan varian awal berhasil ditambahkan!');
                header('Location: ' . baseUrl('admin/products.php'));
                exit;
            }
        }

        // 2. Update Product
        elseif ($action === 'update_product') {
            $prodId = (int)($_POST['product_id'] ?? 0);
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['product_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price_per_day'] ?? 0);
            $deposit = (float)($_POST['deposit'] ?? 0);
            $imgUrl = trim($_POST['image_url'] ?? '');

            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
                $newName = 'prod_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                $targetDir = __DIR__ . '/../uploads/products/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetDir . $newName)) {
                    $imgUrl = baseUrl('uploads/products/' . $newName);
                }
            }

            $upd = $db->prepare("
                UPDATE products 
                SET category_id = ?, product_name = ?, description = ?, price_per_day = ?, deposit = ?, image = ?
                WHERE product_id = ?
            ");
            $upd->execute([$catId, $name, $desc, $price, $deposit, $imgUrl, $prodId]);
            setFlash('success', 'Produk berhasil diperbarui.');
            header('Location: ' . baseUrl('admin/products.php'));
            exit;
        }

        // 3. Toggle Product Active Status
        elseif ($action === 'toggle_product_status') {
            $prodId = (int)($_POST['product_id'] ?? 0);
            $curr = (int)($_POST['current_status'] ?? 1);
            $newStatus = $curr === 1 ? 0 : 1;
            $db->prepare("UPDATE products SET is_active = ? WHERE product_id = ?")->execute([$newStatus, $prodId]);
            setFlash('info', 'Status keaktifan produk berhasil diubah.');
            header('Location: ' . baseUrl('admin/products.php'));
            exit;
        }

        // 4. Add Variant to Existing Product
        elseif ($action === 'add_variant') {
            $prodId = (int)($_POST['product_id'] ?? 0);
            $varName = trim($_POST['variant_name'] ?? '');
            $stock = max(0, (int)($_POST['total_stock'] ?? 0));

            if (!empty($varName) && $prodId > 0) {
                try {
                    $insVar = $db->prepare("INSERT INTO product_variants (product_id, variant_name, total_stock, is_active) VALUES (?, ?, ?, 1)");
                    $insVar->execute([$prodId, $varName, $stock]);
                    setFlash('success', 'Varian baru berhasil ditambahkan.');
                } catch (Exception $e) {
                    $errors[] = 'Gagal menambah varian: Nama varian mungkin sudah ada pada produk ini.';
                }
            }
        }

        // 5. Update Variant Stock
        elseif ($action === 'update_variant_stock') {
            $varId = (int)($_POST['variant_id'] ?? 0);
            $stock = max(0, (int)($_POST['total_stock'] ?? 0));
            $db->prepare("UPDATE product_variants SET total_stock = ? WHERE variant_id = ?")->execute([$stock, $varId]);
            setFlash('success', 'Jumlah stok fisik varian berhasil diperbarui.');
            header('Location: ' . baseUrl('admin/products.php'));
            exit;
        }
    }
}

// Fetch categories for select dropdown
$categories = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();

// Fetch products with their variants
$products = $db->query("
    SELECT p.*, c.category_name,
           COALESCE(SUM(pv.total_stock), 0) AS total_physical_units,
           COUNT(pv.variant_id) AS variant_count
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    LEFT JOIN product_variants pv ON pv.product_id = p.product_id AND pv.is_active = 1
    GROUP BY p.product_id
    ORDER BY p.product_id DESC
")->fetchAll();

$adminTitle = 'Kelola Produk & Varian - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Manajemen Produk & Varian Stok</h1>
    <p class="text-muted small mb-0">Kelola katalog alat pendakian, kapasitas, warna varian, tarif sewa per hari, dan unit fisik.</p>
  </div>
  <button type="button" class="btn btn-sm btn-forest" data-bs-toggle="modal" data-bs-target="#addProductModal">
    <i class="fas fa-plus me-1"></i> Tambah Produk Baru
  </button>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger py-2 small mb-4">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="lk-card p-4">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light small text-muted">
        <tr>
          <th>Produk</th>
          <th>Kategori</th>
          <th>Sewa/Hari</th>
          <th>Deposit/Unit</th>
          <th>Varian & Stok Fisik</th>
          <th>Status</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $prod): 
          // Fetch variants for this product
          $vStmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id ASC");
          $vStmt->execute([$prod['product_id']]);
          $prodVariants = $vStmt->fetchAll();
        ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-3">
                <img src="<?= htmlspecialchars($prod['image'] ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=200') ?>" 
                     alt="<?= htmlspecialchars($prod['product_name']) ?>" 
                     class="rounded" style="width: 54px; height: 54px; object-fit: cover;">
                <div>
                  <a href="<?= baseUrl('pages/product.php?id=' . $prod['product_id']) ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                    <?= htmlspecialchars($prod['product_name']) ?>
                  </a>
                  <div class="small text-muted" style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                    <?= htmlspecialchars($prod['description'] ?: 'Tidak ada deskripsi.') ?>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars($prod['category_name']) ?></span>
            </td>
            <td class="text-forest fw-bold"><?= formatRupiah($prod['price_per_day']) ?></td>
            <td class="small text-muted"><?= formatRupiah($prod['deposit']) ?></td>
            <td>
              <div><strong><?= $prod['total_physical_units'] ?> unit</strong> total</div>
              <div class="small text-muted"><?= count($prodVariants) ?> varian</div>
              <button type="button" class="btn btn-link text-forest p-0 small" data-bs-toggle="modal" data-bs-target="#variantModal-<?= $prod['product_id'] ?>">
                <i class="fas fa-layer-group me-1"></i> Kelola Varian
              </button>
            </td>
            <td>
              <?php if ($prod['is_active']): ?>
                <span class="badge bg-success">Aktif</span>
              <?php else: ?>
                <span class="badge bg-secondary">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                <!-- Edit button -->
                <button type="button" class="btn btn-sm btn-outline-forest py-1 px-2" data-bs-toggle="modal" data-bs-target="#editProdModal-<?= $prod['product_id'] ?>" title="Ubah Produk">
                  <i class="fas fa-edit"></i>
                </button>

                <!-- Toggle Active -->
                <form action="<?= baseUrl('admin/products.php') ?>" method="POST" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_product_status">
                  <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $prod['is_active'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" title="<?= $prod['is_active'] ? 'Nonaktifkan Produk' : 'Aktifkan Produk' ?>">
                    <i class="fas <?= $prod['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                  </button>
                </form>
              </div>

              <!-- Edit Product Modal -->
              <div class="modal fade" id="editProdModal-<?= $prod['product_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content text-start">
                    <form action="<?= baseUrl('admin/products.php') ?>" method="POST" enctype="multipart/form-data">
                      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                      <input type="hidden" name="action" value="update_product">
                      <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">

                      <div class="modal-header">
                        <h5 class="modal-title font-heading fs-6">Ubah Produk: <?= htmlspecialchars($prod['product_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row g-3">
                          <div class="col-md-8">
                            <label class="form-label small fw-bold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($prod['product_name']) ?>" required>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label small fw-bold">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                              <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>" <?= $c['category_id'] == $prod['category_id'] ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($c['category_name']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-bold">Harga Sewa / Hari (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price_per_day" class="form-control" value="<?= $prod['price_per_day'] ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label small fw-bold">Uang Jaminan Deposit / Unit (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="deposit" class="form-control" value="<?= $prod['deposit'] ?>" required>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label small fw-bold">Deskripsi & Kelengkapan</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($prod['description']) ?></textarea>
                          </div>
                          <div class="col-md-8">
                            <label class="form-label small fw-bold">URL Gambar Produk</label>
                            <input type="url" name="image_url" class="form-control" value="<?= htmlspecialchars($prod['image']) ?>" placeholder="https://...">
                          </div>
                          <div class="col-md-4">
                            <label class="form-label small fw-bold">Atau Unggah File Foto</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-forest btn-sm">Simpan Perubahan</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Manage Variants Modal -->
              <div class="modal fade" id="variantModal-<?= $prod['product_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content text-start">
                    <div class="modal-header">
                      <h5 class="modal-title font-heading fs-6">Kelola Varian: <?= htmlspecialchars($prod['product_name']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <!-- Existing variants table -->
                      <h6 class="small fw-bold text-muted mb-2">Daftar Varian & Stok Fisik:</h6>
                      <div class="list-group mb-4">
                        <?php foreach ($prodVariants as $pv): ?>
                          <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                              <strong><?= htmlspecialchars($pv['variant_name']) ?></strong>
                              <span class="badge bg-light text-dark border ms-2"><?= $pv['total_stock'] ?> Unit</span>
                            </div>
                            <form action="<?= baseUrl('admin/products.php') ?>" method="POST" class="d-flex align-items-center gap-1">
                              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                              <input type="hidden" name="action" value="update_variant_stock">
                              <input type="hidden" name="variant_id" value="<?= $pv['variant_id'] ?>">
                              <input type="number" name="total_stock" class="form-control form-control-sm" value="<?= $pv['total_stock'] ?>" min="0" style="width: 70px;">
                              <button type="submit" class="btn btn-sm btn-outline-forest" title="Update Stok"><i class="fas fa-save"></i></button>
                            </form>
                          </div>
                        <?php endforeach; ?>
                      </div>

                      <!-- Add Variant Form -->
                      <h6 class="small fw-bold text-muted mb-2">Tambah Varian Baru:</h6>
                      <form action="<?= baseUrl('admin/products.php') ?>" method="POST" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="add_variant">
                        <input type="hidden" name="product_id" value="<?= $prod['product_id'] ?>">

                        <div class="col-7">
                          <input type="text" name="variant_name" class="form-control form-control-sm" placeholder="Nama varian (contoh: Hijau / 60L)" required>
                        </div>
                        <div class="col-3">
                          <input type="number" name="total_stock" class="form-control form-control-sm" placeholder="Stok" value="1" min="0" required>
                        </div>
                        <div class="col-2">
                          <button type="submit" class="btn btn-sm btn-forest w-100"><i class="fas fa-plus"></i></button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="<?= baseUrl('admin/products.php') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_product">

        <div class="modal-header">
          <h5 class="modal-title font-heading fs-6">Tambah Produk & Varian Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small fw-bold">Nama Produk <span class="text-danger">*</span></label>
              <input type="text" name="product_name" class="form-control" placeholder="Contoh: Tenda Dome Naturehike 2P" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Kategori <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="">Pilih Kategori...</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Harga Sewa / Hari (Rp) <span class="text-danger">*</span></label>
              <input type="number" name="price_per_day" class="form-control" placeholder="50000" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Uang Jaminan Deposit / Unit (Rp) <span class="text-danger">*</span></label>
              <input type="number" name="deposit" class="form-control" placeholder="100000" min="0" required>
            </div>
            <div class="col-md-12">
              <label class="form-label small fw-bold">Deskripsi Alat</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Spesifikasi bahan, kapasitas, kelengkapan frame..."></textarea>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-bold">URL Foto Produk</label>
              <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/...">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Atau Upload Gambar</label>
              <input type="file" name="image_file" class="form-control" accept="image/*">
            </div>

            <!-- Initial Variant -->
            <div class="col-md-12 pt-2 border-top">
              <h6 class="small fw-bold text-forest mb-2">Varian Awal & Stok Fisik</h6>
              <div class="row g-2">
                <div class="col-md-8">
                  <input type="text" name="variant_name" class="form-control form-control-sm" placeholder="Nama varian (contoh: Standar / 2 Orang / Abu-abu)" value="Standar" required>
                </div>
                <div class="col-md-4">
                  <input type="number" name="total_stock" class="form-control form-control-sm" placeholder="Jumlah unit fisik" value="3" min="1" required>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-forest btn-sm">Simpan Produk</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
