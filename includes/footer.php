<?php
// includes/footer.php
?>
  <!-- Footer -->
  <footer class="lk-footer">
    <div class="container">
      <div class="row g-4 mb-4">
        <!-- Col 1: About -->
        <div class="col-lg-4 col-md-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-mountain fa-2x" style="color: var(--lk-leaf);"></i>
            <span class="fs-4 fw-bold font-heading" style="color: var(--lk-white);">Rent<span style="color: var(--lk-leaf);">Gear</span></span>
          </div>
          <p class="small text-secondary mb-3" style="color: #A3A69C !important;">
            Platform persewaan alat camping dan pendakian gunung modern dengan sistem pengecekan stok otomatis, tanpa risiko dobel booking. Perlengkapan bersih, terawat, dan siap menemani ekspedisi Anda.
          </p>
          <div class="d-flex gap-2">
            <a href="#" class="lk-action-btn" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #FFF;"><i class="fab fa-instagram"></i></a>
            <a href="#" class="lk-action-btn" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #FFF;"><i class="fab fa-tiktok"></i></a>
            <a href="#" class="lk-action-btn" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #FFF;"><i class="fab fa-youtube"></i></a>
          </div>
        </div>

        <!-- Col 2: Navigasi Cepat -->
        <div class="col-lg-2 col-md-6">
          <h5>Tautan Cepat</h5>
          <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
            <li><a href="<?= baseUrl() ?>">Beranda</a></li>
            <li><a href="<?= baseUrl('pages/catalog.php') ?>">Katalog Produk</a></li>
            <li><a href="<?= baseUrl('pages/how-it-works.php') ?>">Alur & Cara Sewa</a></li>
            <li><a href="<?= baseUrl('pages/contact.php') ?>">Kontak Toko</a></li>
            <li><a href="<?= baseUrl('pages/my-rentals.php') ?>">Status Sewa Saya</a></li>
          </ul>
        </div>

        <!-- Col 3: Kategori Populer -->
        <div class="col-lg-3 col-md-6">
          <h5>Kategori Alat</h5>
          <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
            <li><a href="<?= baseUrl('pages/catalog.php?category=Tenda') ?>">Tenda Dome & Ultralight</a></li>
            <li><a href="<?= baseUrl('pages/catalog.php?category=Carrier') ?>">Carrier & Backpack 45-70L</a></li>
            <li><a href="<?= baseUrl('pages/catalog.php?category=Sleeping%20Bag') ?>">Sleeping Bag Bulu Angsa & Dacron</a></li>
            <li><a href="<?= baseUrl('pages/catalog.php?category=Masak') ?>">Kompor & Cooking Set</a></li>
            <li><a href="<?= baseUrl('pages/catalog.php?category=Penerangan') ?>">Headlamp & Lampu Tenda</a></li>
          </ul>
        </div>

        <!-- Col 4: Toko & Kontak -->
        <div class="col-lg-3 col-md-6">
          <h5>Informasi Toko</h5>
          <ul class="list-unstyled small d-flex flex-column gap-2 text-secondary" style="color: #A3A69C !important;">
            <li><i class="fas fa-map-marker-alt me-2 text-warning"></i> <?= STORE_ADDRESS ?></li>
            <li><i class="far fa-clock me-2 text-info"></i> <?= STORE_HOURS ?></li>
            <li><i class="fab fa-whatsapp me-2 text-success"></i> +<?= STORE_PHONE ?></li>
            <li><i class="far fa-envelope me-2 text-white"></i> <?= STORE_EMAIL ?></li>
          </ul>
          <div class="mt-3">
            <a href="<?= generateWhatsAppUrl(STORE_PHONE, 'Halo Admin RentGear, saya ingin konsultasi kebutuhan alat mendaki.') ?>" target="_blank" class="btn btn-outline-light btn-sm w-100 py-2">
              <i class="fab fa-whatsapp text-success me-1"></i> Chat WhatsApp Toko
            </a>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="lk-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div>
          &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. Seluruh hak cipta dilindungi undang-undang.
        </div>
        <div class="d-flex align-items-center gap-3">
          <span><i class="fas fa-money-bill-wave me-1"></i> Transfer Bank</span>
          <span><i class="fas fa-qrcode me-1"></i> QRIS</span>
          <span><i class="fas fa-hand-holding-usd me-1"></i> Bayar di Toko</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Back to Top Button -->
  <button id="backToTopBtn" class="lk-back-to-top" aria-label="Kembali ke atas">
    <i class="fas fa-arrow-up"></i>
  </button>

  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Main Script -->
  <script src="<?= baseUrl('assets/js/main.js') ?>"></script>
</body>
</html>
