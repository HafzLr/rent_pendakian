<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/functions.php';

requireAdmin();
$currentUser = currentUser();
$db = getDB();

// Badges counts
$pendingUsersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND verification_status = 'pending'")->fetchColumn();
$pendingPaymentsCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn();
$activeRentalsCount = (int)$db->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminTitle = $adminTitle ?? 'Admin Panel - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($adminTitle) ?></title>
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Lukani Theme CSS -->
  <link rel="stylesheet" href="<?= baseUrl('assets/css/lukani-theme.css') ?>">
</head>
<body style="background-color: #F6F7F5;">

  <!-- Admin Topbar -->
  <nav class="navbar navbar-expand-lg bg-dark navbar-dark px-3 py-2 border-bottom sticky-top shadow-sm" style="background-color: var(--lk-ink) !important;">
    <div class="container-fluid">
      <a class="navbar-brand font-heading text-white fs-4" href="<?= baseUrl('admin/index.php') ?>">
        <i class="fas fa-mountain text-success me-2"></i> RentGear <span class="badge bg-danger small fs-6 ms-1">ADMIN</span>
      </a>

      <div class="d-flex align-items-center gap-3">
        <a href="<?= baseUrl() ?>" target="_blank" class="btn btn-outline-light btn-sm" title="Buka Toko Utama">
          <i class="fas fa-external-link-alt me-1"></i> Buka Website Toko
        </a>
        <div class="dropdown">
          <button class="btn btn-dark btn-sm dropdown-toggle border-secondary" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-user-shield me-1 text-success"></i> <?= htmlspecialchars($currentUser['name']) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($currentUser['email']) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= baseUrl('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">
      <!-- Admin Sidebar Left Column -->
      <nav class="col-md-3 col-lg-2 d-md-block lk-admin-sidebar collapse show p-0" id="adminSidebar">
        <div class="position-sticky pt-3">
          <div class="px-4 py-2 text-uppercase text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.08em;">
            Menu Utama
          </div>
          <ul class="nav flex-column mb-3">
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'index.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/index.php') ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'users.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/users.php') ?>">
                <i class="fas fa-user-check"></i> Verifikasi KTP
                <?php if ($pendingUsersCount > 0): ?>
                  <span class="badge bg-warning text-dark ms-auto"><?= $pendingUsersCount ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'payments.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/payments.php') ?>">
                <i class="fas fa-money-check-alt"></i> Verifikasi Bayar
                <?php if ($pendingPaymentsCount > 0): ?>
                  <span class="badge bg-warning text-dark ms-auto"><?= $pendingPaymentsCount ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'rentals.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/rentals.php') ?>">
                <i class="fas fa-boxes"></i> Daftar Pesanan
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'returns.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/returns.php') ?>">
                <i class="fas fa-undo-alt"></i> Serah & Kembali
                <?php if ($activeRentalsCount > 0): ?>
                  <span class="badge bg-info ms-auto"><?= $activeRentalsCount ?></span>
                <?php endif; ?>
              </a>
            </li>
          </ul>

          <div class="px-4 py-2 text-uppercase text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.08em;">
            Katalog & Jadwal
          </div>
          <ul class="nav flex-column mb-3">
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'products.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/products.php') ?>">
                <i class="fas fa-campground"></i> Produk & Varian
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'categories.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/categories.php') ?>">
                <i class="fas fa-tags"></i> Kategori
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'calendar.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/calendar.php') ?>">
                <i class="fas fa-calendar-alt"></i> Kalender Stok
              </a>
            </li>
            <li class="nav-item">
              <a class="lk-admin-nav-item <?= $currentScript === 'reports.php' ? 'active' : '' ?>" href="<?= baseUrl('admin/reports.php') ?>">
                <i class="fas fa-chart-line"></i> Laporan Keuangan
              </a>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Main Admin Content Area Right Column -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <!-- Render Flash Messages -->
        <?php foreach (getFlash() as $f): ?>
          <div class="alert alert-<?= $f['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endforeach; ?>
