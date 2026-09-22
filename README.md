# Warung Kaure POS & Management System

**Warung Kaure POS & Management System** adalah aplikasi Point of Sale (Kasir) dan Manajemen Kedai berbasis web yang dibangun khusus untuk operasional kedai kopi / makanan & minuman. Aplikasi ini dirancang dengan pendekatan **Mobile-First**, minimal modern bertema coklat hangat khas kedai kopi, dan dapat langsung dijalankan secara lokal menggunakan XAMPP.

---

## 1. Persyaratan Sistem (Requirements)

- **OS**: Windows / Linux / macOS
- **Web Server**: Apache (XAMPP) dengan modul `mod_rewrite` aktif
- **PHP**: PHP 8.1 / 8.2+ dengan ekstensi `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`
- **Database**: MySQL 5.7+ / MariaDB 10.4+ (InnoDB, UTF-8 Unicode)
- **Browser**: Chrome, Microsoft Edge, Firefox, Safari (Mendukung resolusi HP mulai 360px)

---

## 2. Struktur Folder Proyek

```text
kasir/
├── app/
│   ├── Controllers/         # Controller aplikasi (POS, Produk, Transaksi, Laporan, dll)
│   ├── Core/                # Database Singleton, MVC Router, Base Controller, Validator
│   ├── Helpers/             # Helper fungsi: auth, rupiah, tanggal, csrf, response, url
│   ├── Middleware/          # AuthMiddleware & RoleMiddleware (OWNER & CASHIER)
│   ├── Models/              # Representasi entitas data
│   ├── Repositories/        # Data Access Layer menggunakan PDO Prepared Statements
│   └── Services/            # Business Logic Layer (SaleService, Inventory, Payroll, dll)
├── config/
│   ├── app.php              # Konfigurasi aplikasi, timezone, format mata uang, upload
│   └── database.php         # Konfigurasi koneksi database MySQL PDO
├── database/
│   ├── schema.sql           # Skema lengkap 15 tabel database MySQL InnoDB
│   └── seed.sql             # Data awal: akun default, produk, kategori, resep BOM, stok
├── public/
│   ├── assets/
│   │   ├── css/app.css      # Design system coklat Warung Kaure & Dark Mode Bootstrap 5
│   │   ├── js/app.js        # Global client script, dark mode toggle, DataTables
│   │   └── js/pos.js        # Logika interaktif POS, keranjang, quick cash, AJAX checkout
│   ├── uploads/             # Direktori penyimpanan foto produk & bukti pengeluaran
│   ├── .htaccess            # URL rewriting ke front-controller
│   └── index.php            # Front-controller entry point aplikasi
├── routes/
│   └── web.php              # Pemetaan seluruh rute web & middleware
├── views/
│   ├── auth/                # Halaman login
│   ├── components/          # Komponen UI: Navbar, Sidebar desktop & Offcanvas mobile
│   ├── dashboard/           # Dashboard Owner dengan Chart.js & KPI metrics
│   ├── pos/                 # Halaman POS kasir mobile-first & cetak struk thermal
│   ├── products/            # Manajemen menu & produk
│   ├── categories/          # Manajemen kategori
│   ├── ingredients/         # Manajemen bahan baku
│   ├── recipes/             # Manajemen Resep / BOM (Bill of Materials)
│   ├── inventory/           # Stok persediaan, Stock In, Stock Out, Reset Stok ke 0
│   ├── transactions/        # Riwayat transaksi, detail, Void & Refund
│   ├── reports/             # Laporan penjualan, pembayaran (cash vs cashless), HPP & laba
│   ├── users/               # Manajemen akun pegawai & tarif jam kerja
│   ├── attendance/          # Absensi pegawai (8 jam normal & lembur Rp5.000/jam)
│   ├── payroll/             # Penggajian dua mingguan (14 hari)
│   ├── expenses/            # Biaya operasional kedai
│   ├── audit/               # Log audit keamanan & aktivitas sistem
│   ├── settings/            # Pengaturan kedai & format struk
│   └── layouts/             # Master layout, pos layout, dan 404
├── .htaccess                # Root URL rewrite ke subdirektori public/
├── index.php                # Root fallback index forwarder
└── README.md
```

