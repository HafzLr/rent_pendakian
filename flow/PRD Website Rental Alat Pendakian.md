# PRD: Website Rental Alat Pendakian

Versi 1.0 · 29 Oktober 2026 · Disusun dari tiga sumber: `Design.md`, `Alur_user_flow_rental_alat_pendakian.md`, dan `rent_pendakian.sql` (v2)

## 1. Ringkasan

### 1.1 Latar belakang

Penyewaan alat pendakian (tenda, carrier, sleeping bag, dan sejenisnya) masih mengandalkan chat WhatsApp dan pencatatan manual. Akibatnya stok sering bentrok, bukti bayar tercecer, dan identitas penyewa sulit dilacak. Website ini memindahkan seluruh proses ke satu sistem: katalog, cek stok per tanggal, hitung biaya otomatis, pembayaran dengan verifikasi admin, dan pemantauan status sewa.

### 1.2 Tujuan produk

1. Penyewa dapat menyewa alat tanpa harus bertanya stok lewat chat.
2. Stok tidak pernah dobel-booking untuk tanggal yang sama.
3. Admin dapat memverifikasi identitas dan pembayaran dari satu dashboard.
4. Semua transaksi (sewa, deposit, denda) tercatat dan dapat ditelusuri.

### 1.3 Target pengguna

| Peran | Deskripsi | Kebutuhan utama |
| --- | --- | --- |
| Guest | Pengunjung belum login | Melihat katalog, filter kategori, melihat varian dan harga |
| Penyewa (`role = user`) | Pengguna terdaftar | Cek stok, checkout, upload bukti bayar, pantau status |
| Admin (`role = admin`) | Pemilik atau staf toko | Verifikasi KTP/SIM dan pembayaran, kelola produk dan stok, serah-terima barang, catat denda |

### 1.4 Metrik keberhasilan

| Metrik | Target awal |
| --- | --- |
| Pesanan yang bentrok stok | 0 |
| Waktu verifikasi admin (bukti bayar dan KTP) | Kurang dari 12 jam kerja |
| Pesanan berstatus `expired` karena tidak membayar | Kurang dari 20% |
| Halaman katalog termuat (LCP, 4G) | Kurang dari 2,5 detik |

Target ini adalah usulan awal, silakan sesuaikan dengan kondisi toko.

## 2. Keputusan Penyelarasan Antar Sumber

Tiga dokumen sumber tidak sepenuhnya sejalan. Aturan yang dipakai: **skema database (`rent_pendakian.sql` v2) adalah acuan utama untuk data dan aturan bisnis**, user flow menjadi acuan urutan layar, dan `Design.md` menjadi acuan tampilan.

| # | Topik | Di user flow | Di SQL v2 | Keputusan di PRD |
| --- | --- | --- | --- | --- |
| 1 | Metode pengambilan | Ambil di toko atau kirim kurir | Hanya ambil di toko (tidak ada kolom pengiriman) | **Hanya ambil di toko.** Kurir masuk fase lanjutan |
| 2 | Pembayaran | Midtrans, Virtual Account, e-wallet | Manual: `bank_transfer`, `qris`, `cash`, bukti upload, diverifikasi admin | **Verifikasi manual oleh admin.** Tidak ada payment gateway |
| 3 | Status "Denda/Terlambat" | Status ke-5 | Tidak ada status denda; ada kolom `penalty` dan `payment_type = 'penalty'` | Terlambat dan denda adalah **label turunan**, bukan status baru (lihat bagian 7) |
| 4 | Verifikasi akun | "Verifikasi akun berhasil" otomatis setelah register | `verification_status` default `pending`, diubah oleh admin | **Akun baru berstatus `pending`.** Admin memverifikasi KTP/SIM. Penyewa baru boleh melihat dan mengecek stok, tetapi tidak bisa checkout sebelum `verified` |
| 5 | Status pesanan | 5 status | 8 status (`pending_payment` sampai `expired`) | Memakai **8 status dari SQL**, dipetakan ke label di user flow |
| 6 | Tampilan | Template toko tanaman (Lukani) | Tidak relevan | Memakai sistem desain Lukani, **diadaptasi** untuk alat pendakian (bagian 9) |

