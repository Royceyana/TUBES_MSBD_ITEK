# Aplikasi Kasir & Manajemen Kafe — Kafe Kaleng

Sistem manajemen basis data untuk membantu pengelolaan operasional kafe, meliputi transaksi penjualan (Point of Sales), pengelolaan bahan baku, resep menu, serta laporan penjualan dan stok. Project ini dikembangkan sebagai Tugas Besar mata kuliah Manajemen Sistem Basis Data.

## Deskripsi

Kafe Kaleng merupakan aplikasi berbasis web yang dikembangkan untuk membantu kafe mitra mengelola kegiatan operasional secara lebih terstruktur dan terintegrasi.

Aplikasi ini dirancang untuk mendukung pencatatan transaksi penjualan, pengelolaan data menu dan bahan baku, pemantauan persediaan, serta penyajian informasi penjualan dan keuangan. Sistem memanfaatkan database PostgreSQL untuk mengelola data dan menerapkan trigger serta function database dalam mendukung otomatisasi perhitungan transaksi dan pengelolaan stok.

## Fitur Utama

- **Autentikasi dan Manajemen User** — pengelolaan akun pengguna berdasarkan peran dan hak akses.
- **Master Data** — pengelolaan kategori menu, menu/produk, dan bahan baku (ingredients).
- **Resep/BOM (Bill of Materials)** — pengaturan hubungan antara menu dan bahan baku beserta jumlah kebutuhan setiap bahan.
- **Transaksi Penjualan (POS)** — pengelolaan pesanan, keranjang belanja, diskon, metode pembayaran, dan pencetakan struk.
- **Manajemen Stok** — pencatatan stok masuk, penyesuaian stok melalui stok opname, serta pemantauan ketersediaan bahan baku.
- **Otomatisasi Database** — pemanfaatan function dan trigger PostgreSQL untuk membantu perhitungan total transaksi dan perubahan persediaan.
- **Laporan dan Dashboard** — penyajian informasi penjualan, produk terlaris, laba-rugi sederhana, serta kondisi persediaan bahan baku.

## Peran dan Hak Akses

| Role | Tanggung Jawab |
|---|---|
| **Admin** | Mengelola data kategori, menu, bahan baku, resep, pengguna, dan persediaan. |
| **Kasir** | Mengelola transaksi penjualan, pembayaran, dan pencetakan struk. |
| **Waitress (Pelayan)** | Membuat dan mengelola pesanan pelanggan serta memantau status pesanan. |
| **Dapur** | Memantau antrean pesanan dan memperbarui status pengerjaan pesanan. |
| **Owner** | Memantau ringkasan penjualan, produk terlaris, laporan, dan kondisi stok. |

## Teknologi yang Digunakan

- **Backend:** Laravel (PHP)
- **Database:** PostgreSQL
- **Frontend:** Blade Template, HTML, CSS, dan JavaScript
- **Asset Management:** Vite
- **Database Automation:** PostgreSQL Function dan Trigger

## Struktur Database

Entitas utama yang digunakan dalam sistem meliputi:

- `users` — menyimpan data pengguna dan peran.
- `categories` — menyimpan kategori menu.
- `menus` — menyimpan data produk atau menu.
- `ingredients` — menyimpan data bahan baku dan jumlah persediaan.
- `menu_ingredients` — menghubungkan menu dengan bahan baku sebagai resep atau BOM.
- `transactions` — menyimpan data transaksi penjualan.
- `transaction_details` — menyimpan rincian menu dalam transaksi.
- `stock_movements` — mencatat perubahan persediaan bahan baku.

### Alur Pengelolaan Data

1. Kasir membuat pesanan melalui halaman POS.
2. Sistem memeriksa ketersediaan menu berdasarkan kebutuhan bahan baku yang tersimpan pada `menu_ingredients`.
3. Data transaksi dan rincian pesanan disimpan ke tabel `transactions` dan `transaction_details`.
4. Setelah pesanan mencapai status `ready`, trigger database memproses pengurangan stok bahan baku dan mencatat pergerakannya pada `stock_movements`.
5. Admin mencatat stok masuk ketika terdapat pembelian bahan baku.
6. Database memperbarui jumlah persediaan berdasarkan transaksi stok masuk dan penyesuaian stok.
7. Data transaksi dan persediaan digunakan untuk menyajikan informasi penjualan, produk terlaris, dan laporan stok.

## Instalasi dan Konfigurasi

### 1. Clone Repository

```bash
git clone <url-repository>
cd <nama-folder>
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`, kemudian sesuaikan konfigurasi database PostgreSQL.

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database
DB_USERNAME=postgres
DB_PASSWORD=password_database
```

Buat application key:

```bash
php artisan key:generate
```

### 4. Migrasi dan Seeding Database

```bash
php artisan migrate --seed
```

Pastikan PostgreSQL sudah berjalan dan database telah dibuat sebelum menjalankan migrasi.

### 5. Jalankan Aplikasi

Jalankan Vite pada terminal pertama:

```bash
npm run dev
```

Jalankan Laravel pada terminal kedua:

```bash
php artisan serve
```

Aplikasi dapat diakses melalui:

```text
http://127.0.0.1:8000
```

## Struktur Folder

```text
app/
 ├── Models/                 # Model aplikasi
 ├── Http/
 │   ├── Controllers/        # Controller setiap modul
 │   └── Middleware/         # Middleware hak akses
 └── Services/               # Logika layanan aplikasi

database/
 ├── migrations/             # Struktur tabel
 ├── seeders/                # Data awal pengguna
 └── sql/                    # SQL schema, function, dan trigger

resources/
 └── views/                  # Tampilan Blade

routes/
 └── web.php                 # Routing aplikasi
```

## Tim Pengembang

- Kelompok: 

### Anggota

- Royceyana Yosephine Silaban
- Deby Yasmin Nabila
- Cellin Angela Simanungkalit
- Novita Barti Br Barus
- Debora Elsa Naomi Tampubolon

## Lisensi

Project ini dikembangkan untuk keperluan akademik sebagai Tugas Besar mata kuliah Manajemen Sistem Basis Data.