---

## 3. Cara Instalasi di XAMPP

1. **Salin Folder Proyek**:
   Letakkan folder proyek ini di dalam direktori `htdocs` XAMPP, contoh:
   ```text
   C:\xampp\htdocs\kasir
   ```

2. **Jalankan XAMPP Control Panel**:
   - Nyalakan service **Apache** (Start)
   - Nyalakan service **MySQL** (Start)

3. **Import Database**:
   - Buka browser dan kunjungi `http://localhost/phpmyadmin/`
   - Buat database baru bernama `warung_kaure`
   - Klik tab **Import**, pilih file `database/schema.sql`, lalu klik **Import**
   - Setelah selesai, pilih file `database/seed.sql`, lalu klik **Import**
   - *Alternatif via Command Prompt/Terminal*:
     ```bash
     C:\xampp\mysql\bin\mysql.exe -u root -e "source C:/xampp/htdocs/kasir/database/schema.sql; source C:/xampp/htdocs/kasir/database/seed.sql;"
     ```

4. **Konfigurasi Database**:
   Pastikan konfigurasi di `config/database.php` sesuai dengan environment lokal Anda:
   ```php
   'host'     => '127.0.0.1',
   'port'     => '3306',
   'database' => 'warung_kaure',
   'username' => 'root',
   'password' => '', // kosongkan jika tanpa password
   ```

---

## 4. URL Akses Aplikasi

Aplikasi dapat dibuka langsung melalui URL:
- **Akses Langsung**: `http://localhost/kasir/`
- **Akses Public**: `http://localhost/kasir/public/`

---

## 5. Akun Bawaan (Default Login)

Sistem dilengkapi dua role pengguna dengan proteksi otorisasi backend:

### A. Akun Owner (Pemilik Kedai)
- **Username**: `owner`
- **Password**: `owner123`
- **Role**: `OWNER`
- **Hak Akses**: Akses penuh ke Dashboard, POS, Produk, Resep BOM, Kelola Stok, Void & Refund Transaksi, Laporan Keuangan, Penggajian 14 Hari, Absensi, Audit Log, dan Pengaturan.
- *Fitur Otomatis*: Saat Owner pertama kali login setiap hari, sistem otomatis mencatatkan absensi masuk (auto clock-in) tanpa duplikasi.

### B. Akun Kasir (Staff)
- **Username**: `kasir`
- **Password**: `kasir123`
- **Role**: `CASHIER`
- **Hak Akses**: Terbatas hanya untuk POS Penjualan, Pembayaran (Cash, QRIS, Transfer, E-Wallet), Cetak Struk, Riwayat Transaksi Miliknya Sendiri, serta Absensi Masuk/Pulang Mandiri. Kasir tidak dapat mengakses URL owner dan akan otomatis dialihkan kembali ke POS jika mencoba membukanya secara manual.

---

## 6. Fitur Utama & Logika Bisnis

1. **Mobile-First POS Kasir**:
   - Grid produk 2 kolom yang optimal untuk layar smartphone (mulai 360px).
   - Filter cepat kategori (horizontal scroll) & pencarian produk real-time.
   - Sticky bar keranjang di bagian bawah & Bottom Offcanvas Cart.
   - Modal pembayaran cepat dengan nominal tunai preset (Rp10.000, Rp20.000, Rp50.000, Rp100.000, Uang Pas) serta kalkulasi kembalian otomatis.
   - Dukungan pembayaran: Cash, QRIS, Transfer Bank, dan E-Wallet (GoPay, OVO, DANA, ShopeePay, Lainnya).
   - Fitur **Hold Order** (simpan sementara) dan **Resume Order**.

