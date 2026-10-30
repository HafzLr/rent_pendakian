<!-- includes/guest_modal.php: Intercept Modal for Guests -->
<div class="modal fade" id="guestInterceptModal" tabindex="-1" aria-labelledby="guestModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: var(--lk-radius-md);">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body text-center px-4 pt-0 pb-4">
        <div class="mb-3">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background-color: var(--lk-primary-tint); color: var(--lk-forest);">
            <i class="fas fa-user-lock fa-2x"></i>
          </div>
        </div>
        <h4 class="font-heading mb-2" id="guestModalTitle">Wajib Login untuk Melanjutkan</h4>
        <p class="text-muted intercept-message mb-4" style="font-size: 14px;">
          Untuk melakukan pengecekan ketersediaan stok, memesan, dan menyewa alat pendakian, Anda perlu masuk ke akun RentGear Anda.
        </p>
        <div class="d-grid gap-2">
          <a href="<?= baseUrl('auth/login.php') ?>" class="btn btn-forest py-2">
            <i class="fas fa-sign-in-alt me-1"></i> Masuk Sekarang
          </a>
          <a href="<?= baseUrl('auth/register.php') ?>" class="btn btn-outline-forest py-2">
            <i class="fas fa-user-plus me-1"></i> Daftar Akun Baru
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