Catatan nomor 4: ini keputusan produk yang paling berdampak. Kalau toko ingin penyewa bisa checkout langsung setelah register, aturan ini perlu diubah dan verifikasi dipindah ke tahap pickup.

## 3. Ruang Lingkup

### 3.1 Masuk rilis 1 (MVP)

- Register, login, upload KTP/SIM
- Katalog produk, filter kategori, detail produk dengan varian
- Cek ketersediaan stok per rentang tanggal
- Kalkulasi biaya otomatis (sewa, deposit)
- Checkout dengan skema DP atau lunas
- Pembayaran manual: upload bukti, verifikasi admin
- Halaman "Sewa Saya" dengan pelacakan status
- Dashboard admin: verifikasi user, verifikasi pembayaran, kelola katalog dan stok, serah-terima, pengembalian, denda

### 3.2 Di luar rilis 1

- Pengiriman kurir, payment gateway (Midtrans/VA/e-wallet)
- Wishlist, compare, blog, feed Instagram, multi-bahasa dan multi-mata uang (ada di template, tidak dipakai)
- Notifikasi WhatsApp otomatis (rilis 1 memakai tautan WhatsApp manual)
- Ulasan dan rating produk
- Aplikasi mobile native

## 4. User Flow

### 4.1 Alur 1: Kunjungan pertama

```
Buka website
 ├─ Sudah login  → Beranda (akses penuh)
 └─ Guest        → Beranda mode Guest
                    → jelajah katalog + filter kategori
                    → pilih produk + varian
                    → klik "Sewa Sekarang" / "Cek Stok"
                    → pop-up: "Wajib login untuk melanjutkan sewa"
                    → Register/Login
                       (email, password, nama, no. WhatsApp, upload KTP/SIM)
                    → akun dibuat, status verifikasi: pending
```

### 4.2 Alur 2: Proses penyewaan

```
Form Detail Sewa
 1. Pilih tanggal mulai dan tanggal selesai
 2. Pilih jumlah unit
 3. Klik "Cek Ketersediaan Stok"
     ├─ Bentrok/habis → pesan "Stok tidak tersedia pada tanggal ini" + saran tanggal lain
     └─ Tersedia → kalkulasi otomatis:
          Durasi    = tanggal selesai − tanggal mulai (hari)
          Subtotal  = durasi × harga per hari × qty
          Deposit   = deposit per unit × qty
 Halaman Pembayaran
   • Ambil di toko (satu-satunya metode)
   • Skema: DP 50% atau Lunas 100%
   • Metode: transfer bank, QRIS, atau tunai di toko
   • Upload bukti bayar (kecuali tunai)
 Pesanan masuk → "Menunggu Verifikasi Admin"
```

### 4.3 Alur 3: Monitoring pesanan

Tab "Sewa Saya" menampilkan status pesanan. Pemetaan label ke status database:

| Label di user flow | Status database | Keterangan |
| --- | --- | --- |
| (baru, belum ada di flow) Menunggu Pembayaran | `pending_payment` | Penyewa belum upload bukti, atau bukti ditolak |
| 1. Menunggu Verifikasi Admin | `pending_verification` | Bukti bayar sudah diupload |
| 2. Sewa Disetujui / Siap Diambil | `approved` | Dapat diambil pada tanggal mulai |
| 3. Sedang Disewa (Aktif) | `active` | Barang ada di tangan penyewa |
| 4. Selesai | `completed` | Barang kembali, deposit dikembalikan |
| 5. Denda / Terlambat | `active` + label turunan | Lewat `end_date` atau `penalty > 0` |
| (tambahan) Ditolak | `rejected` | Admin menolak pesanan |
| (tambahan) Dibatalkan | `cancelled` | Dibatalkan penyewa |
| (tambahan) Kedaluwarsa | `expired` | Melewati batas waktu bayar |

## 5. Kebutuhan Fungsional

Prioritas: **P0** wajib MVP, **P1** penting, **P2** nanti.

