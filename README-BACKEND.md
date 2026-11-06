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
