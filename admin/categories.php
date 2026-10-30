<?php
// admin/categories.php - Category Management (FR-42)
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
        if ($action === 'create') {
            $catName = trim($_POST['category_name'] ?? '');
            if (empty($catName)) {
                $errors[] = 'Nama kategori tidak boleh kosong.';
            } else {
                $check = $db->prepare("SELECT category_id FROM categories WHERE category_name = ?");
                $check->execute([$catName]);
                if ($check->rowCount() > 0) {
                    $errors[] = 'Kategori dengan nama tersebut sudah ada.';
                } else {
                    $stmt = $db->prepare("INSERT INTO categories (category_name) VALUES (?)");
                    $stmt->execute([$catName]);
                    setFlash('success', 'Kategori baru berhasil ditambahkan.');
                    header('Location: ' . baseUrl('admin/categories.php'));
                    exit;
                }
            }
        } elseif ($action === 'update') {
            $catId = (int)($_POST['category_id'] ?? 0);
            $catName = trim($_POST['category_name'] ?? '');
            if (empty($catName) || $catId <= 0) {
                $errors[] = 'Nama kategori tidak valid.';
            } else {
                $stmt = $db->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
                $stmt->execute([$catName, $catId]);
                setFlash('success', 'Kategori berhasil diperbarui.');
                header('Location: ' . baseUrl('admin/categories.php'));
                exit;
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['category_id'] ?? 0);
            // Check if products exist under this category
            $pCheck = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $pCheck->execute([$catId]);
            $count = (int)$pCheck->fetchColumn();

            if ($count > 0) {
                $errors[] = "Kategori tidak dapat dihapus karena masih memuat {$count} produk. Hapus atau pindahkan produk terlebih dahulu.";
            } else {
                $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->execute([$catId]);
                setFlash('success', 'Kategori berhasil dihapus.');
                header('Location: ' . baseUrl('admin/categories.php'));
                exit;
            }
        }
    }
}

// Fetch categories with product counts
$categories = $db->query("
    SELECT c.*, COUNT(p.product_id) as total_products
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name ASC
")->fetchAll();

$adminTitle = 'Kelola Kategori - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Manajemen Kategori Alat</h1>
    <p class="text-muted small mb-0">Kelola kelompok kategori perlengkapan seperti Tenda, Carrier, Sleeping Bag, dll.</p>
  </div>
  <button type="button" class="btn btn-sm btn-forest" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
    <i class="fas fa-plus me-1"></i> Tambah Kategori
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
          <th>ID</th>
          <th>Nama Kategori</th>
          <th>Jumlah Produk</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><strong>#<?= $cat['category_id'] ?></strong></td>
            <td class="fw-bold fs-6"><?= htmlspecialchars($cat['category_name']) ?></td>
            <td>
              <span class="badge bg-light text-dark border"><?= $cat['total_products'] ?> Produk</span>
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                <!-- Edit Trigger -->
                <button type="button" class="btn btn-sm btn-outline-forest py-1 px-2" data-bs-toggle="modal" data-bs-target="#editModal-<?= $cat['category_id'] ?>">
                  <i class="fas fa-edit"></i> Ubah
                </button>

                <!-- Delete Trigger -->
                <form action="<?= baseUrl('admin/categories.php') ?>" method="POST" onsubmit="return confirm('Hapus kategori <?= htmlspecialchars($cat['category_name']) ?>?')">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" <?= $cat['total_products'] > 0 ? 'disabled title="Kategori masih memiliki produk"' : '' ?>>
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>

              <!-- Edit Modal -->
              <div class="modal fade" id="editModal-<?= $cat['category_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content text-start">
                    <form action="<?= baseUrl('admin/categories.php') ?>" method="POST">
                      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">

                      <div class="modal-header">
                        <h5 class="modal-title font-heading fs-6">Ubah Kategori</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <label class="form-label small fw-bold">Nama Kategori</label>
                        <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($cat['category_name']) ?>" required>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-forest btn-sm">Simpan Perubahan</button>
                      </div>
                    </form>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= baseUrl('admin/categories.php') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create">

        <div class="modal-header">
          <h5 class="modal-title font-heading fs-6">Tambah Kategori Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label small fw-bold">Nama Kategori <span class="text-danger">*</span></label>
          <input type="text" name="category_name" class="form-control" placeholder="Contoh: Flysheet & Tarp..." required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-forest btn-sm">Tambah Kategori</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
