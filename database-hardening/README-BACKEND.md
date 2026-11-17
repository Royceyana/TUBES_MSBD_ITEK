# Backend Kafe Kaleng — perubahan database, model, dan role (Royce)

## Menjalankan
1. Salin `.env.example` ke `.env`, isi `DB_PASSWORD`, lalu buat database `kafe_kaleng` di PostgreSQL.
   Trigger stok dan total hanya ada di PostgreSQL, jadi `DB_CONNECTION` harus `pgsql`.
2. Pilih salah satu:
   - **Database kosong:** `php artisan migrate --seed` membuat semua tabel, view, trigger, dan
     5 akun contoh (admin, kasir, dapur, owner, waitress; kata sandi sama dengan dump).
     Data menu dan bahan tidak ikut; restore dari `kafe_kaleng.sql` bila perlu.
   - **Sudah restore `kafe_kaleng.sql`:** `php artisan migrate` melewati pembuatan tabel,
     hanya menambah kolom Laravel di `users` dan menerapkan perubahan di bawah.
3. Tanpa Laravel: `psql --single-transaction -d kafe_kaleng -f database/sql/2026_11_06_waitress_served_debit_discount.sql`

## Perubahan skema
| Hal | Perubahan |
|---|---|
| Role | `users.role` menerima `waitress` (selain admin, kasir, owner, dapur) |
| Status pesanan | `transactions.order_status`: waiting → preparing → ready → **served** |
| Metode bayar | `transactions.payment_method`: cash, qris, **debit** |
| Diskon | kolom baru `subtotal`, `discount_type` (none/percent/amount), `discount_value`, `discount_amount`; `total = subtotal - discount_amount` |

Aturan di database (trigger):
- `subtotal`, `discount_amount`, dan `total` dihitung otomatis; jangan diisi dari aplikasi.
- Diskon persen maksimal 100, nominal tidak pernah melebihi subtotal, dan tidak bisa diubah setelah `completed`.
- `served` hanya boleh setelah `ready` (stok sudah terpotong), dan tidak bisa mundur.
- Pesanan `served` tidak bisa dibatalkan; pesanan `completed` + `ready` juga tidak (aturan lama).

## Role di rute
Middleware `role` terdaftar di `bootstrap/app.php`:
```php
Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->group(/* ... */);
Route::middleware(['auth', 'role:waitress,kasir'])->group(/* salah satu role */);
```
Akun `inactive` dikeluarkan dari sesi; role yang tidak cocok mendapat 403.
`User::homePath()` memberi halaman awal tiap role. Path dapur (`/dapur/antrian`) dan
waitress (`/waitress/pesanan`) adalah usulan; samakan dengan rute yang dibuat Debora dan Novita.

## Model
`User`, `Category`, `Ingredient`, `Menu`, `MenuIngredient`, `StockMovement`, `Transaction`,
`TransactionDetail` di `app/Models`. Catatan pemakaian:
- Stok hanya berubah lewat `StockMovement` (trigger); `stock_qty` tidak ada di `$fillable`.
- Dapur: sebelum mengubah `order_status`, jalankan `SET LOCAL app.user_id = <id>` agar
  pemotongan stok tercatat atas nama user.
- `Menu::withAvailability()` menambah `porsi_tersedia` dan `is_available` dari view.
- `Transaction::forKitchen()`, `readyToServe()`, dan `unpaid()` untuk antrian dapur, daftar antar, dan kasir.

## Keamanan dan integritas database (laporan III.5 - III.7)
Migrasi `2026_11_17_*` melengkapi database (SQL-nya ada di `database/sql/2026_11_17_*.sql`, idempotent):

| Migrasi | Isi |
|---|---|
| `000001_add_integrity_constraints` | NOT NULL, CHECK, UNIQUE, FK `RESTRICT`, indeks, `COMMENT ON` (kamus data) |
| `000002_add_views_functions_procedures` | View laporan baru, fungsi `fn_sales_report` dkk, prosedur `sp_create_order`, `sp_pay_order`, `sp_cancel_order`, `sp_set_order_status`, `sp_restock`, `sp_stock_opname` |
| `000003_add_protection_and_audit` | Tabel `audit_logs` + trigger pengaman (transaksi tidak bisa dihapus, stok hanya lewat `stock_movements`, riwayat append-only, minimal satu admin aktif) |
| `000005_prepare_set_role_support` | Menyiapkan mode SET ROLE: fungsi trigger lintas tabel berjalan sebagai pemilik, `audit_logs.db_role` mencatat role aktif. Tidak membuat role. |

**Role dan hak akses database TIDAK dibuat oleh Laravel.** Itu langkah manual pemilik database memakai
`database/sql/2026_11_17_roles.sql`: role `kafe_app`, `kafe_report`, `kafe_auditor`, group role `kafe_role_*`, dan akun login per
peran (`owner_kafe`, `admin_kafe`, `staff_kasir`, `staff_chef`, `staff_waitress`).

