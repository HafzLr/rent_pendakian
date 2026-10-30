<?php
// pages/how-it-works.php - Step-by-step guide and FAQ
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

$pageTitle = 'Cara Menyewa & Panduan - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="text-center max-w-700 mx-auto mb-5">
    <div class="badge bg-light text-forest border px-3 py-2 mb-2 text-uppercase fw-bold" style="letter-spacing: 0.05em;">Panduan Lengkap</div>
    <h1 class="font-heading display-6 mb-3">Cara Mudah Menyewa di RentGear</h1>
    <p class="text-muted">Proses penyewaan perlengkapan pendakian kini serba transparan, otomatis tanpa bentrok tanggal, dan identitas terverifikasi aman.</p>
  </div>

  <!-- 5-Step Process Cards -->
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="lk-card p-4 h-100 text-center">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 20px; font-weight: bold;">
          1
        </div>
        <h5 class="font-heading fs-5 mb-2">Daftar & Verifikasi KTP</h5>
        <p class="small text-muted mb-0">Buat akun penyewa dan unggah foto KTP atau SIM Anda. Tim admin kami memvalidasi identitas demi keamanan jaminan sewa.</p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="lk-card p-4 h-100 text-center">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 20px; font-weight: bold;">
          2
        </div>
        <h5 class="font-heading fs-5 mb-2">Pilih Alat & Cek Tanggal</h5>
        <p class="small text-muted mb-0">Tentukan tanggal mulai dan selesai pendakian. Sistem secara otomatis mengecek ketersediaan fisik stok agar tidak ada bentrok booking.</p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="lk-card p-4 h-100 text-center">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 20px; font-weight: bold;">
          3
        </div>
        <h5 class="font-heading fs-5 mb-2">Pilih Skema Bayar (DP / Lunas)</h5>
        <p class="small text-muted mb-0">Pilih opsi DP 50% untuk mengunci alat atau langsung bayar lunas 100%. Lakukan transfer bank, scan QRIS, atau bayar tunai di toko.</p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="lk-card p-4 h-100 text-center">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 20px; font-weight: bold;">
          4
        </div>
        <h5 class="font-heading fs-5 mb-2">Ambil di Toko & Cek Kondisi</h5>
        <p class="small text-muted mb-0">Ambil peralatan di toko kami pada tanggal mulai sewa. Anda dapat memeriksa fungsi tenda, kelengkapan frame, dan kebersihan secara langsung.</p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="lk-card p-4 h-100 text-center">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: var(--lk-primary-tint); color: var(--lk-forest); font-size: 20px; font-weight: bold;">
          5
        </div>
        <h5 class="font-heading fs-5 mb-2">Pengembalian & Refund Deposit</h5>
        <p class="small text-muted mb-0">Kembalikan peralatan sesuai jadwal. Uang jaminan deposit Anda akan langsung dikembalikan 100% setelah pengecekan kelengkapan selesai.</p>
      </div>
    </div>
  </div>

  <!-- FAQ Accordion -->
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="lk-card p-4 p-md-5">
        <h3 class="font-heading fs-3 mb-4 text-center">Pertanyaan yang Sering Diajukan (FAQ)</h3>

        <div class="accordion" id="faqAccordion">
          <div class="accordion-item border-0 mb-3 rounded" style="border: 1px solid var(--lk-sage) !important;">
            <h2 class="accordion-header" id="headingOne">
              <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true">
                Apakah saya bisa menyewa tanpa melakukan verifikasi KTP/SIM?
              </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body small text-muted">
                Untuk keamanan unit peralatan berharga tinggi, seluruh penyewa wajib mengunggah foto identitas asli (KTP atau SIM) yang masih berlaku saat mendaftar. Tim admin akan memverifikasi dalam waktu kurang dari 12 jam kerja sebelum Anda dapat melakukan checkout pesanan.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 rounded" style="border: 1px solid var(--lk-sage) !important;">
            <h2 class="accordion-header" id="headingTwo">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                Bagaimana perhitungan durasi sewa di RentGear?
              </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body small text-muted">
                Durasi sewa dihitung berdasarkan selisih hari antara Tanggal Selesai dan Tanggal Mulai (Durasi = Tanggal Selesai - Tanggal Mulai). Misalnya sewa tanggal 10 sampai tanggal 12 dihitung 2 hari sewa. Hari pengembalian dapat dipesan oleh penyewa berikutnya.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 rounded" style="border: 1px solid var(--lk-sage) !important;">
            <h2 class="accordion-header" id="headingThree">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                Kapan uang jaminan (deposit) dikembalikan?
              </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body small text-muted">
                Uang jaminan deposit dikembalikan penuh saat barang diserahkan kembali di toko fisik kami dalam keadaan utuh dan lengkap. Apabila terdapat bagian alat yang rusak atau hilang, biaya perbaikan/penggantian akan dipotong dari uang deposit tersebut.
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 mb-3 rounded" style="border: 1px solid var(--lk-sage) !important;">
            <h2 class="accordion-header" id="headingFour">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                Bagaimana jika saya terlambat mengembalikan alat?
              </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body small text-muted">
                Keterlambatan pengembalian akan dikenakan tarif denda harian per unit yang terlambat. Mohon segera hubungi kami melalui WhatsApp jika menghadapi kendala cuaca atau keterlambatan di jalur pendakian.
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-4 pt-3 border-top">
          <p class="small text-muted mb-2">Ada pertanyaan lain yang belum terjawab?</p>
          <a href="<?= generateWhatsAppUrl(STORE_PHONE, 'Halo Admin RentGear, saya ingin bertanya lebih lanjut mengenai sewa alat.') ?>" target="_blank" class="btn btn-outline-forest btn-sm">
            <i class="fab fa-whatsapp me-1 text-success"></i> Tanya Admin via WhatsApp
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
