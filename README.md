# Aplikasi Kasir & Manajemen Kafe

Sistem manajemen basis data untuk kafe mitra — mencakup transaksi penjualan (POS), manajemen stok bahan baku, dan laporan penjualan & keuangan. Dibangun sebagai Tugas Besar mata kuliah Manajemen Sistem Basis Data.

## Deskripsi

Aplikasi ini dikembangkan untuk membantu kafe mitra mengelola operasional harian yang sebelumnya dilakukan secara manual, meliputi pencatatan transaksi, pemantauan stok bahan baku, hingga penyusunan laporan penjualan dan keuangan secara otomatis dan terintegrasi.

## Fitur Utama

- **Autentikasi & Manajemen User** — login berbasis role dengan middleware
- **Master Data** — kategori menu, menu/produk, dan bahan baku (ingredients)
- **Resep/BOM (Bill of Material)** — relasi antara menu dan bahan baku beserta takaran, sehingga stok berkurang otomatis setiap ada penjualan
- **Transaksi Penjualan (POS)** — keranjang belanja, metode pembayaran, diskon, cetak struk
- **Manajemen Stok** — stok masuk (pembelian bahan baku), stok keluar otomatis dari penjualan, stok opname, notifikasi stok menipis
- **Laporan** — laporan penjualan harian/bulanan, laba-rugi sederhana, produk terlaris, laporan stok
- **Dashboard Owner** — ringkasan visual (grafik penjualan, produk terlaris, status stok)

## Peran & Hak Akses

| Role | Akses |
|---|---|
| **Admin** | Kelola master data, input pembelian stok, kelola user, lihat semua laporan |
| **Kasir** | Buat transaksi penjualan, lihat stok tersedia, cetak struk |
| **Owner** | Lihat dashboard & laporan penjualan/keuangan (read-only) |

## Tech Stack

- **Backend**: Laravel (PHP)
- **Database**: Postgress
- **Frontend**: Blade
- **Laporan**: Laravel Excel (export), Chart.js (dashboard)

## Struktur Database

Entitas utama: `users`, `categories`, `menus`, `ingredients`, `menu_ingredients`, `transactions`, `transaction_details`, `stock_movements`.

Alur singkat:
1. Kasir membuat transaksi → sistem mengecek kebutuhan bahan baku via `menu_ingredients`
2. Transaksi tersimpan di `transactions` & `transaction_details`
3. Stok bahan baku otomatis berkurang, tercatat di `stock_movements` (type: `out`)
4. Admin mencatat pembelian bahan baku → `stock_movements` (type: `in`) → `ingredients.stock_qty` bertambah
5. Laporan penjualan & laba-rugi dihasilkan dari agregasi `transactions` dan `stock_movements`

## Instalasi & Setup

```bash
# Clone repository
git clone <url-repository>
cd <nama-folder>

# Install dependencies
composer install
npm install

# Konfigurasi environment
cp .env.example .env
php artisan key:generate

# Sesuaikan koneksi database di .env
# DB_DATABASE=nama_database
# DB_USERNAME=root
# DB_PASSWORD=

# Migrasi & seeding database
php artisan migrate --seed

# Jalankan build asset
npm run dev

# Jalankan server lokal
php artisan serve
```

## Struktur Folder (ringkas)

```
app/
 ├── Models/            # Eloquent models (User, Menu, Ingredient, Transaction, dst)
 ├── Http/Controllers/  # Controller per modul (Auth, POS, Stok, Laporan)
 └── Http/Middleware/   # Middleware pengecekan role (admin, kasir, owner)
database/
 ├── migrations/        # Struktur tabel
 └── seeders/           # Data awal (kategori, menu default, user demo)
resources/views/        # Blade views per role
routes/web.php          # Routing aplikasi
```

## Tim Pengembang

- Kelompok: _isi nama kelompok_
- Anggota: _isi nama & NIM_

## Lisensi

Project ini dibuat untuk keperluan akademik (Tugas Besar Manajemen Sistem Basis Data).
