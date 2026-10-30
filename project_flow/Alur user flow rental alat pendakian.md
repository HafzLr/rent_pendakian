[1. KUNJUNGAN PERTAMA / APLIKASI DIBUKA]
       │
       ├───► A. PENGGUNA TERDAFTAR (LOGIN)
       │         │
       │         ▼
       │    [Masuk ke Beranda (Akses Penuh)]
       │
       └───► B. PENGGUNA BUKAN MEMBER (GUEST)
                 │
                 ▼
            [Masuk ke Beranda dalam Mode Guest]
                 │
                 ▼
            [Jelajahi Katalog & Filter Kategori (Tenda, Carrier, dll)]
                 │
                 ▼
            [Pilih Produk & Pilih Varian/Warna]
                 │
                 ▼
            [Klik Tombol "Sewa Sekarang" / "Cek Stok"]
                 │
                 ▼
            🚨 [POP-UP INTERCEPT: "Wajib Login untuk Melanjutkan Sewa"]
                 │
                 ▼
            [Halaman Register / Login]
                 │
                 ├─► Masukkan Email, Password, Nama, No. WhatsApp
                 └─► Upload Dokumen Identitas: Foto KTP / SIM
                 │
                 ▼
            [Verifikasi Akun Berhasil]
                 │
                 ▼
[2. PROSES PENYEWAAN (CHECKOUT FLOW)]
       │
       ▼
[Halaman Form Detail Sewa]
  ├──► 1. Input Tanggal Sewa (Start Date s.d. End Date)
  ├──► 2. Input Jumlah Unit (Qty)
  └──► 3. Klik "Cek Ketersediaan Stok"
            │
            ├───► [Stok Bentrok / Habis] ──► Muncul Pesan "Stok tidak tersedia pada tanggal ini"
            │                                 & Saran Tanggal Lain
            │
            └───► [Stok Tersedia]
                    │
                    ▼
            [Kalkulasi Biaya Otomatis oleh Sistem]
            • Durasi Sewa = (End Date - Start Date) Hari
            • Subtotal = Durasi × Harga per Hari × Qty
            • Deposit / Jaminan Alat
                    │
                    ▼
            [Halaman Pembayaran]
            • Pilih Metode Pengambilan: [Ambil di Toko / Self-Pickup] atau [Kirim Kurir]
            • Pilih Skema Pembayaran:
                ├── Opsi A: [Uang Muka / DP 50%]
                └── Opsi B: [Pembayaran Lunas 100%]
            • Pilih Payment Gateway (Midtrans / Virtual Account / E-Wallet)
                    │
                    ▼
            [Proses Pembayaran Selesai]
                    │
                    ▼
[3. HALAMAN AKUN & MONITORING PESANAN]
       │
       ▼
[Tab "Sewa Saya" / Riwayat Transaksi]
  └── Track Status Pesanan secara Real-time:
      ├── Status 1: "Menunggu Verifikasi Admin" (Admin cek KTP & bukti bayar)
      ├── Status 2: "Sewa Disetujui / Siap Diambil" (Bisa diambil saat Start Date)
      ├── Status 3: "Sedang Disewa (Aktif)" (Barang ada di tangan penyewa)
      ├── Status 4: "Selesai" (Barang kembali utuh, deposit/KTP dikembalikan)
      └── Status 5: "Denda / Terlambat" (Jika terlambat dikembalikan / barang rusak)