### 5.1 Autentikasi dan akun

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-01 | Register dengan nama, email unik, password, nomor WhatsApp (`user_number`) | P0 |
| FR-02 | Upload foto KTP/SIM saat register (`idcard_photo`); format JPG/PNG, maks 5 MB | P0 |
| FR-03 | Password disimpan sebagai hash (`password_hash`/bcrypt atau argon2), tidak pernah plain text | P0 |
| FR-04 | Login dan logout; sesi berbasis cookie aman (HttpOnly, SameSite) | P0 |
| FR-05 | Mode Guest: katalog dan detail produk dapat dilihat tanpa login | P0 |
| FR-06 | Pop-up "Wajib login" ketika guest menekan "Sewa Sekarang" atau "Cek Stok" | P0 |
| FR-07 | Halaman profil menampilkan status verifikasi akun beserta alasan penolakan bila ada | P1 |
| FR-08 | Penyewa yang ditolak dapat mengunggah ulang KTP/SIM | P1 |
| FR-09 | Reset password lewat email | P2 |

### 5.2 Katalog dan produk

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-10 | Beranda: hero, kategori unggulan, produk populer, cara menyewa, kontak toko | P0 |
| FR-11 | Daftar produk dengan filter kategori (`categories`), pencarian nama, urutan harga | P0 |
| FR-12 | Kartu produk: gambar, kategori, nama, harga per hari, deposit | P0 |
| FR-13 | Detail produk: gambar, deskripsi, harga per hari, deposit per unit, pilihan varian (warna/ukuran) | P0 |
| FR-14 | Penanda stok memakai teks dan warna ("Tersedia", "Stok terbatas", "Habis"), tidak mengandalkan warna saja | P1 |

### 5.3 Cek stok dan checkout

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-15 | Form sewa: tanggal mulai, tanggal selesai, qty, varian | P0 |
| FR-16 | Validasi: tanggal mulai tidak di masa lalu, tanggal selesai harus setelah tanggal mulai (sesuai constraint `end_date > start_date`) | P0 |
| FR-17 | Cek ketersediaan memakai query stok tersedia (lampiran A) | P0 |
| FR-18 | Bila tidak tersedia, tampilkan pesan dan saran tanggal terdekat yang tersedia | P0 |
| FR-19 | Kalkulasi otomatis: durasi, subtotal per item, total sewa, total deposit | P0 |
| FR-20 | Satu pesanan dapat berisi beberapa varian (keranjang sewa); satu varian hanya satu baris per pesanan | P0 |
| FR-21 | Checkout hanya untuk akun `verified`; akun `pending`/`rejected` melihat pesan penjelasan | P0 |
| FR-22 | Pilih skema pembayaran `down_payment` atau `full` | P0 |
| FR-23 | Harga per hari disalin ke `rental_items.price_per_day` saat transaksi, sehingga perubahan harga tidak memengaruhi pesanan lama | P0 |
| FR-24 | Saat pesanan dibuat, stok langsung ditahan (status `pending_payment`) dan batas waktu bayar dihitung dari `created_at` | P0 |

### 5.4 Pembayaran

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-25 | Tampilkan instruksi bayar: rekening bank, kode QRIS, atau info bayar tunai di toko | P0 |
| FR-26 | Upload bukti transfer/QRIS (`proof_image`); untuk tunai tidak wajib (NULL) | P0 |
| FR-27 | Setelah bukti diupload, pembayaran `pending` dan pesanan menjadi `pending_verification` | P0 |
| FR-28 | Penyewa dapat mengunggah ulang bukti bila ditolak, selama belum melewati batas waktu | P0 |
| FR-29 | Pelunasan (`settlement`) untuk skema DP dibayar sebelum atau saat pengambilan | P0 |
| FR-30 | Pembayaran denda (`penalty`) dicatat sebagai baris pembayaran terpisah | P1 |
| FR-31 | Pesanan `pending_payment` otomatis menjadi `expired` setelah batas waktu (job terjadwal) dan stok dilepas | P0 |

### 5.5 Sewa Saya (penyewa)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-32 | Daftar pesanan dengan badge status dan filter status | P0 |
| FR-33 | Detail pesanan: item, tanggal, total sewa, deposit, riwayat pembayaran, alasan penolakan, denda dan catatannya | P0 |
| FR-34 | Tombol batalkan pesanan selama status `pending_payment` atau `pending_verification` | P1 |
| FR-35 | Tombol hubungi toko via WhatsApp dengan pesan terisi otomatis berisi nomor pesanan | P1 |

