<?php
// pages/profile.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireLogin('Silakan login untuk melihat profil akun Anda.');
$user = currentUser();
$db = getDB();

$reuploadErrors = [];

// Handle re-upload KTP/SIM if rejected (FR-08)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reupload_idcard') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $reuploadErrors[] = 'Sesi tidak valid, silakan coba lagi.';
    } elseif ($user['verification_status'] !== 'rejected') {
        $reuploadErrors[] = 'Pengunggahan ulang hanya untuk akun dengan status verifikasi ditolak.';
    } else {
        if (isset($_FILES['idcard_photo']) && $_FILES['idcard_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['idcard_photo'];
            if ($file['size'] > 5 * 1024 * 1024) {
                $reuploadErrors[] = 'Ukuran file maksimal 5 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                    $reuploadErrors[] = 'Format file harus JPG, PNG, atau WEBP.';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFileName = 'ktp_' . bin2hex(random_bytes(16)) . '.' . strtolower($ext);
                    $targetDir = __DIR__ . '/../uploads/idcards/';
                    $targetFile = $targetDir . $newFileName;

                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        $newPath = 'uploads/idcards/' . $newFileName;
                        $stmt = $db->prepare("
                            UPDATE users 
                            SET idcard_photo = ?, verification_status = 'pending', verification_notes = NULL 
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$newPath, $user['user_id']]);

                        setFlash('success', 'Foto KTP/SIM berhasil diunggah ulang! Akun Anda kini kembali dalam antrean verifikasi admin.');
                        header('Location: ' . baseUrl('pages/profile.php'));
                        exit;
                    } else {
                        $reuploadErrors[] = 'Gagal menyimpan file identitas.';
                    }
                }
            }
        } else {
            $reuploadErrors[] = 'Pilih file foto KTP/SIM terlebih dahulu.';
        }
    }
}

