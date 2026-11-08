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
Akun contoh dari SQL (atau `php artisan db:seed`, aman dijalankan berulang): `admin@`, `kasir@`, `dapur@`, `waitress@`, `owner@kafekaleng.test`, kata sandi `kaleng123`.
Semua stok bahan di SQL bernilai 0, jadi menu tampil "Stok habis" di kasir sampai admin mencatat **Stok masuk**.

## Alur data
- Kasir menyimpan pesanan `pending` -> bayar (`completed`; tunai/QRIS, diskon none/percent/amount) -> dapur `waiting` -> `preparing` -> `ready` -> pelayan `served`.
- `subtotal`, `discount_amount`, dan `total` diisi trigger. Diskon diisi saat pembayaran dan terkunci setelah lunas.
- Stok bahan berubah hanya lewat `stock_movements` (trigger); jangan UPDATE `ingredients.stock_qty` dari PHP.
- Saat dapur menandai `ready`, trigger mencatat pemakaian bahan sesuai resep. Pesanan `ready` (dibayar) atau `served` tidak bisa dibatalkan.
- Role `waitress` hanya mengantar pesanan siap (`ready` -> `served`); membuat pesanan tetap hanya oleh `kasir`.
- Uang diterima/kembalian tidak ada di skema; hanya tampil di struk pertama.

## Halaman admin: Kelola Pengguna
`/admin/users`: tambah akun, ubah nama/email/role/status, atur ulang kata sandi (opsional saat mengubah).
Akun tidak dihapus, hanya dinonaktifkan, karena transaksi dan riwayat stok mencatat `user_id`.
Admin tidak bisa menonaktifkan atau menurunkan role akunnya sendiri, dan harus selalu ada minimal satu admin aktif.

## Desain
Navigasi aplikasi berupa sidebar per role (di layar kecil jadi menu buka-tutup). Landing page: hero meniru acuan "the bake" (panel foto membulat, judul dua bobot, foto kecil dan tile menu). Halaman dalam mengikuti `Landing_Page.jpg`: halaman putih, judul cokelat tebal (Archivo Black) + kata miring (Cormorant Garamond),
filter & tombol tan, pita blush untuk penutup/peringatan. **Tanpa kartu**: bagian dipisah jarak, garis tipis, dan judul.
Token warna ada di `@theme` pada `resources/css/app.css`.

## Tes
`php artisan test` (butuh database hasil impor SQL; tiap test di-rollback).
