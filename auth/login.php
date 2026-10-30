<?php
// auth/login.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? baseUrl('admin/index.php') : baseUrl()));
    exit;
}

$error = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    $emailVal = $email;

    if (!validateCsrfToken($csrf)) {
        $error = 'Sesi tidak valid, silakan coba kembali.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id, name, email, password, role, verification_status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['user_id'];
            setFlash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['name']) . '!');

            $returnUrl = $_SESSION['return_url'] ?? '';
            unset($_SESSION['return_url']);

            if (!empty($returnUrl) && !str_contains($returnUrl, 'login.php')) {
                header('Location: ' . $returnUrl);
            } elseif ($user['role'] === 'admin') {
                header('Location: ' . baseUrl('admin/index.php'));
            } else {
                header('Location: ' . baseUrl());
            }
            exit;
        } else {
            $error = 'Email atau password yang Anda masukkan salah.';
        }
    }
}

$pageTitle = 'Masuk ke Akun - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="lk-card p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; background-color: var(--lk-primary-tint); color: var(--lk-forest);">
            <i class="fas fa-sign-in-alt fa-2x"></i>
          </div>
          <h2 class="font-heading fs-3 mb-1">Masuk ke Akun</h2>
          <p class="text-muted small">Kelola penyewaan dan cek riwayat alat pendakian Anda</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small mb-4">
            <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form action="<?= baseUrl('auth/login.php') ?>" method="POST">
          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

          <div class="mb-3">
            <label for="email" class="form-label">Alamat Email</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted border-end-0"><i class="far fa-envelope"></i></span>
              <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= htmlspecialchars($emailVal) ?>" placeholder="nama@email.com" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <label for="password" class="form-label">Password</label>
            </div>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-lock"></i></span>
              <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="••••••••" required>
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-forest py-2">
              <i class="fas fa-sign-in-alt me-1"></i> Masuk Sekarang
            </button>
          </div>

          <div class="text-center small text-muted">
            Belum punya akun? <a href="<?= baseUrl('auth/register.php') ?>" class="fw-bold">Daftar Akun Baru</a>
          </div>
        </form>

        <!-- Quick Demo Login Helper for Testing -->
        <div class="mt-4 pt-3 border-top text-center">
          <div class="small text-muted mb-2">Akun Uji Coba Cepat:</div>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillLogin('admin@rentgear.id', 'password123')">
              <i class="fas fa-user-shield me-1"></i> Login Admin
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillLogin('pendaki@rentgear.id', 'password123')">
              <i class="fas fa-user me-1"></i> Login Penyewa
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function fillLogin(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