$pageTitle = 'Profil & Status Verifikasi - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row g-4 justify-content-center">
    <div class="col-lg-8">
      
      <!-- Account Info Card -->
      <div class="lk-card p-4 p-md-5 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom pb-4 mb-4">
          <div>
            <span class="text-uppercase small text-muted fw-bold">Akun Penyewa</span>
            <h2 class="font-heading fs-3 mb-1"><?= htmlspecialchars($user['name']) ?></h2>
            <div class="text-muted small">
              <i class="far fa-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?> &bull; 
              <i class="fab fa-whatsapp me-1 text-success"></i> <?= htmlspecialchars($user['user_number']) ?>
            </div>
          </div>
          <div>
            <?= getUserVerificationBadge($user['verification_status']) ?>
          </div>
        </div>

        <!-- Verification Status Notice -->
        <?php if ($user['verification_status'] === 'pending'): ?>
          <div class="alert alert-warning d-flex align-items-start gap-3 p-3">
            <i class="fas fa-clock fa-2x text-warning mt-1"></i>
            <div>
              <h6 class="fw-bold mb-1">Akun Menunggu Verifikasi Admin</h6>
              <p class="small mb-0">
                Foto identitas (KTP/SIM) Anda sedang ditinjau oleh staf admin toko RentGear. Anda sudah dapat menjelajah katalog dan mengecek ketersediaan stok alat. Proses checkout pesanan dapat dilakukan segera setelah akun Anda diverifikasi (maksimal 12 jam kerja).
              </p>
            </div>
          </div>
        <?php elseif ($user['verification_status'] === 'verified'): ?>
          <div class="alert alert-success d-flex align-items-start gap-3 p-3">
            <i class="fas fa-check-circle fa-2x text-success mt-1"></i>
            <div>
              <h6 class="fw-bold mb-1">Akun Terverifikasi Penuh</h6>
              <p class="small mb-0">
                Identitas Anda telah berhasil diverifikasi oleh admin. Anda memiliki hak akses penuh untuk melakukan booking dan menyewa semua perlengkapan pendakian di RentGear.
              </p>
            </div>
          </div>
        <?php elseif ($user['verification_status'] === 'rejected'): ?>
          <div class="alert alert-danger d-flex align-items-start gap-3 p-3">
            <i class="fas fa-times-circle fa-2x text-danger mt-1"></i>
            <div>
              <h6 class="fw-bold mb-1">Verifikasi Identitas Ditolak</h6>
              <p class="small mb-2">
                Mohon maaf, dokumen identitas yang Anda unggah tidak dapat diverifikasi oleh admin.
              </p>
              <?php if (!empty($user['verification_notes'])): ?>
                <div class="p-2 rounded bg-white border small text-dark mb-2">
                  <strong>Catatan / Alasan Admin:</strong> <?= htmlspecialchars($user['verification_notes']) ?>
                </div>
              <?php endif; ?>
              <p class="small mb-0">
                Silakan unggah ulang foto KTP/SIM yang jelas, terbaca, dan tidak buram pada formulir di bawah.
              </p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Identity Document Details -->
        <div class="mt-4 pt-2">
          <h5 class="fw-bold mb-3 fs-6"><i class="fas fa-id-card text-forest me-2"></i> Dokumen Identitas Terunggah</h5>
          <?php if (!empty($user['idcard_photo'])): ?>
            <div class="d-flex align-items-center justify-content-between p-3 rounded border" style="background-color: var(--lk-blush);">
              <div class="d-flex align-items-center gap-3">
                <i class="fas fa-file-image fa-2x text-forest"></i>
                <div>
                  <div class="fw-bold small">Foto KTP / SIM Penyewa</div>
                  <div class="text-muted" style="font-size: 12px;">Tersimpan secara terenkripsi dan terlindungi di server</div>
                </div>
              </div>
              <a href="<?= baseUrl('api/download-doc.php?type=idcard&id=' . $user['user_id']) ?>" target="_blank" class="btn btn-outline-forest btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Dokumen
              </a>
            </div>
          <?php else: ?>
            <p class="small text-muted">Belum ada dokumen yang diunggah.</p>
          <?php endif; ?>
        </div>

        <!-- Re-upload Form if Rejected (FR-08) -->
        <?php if ($user['verification_status'] === 'rejected'): ?>
          <div class="mt-4 pt-4 border-top">
            <h5 class="font-heading fs-5 mb-2"><i class="fas fa-upload text-forest me-2"></i> Unggah Ulang Dokumen Identitas</h5>
            <p class="small text-muted mb-3">Pastikan foto KTP/SIM tampak jelas, tidak terpotong, dan tulisan terbaca dengan baik.</p>

            <?php if (!empty($reuploadErrors)): ?>
              <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                  <?php foreach ($reuploadErrors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form action="<?= baseUrl('pages/profile.php') ?>" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
              <input type="hidden" name="action" value="reupload_idcard">

              <div class="mb-3">
                <input type="file" class="form-control image-preview-input" name="idcard_photo" accept="image/jpeg,image/png,image/webp" data-preview-target="reuploadPreview" required>
              </div>
              <div class="mb-3 text-center">
                <img id="reuploadPreview" src="#" alt="Pratinjau KTP Baru" class="img-thumbnail d-none" style="max-height: 180px;">
              </div>
              <button type="submit" class="btn btn-forest">
                <i class="fas fa-cloud-upload-alt me-1"></i> Kirim Ulang untuk Verifikasi
              </button>
            </form>
          </div>
        <?php endif; ?>

        <!-- Quick actions -->
        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <a href="<?= baseUrl('pages/my-rentals.php') ?>" class="btn btn-outline-forest btn-sm">
            <i class="fas fa-box-open me-1"></i> Riwayat Sewa Saya
          </a>
          <a href="<?= baseUrl('pages/catalog.php') ?>" class="btn btn-forest btn-sm">
            <i class="fas fa-compass me-1"></i> Jelajah Alat Pendakian
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