### 5.6 Dashboard admin

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-36 | Antrean verifikasi user: lihat foto KTP/SIM, setujui (`verified`) atau tolak (`rejected`) dengan catatan | P0 |
| FR-37 | Antrean verifikasi pembayaran: lihat bukti, setujui (`success`, isi `paid_at`, `verified_by`) atau tolak (`failed`, wajib `rejection_reason`) | P0 |
| FR-38 | Setelah pembayaran DP/lunas disetujui, pesanan menjadi `approved` | P0 |
| FR-39 | Tandai pesanan `active` saat barang diserahkan (hanya pada atau setelah `start_date`) | P0 |
| FR-40 | Catat pengembalian: isi `return_date`, hitung keterlambatan, input `penalty` dan `penalty_notes` bila ada | P0 |
| FR-41 | Tandai `completed` setelah barang kembali, pelunasan dan denda selesai, lalu catat pengembalian deposit | P0 |
| FR-42 | CRUD kategori, produk, varian, dan `total_stock` | P0 |
| FR-43 | Daftar semua pesanan dengan filter status dan tanggal | P0 |
| FR-44 | Pembayaran tunai dicatat admin langsung (tanpa bukti), status `success` | P1 |
| FR-45 | Kalender ketersediaan per varian | P2 |
| FR-46 | Laporan pendapatan, denda, dan produk terlaris | P2 |

## 6. Aturan Bisnis

1. **Durasi** = `end_date − start_date` dalam hari. Tanggal selesai adalah hari pengembalian, sehingga sewa 10 sampai 12 dihitung 2 hari. Ini konsisten dengan query stok (`start_date < :end_date AND end_date > :start_date`) yang memperbolehkan pesanan lain mulai tepat di hari pengembalian.
2. **Subtotal item** = durasi × `price_per_day` × `quantity`. **Total sewa** = jumlah semua subtotal. **Total deposit** = jumlah (`deposit` × `quantity`) tiap item.
3. **Stok tersedia** = `total_stock` dikurangi jumlah qty pada pesanan berstatus `pending_payment`, `pending_verification`, `approved`, `active` yang tanggalnya beririsan.
4. **Skema DP**: nominal DP adalah 50% dari total tagihan; sisanya dilunasi sebagai `settlement`. Persentase disimpan sebagai konfigurasi aplikasi, bukan hardcode.
5. **Batas waktu bayar**: dihitung dari `rentals.created_at`. Usulan awal 24 jam, dapat dikonfigurasi.
6. **Keterlambatan**: pesanan `active` dengan tanggal hari ini melewati `end_date` ditandai "Terlambat". Besar denda ditentukan admin saat pengembalian (tarif per hari terlambat dan/atau biaya kerusakan) dan dicatat di `penalty`.
7. **Deposit** dikembalikan saat `completed`, dikurangi denda bila perlu.
8. **Harga historis**: perubahan harga produk tidak mengubah pesanan yang sudah dibuat.
9. **Penghapusan data**: produk, varian, kategori, dan user yang sudah punya transaksi tidak dapat dihapus (relasi `ON DELETE RESTRICT`); gunakan penonaktifan (lihat bagian 12).

## 7. Status Pesanan

```
pending_payment ──upload bukti──► pending_verification ──admin setuju──► approved ──serah barang──► active ──kembali + lunas──► completed
      │   ▲                              │                                                              │
      │   └──── bukti ditolak ───────────┤                                                              └─ lewat end_date: label "Terlambat"
      ├──► expired    (lewat batas waktu bayar)                                                          └─ penalty > 0: label "Denda"
      ├──► cancelled  (dibatalkan penyewa)
      └──► rejected   (ditolak admin; juga dari pending_verification)
```

Catatan: bukti bayar yang ditolak mengembalikan pesanan ke `pending_payment` dengan alasan penolakan terlihat. Pesanan `rejected` dipakai ketika admin menolak seluruh pesanan, misalnya karena identitas tidak valid. Status `expired`, `cancelled`, dan `rejected` melepas stok.

## 8. Model Data

Skema mengikuti `rent_pendakian.sql` v2 (MySQL 8.0.16+ atau MariaDB 10.2+, engine InnoDB, utf8mb4). Tujuh entitas, tujuh relasi satu-ke-banyak.

### 8.1 Relasi

```
users 1──N rentals 1──N rental_items N──1 product_variants N──1 products N──1 categories
  │            └──────N payments
  └──N payments.verified_by (admin yang memverifikasi)
```

