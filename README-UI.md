# UI Kafe Kaleng — panduan integrasi (Frontend → tim)

## Pasang
1. Salin isi folder ini ke root project Laravel (timpa `resources/`, `routes/web.php`, `vite.config.js`).
2. Hapus `resources/views/dashboard.blade.php` & `resources/views/layouts/app.blade.php` lama (sudah digantikan).
3. `npm install && npm run dev`, lalu `php artisan serve`.
4. Buka `/` (landing), `/login`, lalu tombol pratinjau role (muncul saat `APP_DEBUG=true`).
5. (Opsional) simpan logo asli di `public/images/logo.png` — otomatis dipakai menggantikan logo SVG.

## Konvensi untuk backend
- **Role** layout dibaca dari `auth()->user()->role`; fallback ke segmen URL (`/admin`, `/kasir`, `/owner`).
- Form berlabel `data-demo` **tidak dikirim** ke server (hanya tampilkan toast). Hapus atribut itu dan isi `action`/`method` saat controller siap.
- Data contoh ada di `routes/ui-preview.php`; bentuk variabel sama dengan Eloquent (`$menu->name`, `$ing->stock_qty`, dst).
- Flash message: `->with('success', '…')` otomatis jadi toast.
- Hapus: tambahkan `data-confirm="teks"` pada `<form>` untuk memunculkan dialog konfirmasi.

## Variabel tiap view
| View | Variabel |
|---|---|
| admin.dashboard | `$stats[menus,categories,ingredients,today]`, `$lowStock`, `$movements` |
| admin.categories | `$categories` (+`menus_count`) |
| admin.menus | `$categories`, `$menus` (+`category`, `recipe_count`) |
| admin.recipe | `$menu`, `$ingredients`, `$recipe` (`ingredient_id`,`qty_needed`,`unit`) |
| admin.ingredients | `$ingredients` |
| admin.stock | `$ingredients`, `$lowStock`, `$movements` |
| kasir.pos | `$categories`, `$menus` (+`available` = stok cukup) |
| kasir.receipt | `$trx` |
| owner.dashboard | `$stats`, `$chart[labels,data]`, `$top`, `$critical` |

## ⚠️ Catatan skema database (diskusikan dengan PM / Dev A & B)
- `transactions` belum punya kolom **diskon** — UI kasir sudah ada fitur diskon (sesuai daftar fitur).
- `stock_movements` belum punya **harga/total biaya** — laporan laba-rugi butuh nilai pembelian bahan. Form stok masuk sudah menyediakan field `total_cost`.
- `menus.status` dipakai dengan nilai `active` / `inactive`; sesuaikan bila beda.
