<?php
// includes/header.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/cart.php';

$currentUser = currentUser();
$cartTotals = getCartTotals();
$flashes = getFlash();

$pageTitle = $pageTitle ?? (APP_NAME . ' - ' . APP_TAGLINE);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="Sewa alat pendakian dan camping terlengkap, terawat, dan harga bersahabat. Tenda, carrier, sleeping bag, kompor gunung dengan cek stok otomatis.">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Lukani Theme CSS -->
  <link rel="stylesheet" href="<?= baseUrl('assets/css/lukani-theme.css') ?>">

  <script>
    const baseUrl = '<?= baseUrl() ?>';
  </script>
</head>
<body>

  <!-- Top Bar -->
  <div class="lk-topbar d-none d-md-block">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-4">
          <span><i class="fab fa-whatsapp text-success me-1"></i> WA Toko: <a href="<?= generateWhatsAppUrl(STORE_PHONE, 'Halo RentGear, saya ingin bertanya tentang sewa alat pendakian.') ?>" target="_blank">+<?= STORE_PHONE ?></a></span>
          <span><i class="far fa-clock me-1"></i> <?= STORE_HOURS ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <?php if ($currentUser): ?>
            <span class="small">Halo, <strong><?= htmlspecialchars($currentUser['name']) ?></strong></span>
            <?= getUserVerificationBadge($currentUser['verification_status']) ?>
            <?php if (isAdmin()): ?>
              <span class="badge bg-danger">ADMIN</span>
            <?php endif; ?>
          <?php else: ?>
            <a href="<?= baseUrl('auth/login.php') ?>"><i class="fas fa-sign-in-alt me-1"></i> Masuk</a>
            <span class="text-muted">|</span>
            <a href="<?= baseUrl('auth/register.php') ?>"><i class="fas fa-user-plus me-1"></i> Daftar Akun</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Navigation Bar -->
  <header class="lk-header">
    <nav class="navbar navbar-expand-lg py-3">
      <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="<?= baseUrl() ?>">
          <i class="fas fa-mountain"></i>
          <span>Rent<span style="color: var(--lk-leaf);">Gear</span></span>
        </a>

        <!-- Mobile Toggler -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
          <button class="lk-action-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-label="Keranjang">
            <i class="fas fa-shopping-bag"></i>
            <span class="lk-action-badge"><?= $cartTotals['items_count'] ?></span>
          </button>
          <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>

        <!-- Nav Items -->
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
            <li class="nav-link-item">
              <a class="nav-link <?= empty($_SERVER['SCRIPT_NAME']) || str_ends_with($_SERVER['SCRIPT_NAME'], 'index.php') && !str_contains($_SERVER['SCRIPT_NAME'], 'admin') ? 'active' : '' ?>" href="<?= baseUrl() ?>">Beranda</a>
            </li>
            <li class="nav-link-item">
              <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'catalog.php') ? 'active' : '' ?>" href="<?= baseUrl('pages/catalog.php') ?>">Katalog Sewa</a>
            </li>
            <li class="nav-link-item">
              <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'how-it-works.php') ? 'active' : '' ?>" href="<?= baseUrl('pages/how-it-works.php') ?>">Cara Menyewa</a>
            </li>
            <?php if ($currentUser): ?>
              <li class="nav-link-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'my-rentals.php') ? 'active' : '' ?>" href="<?= baseUrl('pages/my-rentals.php') ?>">
                  <i class="fas fa-list-alt me-1"></i> Sewa Saya
                </a>
              </li>
            <?php endif; ?>
            <li class="nav-link-item">
              <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'contact.php') ? 'active' : '' ?>" href="<?= baseUrl('pages/contact.php') ?>">Kontak & Lokasi</a>
            </li>
          </ul>

          <!-- Search form in navbar -->
          <form class="d-flex me-lg-3 my-2 my-lg-0" action="<?= baseUrl('pages/catalog.php') ?>" method="GET" style="max-width: 250px;">
            <div class="input-group input-group-sm">
              <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari tenda, carrier..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
              <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            </div>
          </form>

          <!-- Action Buttons Desktop -->
          <div class="d-none d-lg-flex align-items-center gap-2">
            <!-- Account dropdown -->
            <?php if ($currentUser): ?>
              <div class="dropdown">
                <button class="lk-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Akun Saya">
                  <i class="far fa-user"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="border-radius: var(--lk-radius-md); min-width: 220px;">
                  <li class="px-3 py-2 border-bottom">
                    <div class="fw-bold text-truncate"><?= htmlspecialchars($currentUser['name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($currentUser['email']) ?></div>
                    <div class="mt-1"><?= getUserVerificationBadge($currentUser['verification_status']) ?></div>
                  </li>
                  <li><a class="dropdown-item py-2" href="<?= baseUrl('pages/profile.php') ?>"><i class="fas fa-id-card me-2 text-muted"></i> Profil & Verifikasi KTP</a></li>
                  <li><a class="dropdown-item py-2" href="<?= baseUrl('pages/my-rentals.php') ?>"><i class="fas fa-box-open me-2 text-muted"></i> Riwayat Sewa Saya</a></li>
                  <?php if (isAdmin()): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-primary fw-bold" href="<?= baseUrl('admin/index.php') ?>"><i class="fas fa-shield-alt me-2"></i> Panel Dashboard Admin</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item py-2 text-danger" href="<?= baseUrl('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
                </ul>
              </div>
            <?php else: ?>
              <a href="<?= baseUrl('auth/login.php') ?>" class="lk-action-btn" title="Masuk / Daftar">
                <i class="far fa-user"></i>
              </a>
            <?php endif; ?>

            <!-- Cart Trigger -->
            <button class="lk-action-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-label="Buka Keranjang">
              <i class="fas fa-shopping-bag"></i>
              <span class="lk-action-badge"><?= $cartTotals['items_count'] ?></span>
            </button>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <!-- Flash Messages -->
  <?php if (!empty($flashes)): ?>
    <div class="container mt-3">
      <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
          <i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Includes Cart Drawer and Guest Modal -->
  <?php include __DIR__ . '/cart_drawer.php'; ?>
  <?php include __DIR__ . '/guest_modal.php'; ?>