### 8.2 Tabel dan penggunaannya

| Tabel | Kolom penting | Dipakai oleh |
| --- | --- | --- |
| `users` | `user_id`, `name`, `email` (unik), `password`, `user_number`, `role` (`user`/`admin`), `idcard_photo`, `verification_status` (`pending`/`verified`/`rejected`) | FR-01 sampai FR-09, FR-36 |
| `categories` | `category_id`, `category_name` (unik) | FR-11, FR-42 |
| `products` | `product_id`, `category_id`, `product_name`, `description`, `price_per_day`, `deposit` (per unit), `image` | FR-10 sampai FR-14, FR-42 |
| `product_variants` | `variant_id`, `product_id`, `variant_name` (unik per produk), `total_stock` | FR-13, FR-17, FR-42 |
| `rentals` | `user_id`, `start_date`, `end_date`, `return_date`, `payment_scheme` (`down_payment`/`full`), `total_rental`, `total_deposit`, `penalty`, `penalty_notes`, `status`, `created_at` | FR-19 sampai FR-24, FR-32 sampai FR-43 |
| `rental_items` | `rental_id`, `variant_id`, `quantity`, `price_per_day` (salinan), `subtotal`. Hapus pesanan ikut menghapus item (`CASCADE`) | FR-20, FR-23 |
| `payments` | `rental_id`, `payment_type` (`down_payment`/`settlement`/`penalty`), `payment_amount`, `payment_method` (`bank_transfer`/`qris`/`cash`), `payment_status` (`pending`/`success`/`failed`/`expired`), `paid_at`, `proof_image`, `rejection_reason`, `verified_by` | FR-25 sampai FR-31, FR-37, FR-44 |

### 8.3 Constraint yang harus dihormati aplikasi

- `end_date > start_date`; semua nominal tidak negatif; `payment_amount > 0`; `quantity > 0`.
- Pembayaran `success` wajib punya `paid_at`; pembayaran `failed` wajib punya `rejection_reason`.
- `verified_by` diisi oleh aplikasi dengan `user_id` admin yang sedang login, dan harus berperan `admin`.
- Satu varian hanya boleh muncul sekali per pesanan (`uq_rental_item_identity`).
- Total di `rentals` adalah hasil hitung yang disimpan; aplikasi wajib menghitungnya di server, bukan menerima nilai dari browser.

### 8.4 Keamanan transaksi stok

Pembuatan pesanan harus berjalan dalam satu transaksi database: kunci baris `product_variants` yang terkait (`SELECT ... FOR UPDATE`), hitung ulang stok tersedia, baru insert `rentals` dan `rental_items`. Tanpa ini, dua penyewa yang checkout bersamaan bisa sama-sama lolos cek stok.

### 8.5 Celah skema yang perlu diputuskan

| # | Celah | Usulan |
| --- | --- | --- |
| 1 | Tidak ada kolom alasan penolakan KTP/SIM | Aktifkan `ALTER TABLE users ADD COLUMN verification_notes` yang sudah disediakan (dikomentari) di SQL |
| 2 | Pengembalian deposit tidak punya jejak data | Tambah `deposit_refunded` dan `deposit_refunded_at` di `rentals`, atau catat di `penalty_notes` pada rilis 1 |
| 3 | Tidak ada penonaktifan produk/varian (karena `RESTRICT`) | Tambah `is_active TINYINT(1)` di `products` dan `product_variants` |
| 4 | Tidak ada waktu pembuatan di `users` dan `payments` | Tambah `created_at` untuk audit dan urutan antrean verifikasi |
| 5 | Pesanan `active` yang terlambat tidak lagi menahan stok, karena query stok memakai irisan tanggal dan `end_date` sudah lewat | Pada query stok, anggap pesanan `active` tanpa `return_date` tetap menahan stok sampai barang kembali |
| 6 | Tidak ada tabel konfigurasi (persentase DP, batas waktu bayar, tarif denda) | Simpan di file konfigurasi atau tabel `settings` |
| 7 | Satu gambar per produk | Cukup untuk MVP; galeri multi-gambar masuk fase berikutnya |

## 9. Spesifikasi Desain

