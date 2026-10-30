<?php
// auth/register.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

if (isLoggedIn()) {
    header('Location: ' . baseUrl());
    exit;
}

$errors = [];
$nameVal = '';
$emailVal = '';
$phoneVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['user_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    $nameVal = $name;
    $emailVal = $email;
    $phoneVal = $phone;

    if (!validateCsrfToken($csrf)) {
        $errors[] = 'Sesi tidak valid, silakan refresh halaman.';
    }

    if (empty($name) || strlen($name) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    }

    if (empty($phone) || strlen($phone) < 9) {
        $errors[] = 'Nomor WhatsApp tidak valid (minimal 9 digit).';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Check unique email
    $db = getDB();
    $check = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        $errors[] = 'Alamat email sudah terdaftar. Silakan gunakan email lain atau login.';
    }

    // Handle KTP/SIM photo upload (FR-02)
    $idcardRelativePath = null;
    if (isset($_FILES['idcard_photo']) && $_FILES['idcard_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['idcard_photo'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah foto KTP/SIM.';
        } else {
            // Check size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran foto KTP/SIM maksimal 5 MB.';
            }

            // Check mime type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mime, $allowedMimes)) {
                $errors[] = 'Format foto KTP/SIM harus berformat JPG, PNG, atau WEBP.';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $cleanExt = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp']) ? strtolower($ext) : 'jpg';
                $newFileName = 'ktp_' . bin2hex(random_bytes(16)) . '.' . $cleanExt;
                $targetDir = __DIR__ . '/../uploads/idcards/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $targetFile = $targetDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $idcardRelativePath = 'uploads/idcards/' . $newFileName;
                } else {
                    $errors[] = 'Gagal menyimpan file identitas di server.';
                }
            }
        }
    } else {
        $errors[] = 'Foto KTP atau SIM wajib diunggah sebagai syarat identitas sewa.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $insert = $db->prepare("
            INSERT INTO users (name, email, password, user_number, role, idcard_photo, verification_status, created_at)
            VALUES (?, ?, ?, ?, 'user', ?, 'pending', NOW())
        ");
        $insert->execute([$name, $email, $passwordHash, $phone, $idcardRelativePath]);
        $newUserId = (int)$db->lastInsertId();

        $_SESSION['user_id'] = $newUserId;
        setFlash('success', 'Pendaftaran berhasil! Akun Anda telah dibuat. Tim admin kami sedang memverifikasi dokumen identitas KTP/SIM Anda.');
        header('Location: ' . baseUrl('pages/profile.php'));
        exit;
    }
}

$pageTitle = 'Daftar Akun Penyewa - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="lk-card p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; background-color: var(--lk-primary-tint); color: var(--lk-forest);">
            <i class="fas fa-user-plus fa-2x"></i>
          </div>
          <h2 class="font-heading fs-3 mb-1">Daftar Akun Penyewa</h2>
          <p class="text-muted small">Buat akun untuk sewa perlengkapan pendakian dengan mudah dan aman</p>
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

        <form action="<?= baseUrl('auth/register.php') ?>" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

          <div class="mb-3">
            <label for="name" class="form-label">Nama Lengkap (Sesuai KTP) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($nameVal) ?>" placeholder="Contoh: Budi Santoso" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($emailVal) ?>" placeholder="budi@gmail.com" required>
            </div>
            <div class="col-md-6">
              <label for="user_number" class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="user_number" name="user_number" value="<?= htmlspecialchars($phoneVal) ?>" placeholder="081234567890" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="col-md-6">
              <label for="password_confirm" class="form-label">Ulangi Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required>
            </div>
          </div>

          <!-- Upload KTP/SIM -->
          <div class="mb-4 p-3 rounded" style="background-color: var(--lk-blush); border: 1px dashed var(--lk-sage);">
            <label for="idcard_photo" class="form-label fw-bold mb-1">
              <i class="fas fa-id-card text-forest me-1"></i> Foto KTP / SIM Asli <span class="text-danger">*</span>
            </label>
            <p class="small text-muted mb-2">
              Diperlukan untuk jaminan identitas penyewaan. Dokumen Anda tersimpan aman dan hanya dapat diakses oleh admin verifikasi toko. (Format: JPG, PNG, WEBP maks 5 MB).
            </p>
            <input type="file" class="form-control image-preview-input" id="idcard_photo" name="idcard_photo" accept="image/jpeg,image/png,image/webp" data-preview-target="ktpPreview" required>
            <div class="mt-2 text-center">
              <img id="ktpPreview" src="#" alt="Pratinjau KTP" class="img-thumbnail d-none" style="max-height: 160px;">
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-forest py-2">
              <i class="fas fa-user-check me-1"></i> Daftar Sekarang
            </button>
          </div>

          <div class="text-center small text-muted">
            Sudah punya akun? <a href="<?= baseUrl('auth/login.php') ?>" class="fw-bold">Masuk di sini</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
