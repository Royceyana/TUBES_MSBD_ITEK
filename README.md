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

Aplikasi ini berjalan di atas database PostgreSQL `kafe_kaleng` (trigger, view, function, dan prosedur
tersimpan ada di database). Cara paling aman dan sama persis dengan yang dipakai tim adalah **me-restore file
dump** (`dump-kafe_kaleng-*.sql`), bukan membuat tabel dari nol.

### 0. Prasyarat

| Perangkat | Versi | Cek |
|---|---|---|
| PHP | 8.3 atau lebih baru, ekstensi `pdo_pgsql` dan `pgsql` aktif | `php -v` dan `php -m` |
| Composer | 2.x | `composer -V` |
| Node.js | LTS terbaru (20.19+ atau 22.12+) | `node -v` |
| PostgreSQL | 16 ke atas (dump dibuat dari 18.6) | `psql --version` |

Di Windows, ekstensi diaktifkan di `php.ini` dengan menghapus tanda `;` pada baris
`extension=pdo_pgsql` dan `extension=pgsql`, lalu buka terminal baru. Perintah `php -m` harus menampilkan
keduanya. Bila tidak, aplikasi akan error `could not find driver`.

### 1. Clone repository dan install dependensi

```bash
git clone <url-repository>
cd <nama-folder>
composer install
npm install
```

### 2. Restore database dari dump

Dump berisi `DROP DATABASE`, `CREATE DATABASE kafe_kaleng`, struktur, trigger, dan seluruh data, jadi jalankan
dengan `psql` yang terhubung ke database `postgres` (bukan ke `kafe_kaleng`). Jangan memakai Query Tool pgAdmin
atau menu Restore pgAdmin: file ini berformat SQL biasa dan memuat perintah `\connect`.

**Windows (PowerShell)**. Sesuaikan versi PostgreSQL dan lokasi file:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -h 127.0.0.1 -p 5432 -d postgres -f "C:\lokasi\dump-kafe_kaleng-202611202311.sql"
```

Pada restore pertama muncul `ERROR: database "kafe_kaleng" does not exist` (dari baris `DROP DATABASE`). Itu normal.
Bila `psql` Anda lebih lama dari 18, mungkin muncul juga `invalid command \restrict` atau
`unrecognized configuration parameter "transaction_timeout"`. Keduanya tidak memengaruhi hasil restore.

**Linux / macOS**. Dump memakai locale Windows (`English_Indonesia.1252`) yang tidak ada di sistem ini, jadi
buat databasenya manual lalu restore tanpa baris pembuatan database:

```bash
psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE kafe_kaleng ENCODING 'UTF8' TEMPLATE template0;"
sed -e '/^DROP DATABASE/d' -e '/^CREATE DATABASE/d' -e '/^ALTER DATABASE/d' -e '/^\\connect/d' \
    dump-kafe_kaleng-202611202311.sql | psql -U postgres -h 127.0.0.1 -d kafe_kaleng
```

**Cek hasil restore.** Jumlah baris harus sama dengan angka di kanan:

```bash
psql -U postgres -d kafe_kaleng -c "select (select count(*) from users) users, (select count(*) from menus) menus, (select count(*) from ingredients) ingredients, (select count(*) from transactions) transactions;"
#  users | menus | ingredients | transactions
#      6 |    67 |          66 |          108
```

Untuk mengulang dari awal, jalankan lagi perintah restore (database lama dihapus dan dibuat ulang). Tutup dulu pgAdmin,
`php artisan serve`, dan program lain yang sedang terhubung ke `kafe_kaleng`, kalau tidak `DROP DATABASE` gagal
dengan pesan `database "kafe_kaleng" is being accessed by other users`.

### 3. Konfigurasi environment

Salin `.env.example` menjadi `.env` (Windows: `copy .env.example .env`), lalu isi bagian database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kafe_kaleng
DB_USERNAME=postgres
DB_PASSWORD=<kata sandi user postgres Anda>
DB_SET_ROLE=false
```

Biarkan `DB_SET_ROLE=false`: dump tidak berisi role `kafe_web_*`, jadi `true` membuat setiap halaman error
`role "kafe_web_..." does not exist`. Lalu:

```bash
php artisan key:generate
php artisan config:clear
```

### 4. Migrasi (tanpa seeding)

```bash
php artisan migrate
```

Hasil yang benar adalah `INFO  Nothing to migrate.` karena dump sudah memuat tabel `migrations` yang lengkap.
**Jangan** menjalankan `--seed` pada database hasil restore: seeder stok (`InitialStockSeeder`) menambah
pergerakan stok ke data yang sudah ada. `--seed` hanya untuk database kosong.

Agar foto menu yang diunggah admin tampil:

```bash
php artisan storage:link
```

### 5. Jalankan aplikasi

Buka dua terminal di folder proyek.

```bash
# Terminal 1: aset (CSS/JS). Alternatif sekali jalan: npm run build
npm run dev
```

```bash
# Terminal 2: server Laravel
php artisan serve
```

Buka `http://127.0.0.1:8000`. Akun dari dump (kata sandi semuanya `kaleng123`):

| Role | Email |
|---|---|
| Admin | `admin@kafekaleng.test` |
| Kasir | `kasir@kafekaleng.test` |
| Dapur | `dapur@kafekaleng.test` |
| Pelayan | `waitress@kafekaleng.test` |
| Owner | `owner@kafekaleng.test` |

### 6. Menjalankan test (opsional)

```bash
php artisan test
```

Test berjalan di database `kafe_kaleng` yang sama (lihat `phpunit.xml`). Setiap test dibungkus transaksi lalu
dibatalkan, jadi data tidak berubah, tetapi nomor urut id (sequence) ikut maju. Ambil cadangan dulu bila data
tersebut penting: `pg_dump -U postgres -d kafe_kaleng -f cadangan.sql`.


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