Mengikuti `Design.md` (sistem desain Lukani oleh HasTech, berbasis Bootstrap 5). Perhatikan bahwa dokumen itu menandai banyak nilai sebagai inferensi **\[I\]** atau usulan **\[P\]**, bukan hasil pengukuran template. Warna adalah satu-satunya bagian yang terverifikasi.

### 9.1 Prinsip desain yang dipertahankan

1. Tenang dan natural: hijau lembut di atas latar blush, tidak ada warna jenuh.
2. Foto produk sebagai fokus; antarmuka tidak berebut perhatian.
3. Pola e-commerce yang familiar (menu kategori, keranjang off-canvas, grid dan list).
4. Ruang lega, teks pendek.
5. Satu warna aksi utama: Forest.
6. Wadah gambar dengan rasio tetap agar foto alat dengan berbagai ukuran tetap rapi.

### 9.2 Warna

| Peran | Token | Hex |
| --- | --- | --- |
| Latar halaman | `--lk-blush` | `#F3EAEB` |
| Permukaan (kartu, header, input) | `--lk-white` | `#FFFFFF` |
| Teks utama dan footer | `--lk-ink` | `#12190C` |
| Teks sekunder | `--lk-grey` | `#63635E` |
| Aksi utama (tombol, link, fokus) | `--lk-forest` | `#3C521B` |
| Aksen sekunder (outline, ikon) | `--lk-moss` | `#7B825B` |
| Aksen dekoratif | `--lk-leaf` | `#95A264` |
| Border | `--lk-sage` | `#C5C4B4` |

Status: hover utama `#334617`, tekan `#2A3913`, tint terpilih `#D8DCD1`. Semantik (usulan): sukses `#3F7D3A`, peringatan atau stok terbatas `#B7791F`, bahaya atau stok habis `#B3382C`, info `#3B6E8F`.

Aturan kontras dari `Design.md` yang wajib diikuti:

- Tombol terisi selalu Forest dengan teks putih (rasio 8,70, lulus AAA).
- Leaf hanya dekoratif; jangan dipakai sebagai teks di atas putih atau teks putih di atas Leaf (rasio 2,75, gagal). Di atas Leaf gunakan teks Ink.
- Moss dengan teks putih (4,05) hanya untuk outline, ikon, dan teks besar.
- Teks abu-abu tidak dipakai di atas Sage.
- Proporsi kasar 60% permukaan blush/putih, 30% teks, 10% aksen hijau.

### 9.3 Tipografi (usulan, belum terverifikasi)

- Judul: Playfair Display 600 (cadangan Georgia, serif).
- Isi dan UI: Jost 400/500/600 (cadangan sans-serif sistem).
- Skala: Display 56/36px, H1 44/32, H2 36/28, H3 28/24, H4 22/20, H5 18/17, isi 16px, kecil 14px, caption 12px (desktop/mobile).
- Tombol 14px, 600, huruf kapital, letter-spacing 0,06em. Harga 18 sampai 20px, berat 600, warna Forest, angka tabular.
- Nama produk dipotong 2 baris. Lebar baca maksimum sekitar 680px.

### 9.4 Tata letak dan grid

- Bootstrap 5, 12 kolom, gutter 24px. Container maksimum 1140px (xl) dan 1320px (xxl).
- Titik henti: 576, 768, 992, 1200, 1400px.
- Ritme vertikal antar seksi: 96px desktop, 64px tablet, 48px mobile.
- Grid produk: 3 sampai 4 kolom desktop, 2 sampai 3 tablet, 2 kolom mobile (atau 1 pada layar sangat sempit). Detail produk: 2 kolom 50/50, menumpuk di tablet ke bawah.
- Radius: tombol dan input 4px, kartu dan gambar 8px, badge pill. Bayangan bertingkat 1 sampai 3 dengan warna Ink, bukan hitam murni.

### 9.5 Komponen dan adaptasinya