2. **Dua Model Stok (Direct vs Recipe / BOM)**:
   - **DIRECT**: Mengurangi stok produk jadi langsung (contoh: Air Mineral, Snack kemasan).
   - **RECIPE (BOM)**: Setiap menu terjual otomatis mengurangi bahan baku resepnya (contoh: 1 porsi Es Kopi Susu otomatis memotong 18g Kopi, 120ml Susu, 20ml Gula Aren, 1 Cup 16oz, dan 1 Sedotan).

3. **Weighted Average Cost (WAC)**:
   - Saat stok bahan baku baru masuk (*Stock In*), harga modal rata-rata dihitung otomatis menggunakan formula WAC:
     $$\text{New Average Cost} = \frac{(\text{Stok Lama} \times \text{Harga Rata-rata Lama}) + (\text{Stok Masuk} \times \text{Harga Masuk})}{\text{Stok Lama} + \text{Stok Masuk}}$$

4. **Keamanan Transaksi & Concurrency**:
   - Proses checkout menggunakan transaksi database (`$pdo->beginTransaction()`) dan row locking (`SELECT ... FOR UPDATE`) untuk mencegah *overselling* saat ada transaksi bersamaan.
   - Reversal stok otomatis saat transaksi di-**VOID** atau di-**REFUND**.

5. **Struk Thermal Multi-Ukuran**:
   - Mendukung format cetak thermal printer 58mm, 80mm, serta browser print menggunakan CSS `@media print`.
   - Cetak ulang struk secara otomatis menampilkan label `*** REPRINT ***`.

6. **Penggajian Dua Mingguan (14 Hari)**:
   - Jam kerja normal 8 jam/hari (480 menit).
   - Jam lembur dihitung otomatis untuk kelebihan jam kerja dengan tarif bawaan Rp5.000/jam.
   - Penyimpanan *rate snapshot* agar riwayat slip gaji lama tidak berubah bila tarif pegawai diubah di masa depan.

7. **Laba Kotor & Estimasi Laba Bersih**:
   - $\text{Laba Kotor} = \text{Net Sales} - \text{HPP}$
   - $\text{Estimasi Laba Bersih} = \text{Laba Kotor} - \text{Beban Operasional} - \text{Beban Gaji}$

8. **Audit Trail**:
   - Setiap aktivitas penting (login, logout, penjualan, void, refund, koreksi stok, payroll) tercatat rapi di tabel `audit_logs` lengkap dengan user, IP, User Agent, dan snapshot data JSON (password tidak pernah dicatat).

9. **Dark Mode**:
   - Mendukung mode gelap/terang Bootstrap 5 dengan toggle di navbar dan preferensi tersimpan di `localStorage`. Saat mencetak struk, tampilan tetap hitam-putih bersih.

---

## 7. Catatan Printer Struk

- Saat membuka halaman struk, tekan tombol **Cetak Struk** atau gunakan shortcut `Ctrl + P`.
- Pada jendela print browser:
  - Pilih printer thermal yang Anda gunakan.
  - Atur **Margins** menjadi *None* atau *Minimum*.
  - Hilangkan centang pada opsi *Headers and footers*.
  - Ukuran lebar kertas dapat diatur di menu **Pengaturan** (Pilih 58 mm, 80 mm, atau Auto).

---

## 8. Panduan Troubleshooting

- **Halaman 404 saat membuka menu**:
  Pastikan modul `rewrite_module` aktif pada Apache. Di file `C:\xampp\apache\conf\httpd.conf`, pastikan baris `LoadModule rewrite_module modules/mod_rewrite.so` tidak diawali tanda `#`.
- **Gagal Terhubung ke Database**:
  Pastikan MySQL di XAMPP Control Panel berstatus hijau (Running) pada port 3306. Cek kredensial database di `config/database.php`.
- **Foto Produk Tidak Muncul**:
  Pastikan folder `public/uploads/products/` memiliki izin baca dan tulis (*writeable*).