### Cara menjalankan
```bash
# 1. Struktur + data (koneksi pgsql_admin = akun pemilik database, mis. postgres)
php artisan migrate:fresh --seed --database=pgsql_admin     # database kosong
php artisan migrate --database=pgsql_admin                  # database hasil restore kafe_kaleng.sql

# 2. Role dan hak akses (MANUAL, sebagai postgres; ulangi setelah migrate:fresh)
psql -U postgres -d kafe_kaleng -f database/sql/2026_11_17_roles.sql

# 3. Kata sandi tiap role (MANUAL): salin database/sql/2026_11_17_roles_passwords.sql.example,
#    ganti kata sandinya, jalankan sebagai postgres. Jangan commit kata sandi asli.
```
Isi `.env`: `DB_ADMIN_USERNAME/DB_ADMIN_PASSWORD` (akun pemilik, untuk migrasi/seeder). Setelah langkah 2-3 selesai, ganti runtime aplikasi:
```
DB_USERNAME=kafe_app
DB_PASSWORD=<kata sandi kafe_app yang diatur manual>
```
Selama `DB_USERNAME` masih `postgres`, aplikasi tetap jalan, tetapi tanpa pembatasan hak akses. Laravel hanya memakai satu akun
koneksi (`kafe_app`); akun per peran dipakai untuk akses langsung lewat SQL client, bukan dipilih otomatis oleh Laravel.

### Mode SET ROLE (opsional)
Dengan `DB_SET_ROLE=true`, middleware `SetDatabaseUser` menjalankan `SET ROLE kafe_web_<peran>` selama request pengguna yang login
(lalu `RESET ROLE`), jadi database memakai hak peran itu: admin ke `kafe_web_admin`, kasir ke `kafe_web_kasir`, dapur ke `kafe_web_dapur`,
waitress ke `kafe_web_waitress`, owner ke `kafe_web_owner`. Tamu (login, beranda) tetap memakai `kafe_app`.

Syarat (semuanya manual, Laravel tidak membuat role):
1. `php artisan migrate --database=pgsql_admin` (menjalankan migrasi `000005`).
2. Jalankan `database/sql/2026_11_17_roles.sql` sebagai postgres (bagian 9 membuat role `kafe_web_*` dan memberi `kafe_app` hak `SET ROLE`).
3. `.env`: `DB_USERNAME=kafe_app` dan `DB_SET_ROLE=true`.

Hak tiap role dihitung dari controller: kasir hanya INSERT transaksi/rincian dan UPDATE kolom status, metode bayar, diskon; dapur dan waitress
hanya UPDATE `order_status`; owner baca-saja; admin mengelola master data, akun, dan stok tetapi tidak dapat mengubah atau menghapus
transaksi. Bila fitur baru butuh tabel/kolom lain, tambahkan hak di bagian 9 `roles.sql` (kalau lupa, errornya `permission denied`).
Catatan: SET ROLE melindungi dari bug/salah pakai di kode, bukan dari SQL injection (penyerang yang bisa menjalankan SQL bebas dapat `RESET ROLE`).

**Penting:** hapus berkas lama `database/migrations/2026_11_17_000004_create_security_roles.php` bila masih ada di proyekmu. Migrasi itu
membuat role otomatis dan sudah digantikan langkah manual di atas (tercatat "Ran" di tabel `migrations` itu wajar, tidak apa-apa).

### Aturan baru yang perlu diketahui tim
- Transaksi, rincian, dan riwayat stok tidak bisa dihapus; batalkan pesanan (`cancelled`) atau nonaktifkan data.
- `ingredients.stock_qty` tidak boleh di-UPDATE langsung; ubah lewat `stock_movements` (barang masuk) atau `fn_stock_adjust_to` (opname).
- `transaction_details` unik per (`transaction_id`, `menu_id`): menu yang sama dua kali harus digabung di `qty`. Rincian hanya bisa ditambah saat pesanan `pending`, dan `subtotal` selalu dihitung ulang dari harga menu.
- `kafe_app` tidak punya DELETE pada `transaction_details`. Jika fitur "edit pesanan" butuh itu, diskusikan dulu (hak diberikan di `database/sql/2026_11_17_roles.sql`).
- Kata sandi di `users.password` harus hash bcrypt/argon (CHECK); email unik tanpa beda huruf besar/kecil.
- Middleware `SetDatabaseUser` mengisi `app.user_id` tiap request sehingga `audit_logs.app_user_id` berisi pelaku perubahan.
- `SampleTransactionSeeder` memberi tanggal pemakaian stok sesuai pesanan hanya bila dijalankan sebagai pemilik tabel (`php artisan db:seed --database=pgsql_admin`); sebagai `kafe_app` tanggalnya hari ini.