| Komponen di template | Dipakai untuk |
| --- | --- |
| Top bar | Nomor WhatsApp toko, jam buka, tautan login/akun (tanpa pemilih bahasa/mata uang) |
| Header sticky | Logo, pencarian, ikon akun, ikon "Keranjang Sewa" dengan badge jumlah |
| Menu kategori | Filter kategori alat (Tenda, Carrier, Sleeping Bag, dll.) |
| Keranjang off-canvas (360 sampai 420px) | Ringkasan item sewa, tanggal, subtotal, tombol "Checkout" |
| Hero slider | Banner promo dan musim pendakian (autoplay 5 sampai 6 detik, jeda saat hover/fokus) |
| Kartu produk | Gambar 1:1 atau 4:5, kategori, nama, harga per hari, label deposit, badge stok. Aksi wishlist, compare, quick view **dihapus** |
| Sidebar filter | Kategori dan rentang harga |
| Detail produk | Galeri, judul, harga, **pemilih varian**, **date picker sewa**, qty stepper, tombol "Cek Ketersediaan" dan "Sewa Sekarang" |
| Tab detail | Deskripsi, Spesifikasi, Syarat Sewa |
| Formulir | Tinggi input 48px, border Sage, fokus Forest dengan ring 30%, pesan error 13px |
| Tabel | Sewa Saya, riwayat pembayaran, antrean admin |
| Badge status | Pill 12px dengan teks dan warna (lihat 9.6) |
| Footer | Latar Ink, teks Blush, 4 kolom: tentang toko, informasi, akun, kontak |
| Tombol kembali ke atas | Lingkaran Forest, muncul setelah scroll sekitar 300px |

Komponen template yang tidak dipakai di rilis 1: popup newsletter, feed Instagram, blog, wishlist, compare.

### 9.6 Warna badge status pesanan

| Status | Label | Warna |
| --- | --- | --- |
| `pending_payment` | Menunggu Pembayaran | Peringatan |
| `pending_verification` | Menunggu Verifikasi Admin | Info |
| `approved` | Siap Diambil | Sukses |
| `active` | Sedang Disewa | Forest |
| `active` + lewat `end_date` | Terlambat | Bahaya |
| `completed` | Selesai | Abu-abu (Stone Grey) |
| `rejected` / `cancelled` / `expired` | Ditolak / Dibatalkan / Kedaluwarsa | Bahaya (outline) atau abu-abu |

Semua badge wajib memuat teks, bukan hanya warna.

### 9.7 Gerak dan aksesibilitas

- Transisi warna dan bayangan 200 sampai 300ms; slide 600 sampai 800ms; off-canvas 300ms `cubic-bezier(.4,0,.2,1)`. Kartu naik 4px saat hover.
- Hormati `prefers-reduced-motion`.
- Fokus terlihat di semua elemen interaktif (ring Forest). Tombol hanya-ikon wajib punya `aria-label`. Off-canvas dan modal menjebak fokus, menutup dengan Esc, dan mengembalikan fokus ke pemicu.
- Area sentuh minimal 44px.

### 9.8 Gambar

Panduan gambar di template ditulis untuk tanaman, jadi disesuaikan: foto alat di latar netral (blush atau sage) untuk produk, foto lifestyle di alam untuk banner. Rasio produk 1:1 atau 4:5, hero 16:7 (aman dipotong 4:5 di mobile). Ukuran produk 800×800, hero 1920×820, format WebP dengan cadangan JPEG, lazy-load di bawah fold. Teks alternatif memuat jenis alat, varian, dan konteks.

## 10. Daftar Halaman

| Area | Halaman | Akses |
| --- | --- | --- |
| Publik | Beranda, Katalog, Detail Produk, Cara Menyewa/FAQ, Kontak, 404 | Guest dan user |
| Akun | Register, Login, Profil dan Status Verifikasi | Guest (register/login), user |
| Penyewaan | Form Detail Sewa, Pembayaran, Upload Bukti, Sewa Saya, Detail Pesanan | User terverifikasi |
| Admin | Dashboard, Verifikasi User, Verifikasi Pembayaran, Daftar Pesanan, Serah-Terima dan Pengembalian, Kategori, Produk dan Varian | Admin |

