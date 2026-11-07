# Kafe Kaleng — catatan frontend & basis data

Skema basis data mengikuti **`kafe_kaleng.sql`** (PostgreSQL). Berkas itu satu-satunya sumber skema:
tabel, view (`v_menu_availability`, `v_menu_hpp`, `v_ingredient_stock`), dan trigger stok.
SQL terbaru juga memuat tabel framework (sessions, cache, jobs, migrations), jadi `php artisan migrate` aman dijalankan.
Migrasi Laravel lama untuk users/transaksi/kategori/menu sudah dikosongkan agar tidak bentrok.

## Menjalankan
```bash
createdb kafe_kaleng && psql -d kafe_kaleng -f kafe_kaleng.sql
cp .env.example .env && php artisan key:generate     # isi DB_PASSWORD
php artisan migrate                                  # aman: tabel framework sudah ada di SQL
php artisan storage:link                             # foto menu
composer install && npm install && npm run build     # atau npm run dev
rm -f public/hot                                     # jika sisa dev server lama
php artisan serve
```
Akun contoh dari SQL: `admin@`, `kasir@`, `dapur@`, `waitress@`, `owner@kafekaleng.test`, kata sandi `kaleng123`.
Semua stok bahan di SQL bernilai 0, jadi menu tampil "Stok habis" di kasir sampai admin mencatat **Stok masuk**.

## Alur data
- Kasir menyimpan pesanan `pending` -> bayar (`completed`; cash/qris/debit, diskon none/percent/amount) -> dapur `waiting` -> `preparing` -> `ready` -> pelayan `served`.
- `subtotal`, `discount_amount`, dan `total` diisi trigger. Diskon diisi saat pembayaran dan terkunci setelah lunas.
- Stok bahan berubah hanya lewat `stock_movements` (trigger); jangan UPDATE `ingredients.stock_qty` dari PHP.
- Saat dapur menandai `ready`, trigger mencatat pemakaian bahan sesuai resep. Pesanan `ready` (dibayar) atau `served` tidak bisa dibatalkan.
- Role `waitress` hanya mengantar pesanan siap (`ready` -> `served`); membuat pesanan tetap hanya oleh `kasir`.
- Uang diterima/kembalian tidak ada di skema; hanya tampil di struk pertama.

## Desain
Mengikuti `Landing_Page.jpg`: halaman putih, judul cokelat tebal (Archivo Black) + kata miring (Cormorant Garamond),
filter & tombol tan, pita blush untuk penutup/peringatan. **Tanpa kartu**: bagian dipisah jarak, garis tipis, dan judul.
Token warna ada di `@theme` pada `resources/css/app.css`.

## Tes
`php artisan test` (butuh database hasil impor SQL; tiap test di-rollback).
