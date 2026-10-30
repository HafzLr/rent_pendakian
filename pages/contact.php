<?php
// pages/contact.php - Store Contact & Location Details
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

$pageTitle = 'Kontak & Lokasi Toko - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Kontak & Lokasi</li>
      </ol>
    </nav>
    <h1 class="font-heading fs-2 mb-1">Hubungi & Kunjungi Toko RentGear</h1>
    <p class="text-muted small">Kami siap melayani kebutuhan peralatan pendakian dan ekspedisi alam bebas Anda setiap hari.</p>
  </div>

  <div class="row g-4 mb-5">
    <!-- Store Contact Details Left -->
    <div class="col-lg-5">
      <div class="lk-card p-4 p-md-5 h-100">
        <h4 class="font-heading fs-4 mb-4">Informasi Toko Fisik</h4>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle d-flex align-items-center justify-content-center p-3" style="background-color: var(--lk-primary-tint); color: var(--lk-forest); min-width: 48px; height: 48px;">
            <i class="fas fa-map-marked-alt fa-lg"></i>
          </div>
          <div>
            <div class="fw-bold mb-1">Alamat Penjemputan / Toko:</div>
            <div class="text-muted small"><?= STORE_ADDRESS ?></div>
            <div class="text-muted small mt-1">(Dekat basecamp pendakian, mudah diakses kendaraan roda 2 dan roda 4)</div>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle d-flex align-items-center justify-content-center p-3" style="background-color: var(--lk-primary-tint); color: var(--lk-forest); min-width: 48px; height: 48px;">
            <i class="far fa-clock fa-lg"></i>
          </div>
          <div>
            <div class="fw-bold mb-1">Jam Operasional Toko:</div>
            <div class="text-muted small"><?= STORE_HOURS ?></div>
            <div class="text-muted small mt-1">Buka setiap hari termasuk hari libur nasional & akhir pekan</div>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle d-flex align-items-center justify-content-center p-3" style="background-color: var(--lk-primary-tint); color: var(--lk-forest); min-width: 48px; height: 48px;">
            <i class="fab fa-whatsapp fa-lg text-success"></i>
          </div>
          <div>
            <div class="fw-bold mb-1">WhatsApp Customer Service:</div>
            <div class="text-muted small">+<?= STORE_PHONE ?></div>
            <div class="mt-2">
              <a href="<?= generateWhatsAppUrl(STORE_PHONE, 'Halo Admin RentGear, saya ingin konsultasi sewa alat.') ?>" target="_blank" class="btn btn-forest btn-sm">
                <i class="fab fa-whatsapp me-1"></i> Buka Chat WhatsApp
              </a>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center p-3" style="background-color: var(--lk-primary-tint); color: var(--lk-forest); min-width: 48px; height: 48px;">
            <i class="far fa-envelope fa-lg"></i>
          </div>
          <div>
            <div class="fw-bold mb-1">Email Layanan:</div>
            <div class="text-muted small"><?= STORE_EMAIL ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Map & Pickup Instructions Right -->
    <div class="col-lg-7">
      <div class="lk-card p-4 p-md-5 h-100">
        <h4 class="font-heading fs-4 mb-3">Panduan Pengambilan Mandiri di Toko</h4>
        <p class="small text-muted mb-4">
          Semua transaksi sewa diambil mandiri di toko RentGear. Hal ini bertujuan agar penyewa dapat memastikan seluruh kelengkapan alat (kondisi tenda, frame, pasak, resleting carrier, dan fungsi kompor) berfungsi prima sebelum dibawa mendaki.
        </p>

        <!-- Simulated Interactive Map Card -->
        <div class="p-4 rounded border text-center mb-4" style="background: linear-gradient(135deg, #E5E9E0, #F3EAEB);">
          <i class="fas fa-map-location-dot fa-3x text-forest mb-3"></i>
          <h5 class="fw-bold fs-6">Peta Lokasi Outlet RentGear</h5>
          <p class="small text-muted mb-3"><?= STORE_ADDRESS ?></p>
          <a href="https://maps.google.com/?q=<?= urlencode(STORE_ADDRESS) ?>" target="_blank" class="btn btn-outline-forest btn-sm">
            <i class="fas fa-external-link-alt me-1"></i> Buka Petunjuk Arah di Google Maps
          </a>
        </div>

        <div class="alert alert-light border small">
          <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-circle text-forest me-1"></i> Catatan Saat Penjemputan:</h6>
          <ul class="mb-0 ps-3 text-muted">
            <li>Harap tunjukkan kartu identitas KTP/SIM yang terdaftar pada akun Anda.</li>
            <li>Tunjukkan nomor pesanan sewa (contoh: #10) kepada kasir toko.</li>
            <li>Bagi yang memilih skema DP 50%, sisa pelunasan dapat dibayar tunai atau QRIS saat pengambilan barang.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