## 11. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
| --- | --- |
| Keamanan | Hash password, proteksi CSRF dan XSS, prepared statement (anti SQL injection), kontrol akses berbasis `role` di server |
| Privasi dokumen | Foto KTP/SIM dan bukti bayar disimpan di luar folder publik dan hanya dapat dibuka oleh pemilik dan admin lewat URL bertanda tangan atau endpoint terautentikasi |
| Upload | Validasi tipe dan ukuran file, ganti nama file acak, hapus metadata EXIF |
| Integritas | Semua perhitungan biaya dan stok dilakukan di server; pembuatan pesanan memakai transaksi (bagian 8.4) |
| Performa | Halaman katalog LCP di bawah 2,5 detik pada 4G; gambar lazy-load; indeks `idx_rentals_availability` dipakai query stok |
| Responsif | Mobile-first; diuji di 375, 576, 768, 992, 1200, 1400px |
| Aksesibilitas | Sesuai bagian 9.7 dan target WCAG 2.x AA |
| Browser | Chrome, Firefox, Safari, Edge versi terbaru. Dukungan IE11 pada template tidak diperlukan |
| Audit | Setiap verifikasi mencatat admin dan waktu (`verified_by`, `paid_at`) |
| Zona waktu | Tampilan memakai WIB; penyimpanan konsisten satu zona waktu |
| Pencadangan | Backup database harian |

## 12. Rencana Rilis

| Fase | Isi |
| --- | --- |
| Fase 1: Fondasi | Skema database, autentikasi, upload dokumen, tema dan komponen dasar sesuai Design.md |
| Fase 2: Katalog | Beranda, katalog, filter, detail produk dengan varian, CRUD admin untuk produk |
| Fase 3: Transaksi | Cek stok, kalkulasi, checkout, pembayaran manual, verifikasi admin, job kedaluwarsa |
| Fase 4: Operasional | Serah-terima, pengembalian, denda, deposit, Sewa Saya lengkap |
| Fase 5: Penyempurnaan | Kalender stok, laporan, notifikasi WhatsApp, reset password, uji beban dan aksesibilitas |

Fase lanjutan: pengiriman kurir, payment gateway, ulasan, galeri multi-gambar.

## 13. Risiko

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Dobel-booking saat checkout bersamaan | Stok bentrok | Transaksi dan penguncian baris (8.4) |
| Verifikasi admin lambat | Penyewa batal atau pesanan kedaluwarsa | Antrean terurut, target waktu verifikasi, pengingat ke admin |
| Kebocoran foto KTP/SIM | Risiko hukum dan reputasi | Penyimpanan privat, akses terbatas, kebijakan retensi |
| Barang terlambat dikembalikan tetapi stok terlihat tersedia | Pesanan berikutnya tidak bisa dipenuhi | Perbaikan query stok (celah nomor 5) |
| Nilai desain belum terverifikasi | Tampilan akhir berbeda dari template | Lakukan checklist DevTools di Design.md bagian 15 sebelum implementasi |

## 14. Pertanyaan Terbuka

1. Apakah penyewa yang baru register boleh checkout sebelum KTP/SIM diverifikasi? (PRD ini: tidak.)
2. DP 50% dihitung dari total sewa saja, atau total sewa ditambah deposit? (PRD ini: total tagihan, termasuk deposit.)
3. Berapa batas waktu pembayaran, dan apakah berbeda untuk skema DP dan lunas?
4. Bagaimana rumus denda: tarif per hari terlambat, persentase dari harga sewa, atau ditentukan admin?
5. Apakah deposit berupa uang, atau penyewa menitipkan KTP/SIM asli (user flow menyebut "deposit/KTP dikembalikan")?
6. Berapa lama pesanan boleh dibuat sebelum tanggal mulai (jendela pemesanan maksimum)?
7. Apakah ada batas durasi sewa minimum atau maksimum, dan jam operasional pengambilan?
8. Nama toko, logo, dan rekening/QRIS untuk instruksi pembayaran.

## Lampiran A: Query Stok Tersedia

Dari `rent_pendakian.sql`, dipakai oleh FR-17 (ganti nilai `:named` dengan parameter):

```sql
SELECT v.total_stock - COALESCE((
         SELECT SUM(ri.quantity)
         FROM rental_items ri
         JOIN rentals r ON r.rental_id = ri.rental_id
         WHERE ri.variant_id = v.variant_id
           AND r.status IN ('pending_payment','pending_verification','approved','active')
           AND r.start_date < :end_date
           AND r.end_date   > :start_date
       ), 0) AS available_stock
FROM product_variants v
WHERE v.variant_id = :variant_id;
```

Pesanan boleh dibuat bila `available_stock` lebih besar atau sama dengan qty yang diminta, dihitung ulang di dalam transaksi pembuatan pesanan.
