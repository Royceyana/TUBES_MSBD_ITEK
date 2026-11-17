-- =====================================================================================
-- Kafe Kaleng | III.5 Integritas data: NOT NULL, CHECK, UNIQUE, FOREIGN KEY, indeks, komentar
-- Idempotent: aman dijalankan ulang (psql -f) maupun lewat migrasi Laravel.
-- Syarat: skema dasar (kafe_kaleng_schema.sql + migrasi waitress) sudah ada.
-- =====================================================================================

-- ---- 1. Kolom wajib terisi ------------------------------------------------------------
ALTER TABLE ingredients         ALTER COLUMN stock_qty        SET NOT NULL,
                                ALTER COLUMN min_stock        SET NOT NULL;
ALTER TABLE transaction_details ALTER COLUMN transaction_id   SET NOT NULL,
                                ALTER COLUMN menu_id          SET NOT NULL;
ALTER TABLE transactions        ALTER COLUMN user_id          SET NOT NULL,
                                ALTER COLUMN transaction_date SET NOT NULL;
ALTER TABLE stock_movements     ALTER COLUMN ingredient_id    SET NOT NULL,
                                ALTER COLUMN created_at       SET NOT NULL;

-- ---- 2. CHECK: nilai yang masuk akal --------------------------------------------------
ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_name_not_blank;
ALTER TABLE categories ADD  CONSTRAINT categories_name_not_blank CHECK (btrim(name) <> '');

ALTER TABLE menus DROP CONSTRAINT IF EXISTS menus_name_not_blank;
ALTER TABLE menus ADD  CONSTRAINT menus_name_not_blank CHECK (btrim(name) <> '');

ALTER TABLE ingredients DROP CONSTRAINT IF EXISTS ingredients_name_unit_not_blank;
ALTER TABLE ingredients ADD  CONSTRAINT ingredients_name_unit_not_blank CHECK (btrim(name) <> '' AND btrim(unit) <> '');
ALTER TABLE ingredients DROP CONSTRAINT IF EXISTS ingredients_min_stock_check;
ALTER TABLE ingredients ADD  CONSTRAINT ingredients_min_stock_check CHECK (min_stock >= 0);

ALTER TABLE transaction_details DROP CONSTRAINT IF EXISTS transaction_details_subtotal_check;
ALTER TABLE transaction_details ADD  CONSTRAINT transaction_details_subtotal_check CHECK (subtotal >= 0);

-- total = subtotal - diskon, dan diskon harus sama dengan hasil fn_calc_discount (tidak bisa diisi sembarang).
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_amounts_check;
ALTER TABLE transactions ADD  CONSTRAINT transactions_amounts_check
    CHECK (subtotal >= 0 AND total >= 0 AND total = subtotal - discount_amount);
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_discount_calc_check;
ALTER TABLE transactions ADD  CONSTRAINT transactions_discount_calc_check
    CHECK (discount_amount = fn_calc_discount(subtotal, discount_type, discount_value));
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_table_no_check;
ALTER TABLE transactions ADD  CONSTRAINT transactions_table_no_check CHECK (table_no IS NULL OR btrim(table_no) <> '');

-- Pemakaian bahan ('out') selalu berasal dari sebuah transaksi.
ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_out_needs_trx;
ALTER TABLE stock_movements ADD  CONSTRAINT stock_movements_out_needs_trx CHECK (type <> 'out' OR transaction_id IS NOT NULL);

-- Akun: nama terisi, email berformat benar, kata sandi wajib berupa hash (bcrypt/argon), bukan teks biasa.
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_name_not_blank;
ALTER TABLE users ADD  CONSTRAINT users_name_not_blank CHECK (btrim(name) <> '');
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_format_check;
ALTER TABLE users ADD  CONSTRAINT users_email_format_check CHECK (email ~ '^[^@[:space:]]+@[^@[:space:]]+\.[^@[:space:]]+$');
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_password_hashed_check;
ALTER TABLE users ADD  CONSTRAINT users_password_hashed_check
    CHECK (password ~ '^\$2[abxy]\$[0-9]{2}\$.{53}$' OR password ~ '^\$argon2(id|i|d)\$');

-- ---- 3. UNIQUE ------------------------------------------------------------------------
-- Satu baris rincian per menu dalam satu transaksi (jumlah digabung di kolom qty).
ALTER TABLE transaction_details DROP CONSTRAINT IF EXISTS uq_td_trx_menu;
ALTER TABLE transaction_details ADD  CONSTRAINT uq_td_trx_menu UNIQUE (transaction_id, menu_id);
-- Email tidak boleh kembar walau beda huruf besar/kecil.
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_lower ON users (lower(email));
-- Satu bahan hanya dipotong sekali per transaksi (pengaman ganda selain pengecekan di trigger).
CREATE UNIQUE INDEX IF NOT EXISTS uq_sm_out_per_trx ON stock_movements (transaction_id, ingredient_id) WHERE type = 'out';

-- ---- 4. FOREIGN KEY: riwayat tidak ikut hilang ---------------------------------------
-- Akun, bahan, dan transaksi yang punya riwayat tidak bisa dihapus (RESTRICT); nonaktifkan saja.
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_user_id_fkey;
ALTER TABLE transactions ADD  CONSTRAINT transactions_user_id_fkey
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;
ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_user_id_fkey;
ALTER TABLE stock_movements ADD  CONSTRAINT stock_movements_user_id_fkey
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;
ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_ingredient_id_fkey;
ALTER TABLE stock_movements ADD  CONSTRAINT stock_movements_ingredient_id_fkey
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT;
ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_transaction_id_fkey;
ALTER TABLE stock_movements ADD  CONSTRAINT stock_movements_transaction_id_fkey
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE RESTRICT;

-- ---- 5. Indeks (III.9) -----------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_menus_category        ON menus (category_id);
CREATE INDEX IF NOT EXISTS idx_menu_ingredients_ing  ON menu_ingredients (ingredient_id);
CREATE INDEX IF NOT EXISTS idx_td_menu               ON transaction_details (menu_id);
CREATE INDEX IF NOT EXISTS idx_trx_user              ON transactions (user_id);
CREATE INDEX IF NOT EXISTS idx_sm_user               ON stock_movements (user_id);
-- Antrian dapur/pelayan hanya melihat pesanan yang masih berjalan.
CREATE INDEX IF NOT EXISTS idx_trx_open_orders       ON transactions (order_status, transaction_date)
    WHERE status <> 'cancelled' AND order_status <> 'served';

-- ---- 6. Kamus data: komentar tabel dan kolom ------------------------------------------
COMMENT ON TABLE  users IS 'Akun pengguna aplikasi (admin, kasir, dapur, owner, waitress).';
COMMENT ON COLUMN users.id IS 'Kunci utama akun.';
COMMENT ON COLUMN users.name IS 'Nama lengkap pengguna.';
COMMENT ON COLUMN users.email IS 'Email untuk login; unik tanpa membedakan huruf besar/kecil.';
COMMENT ON COLUMN users.email_verified_at IS 'Waktu verifikasi email (bawaan Laravel, tidak dipakai).';
COMMENT ON COLUMN users.password IS 'Hash kata sandi (bcrypt/argon); teks biasa ditolak constraint.';
COMMENT ON COLUMN users.role IS 'Peran: admin, kasir, owner, dapur, atau waitress.';
COMMENT ON COLUMN users.status IS 'active = boleh login, inactive = diblokir.';
COMMENT ON COLUMN users.remember_token IS 'Token "ingat saya" bawaan Laravel.';
COMMENT ON COLUMN users.created_at IS 'Waktu akun dibuat.';
COMMENT ON COLUMN users.updated_at IS 'Waktu akun terakhir diubah.';

COMMENT ON TABLE  categories IS 'Kategori menu (Nasi, Mie, Snack, dst).';
COMMENT ON COLUMN categories.id IS 'Kunci utama kategori.';
COMMENT ON COLUMN categories.name IS 'Nama kategori; unik.';
COMMENT ON COLUMN categories.created_at IS 'Waktu kategori dibuat.';
COMMENT ON COLUMN categories.updated_at IS 'Waktu kategori terakhir diubah.';

COMMENT ON TABLE  menus IS 'Daftar menu yang dijual beserta harga jual.';
COMMENT ON COLUMN menus.id IS 'Kunci utama menu.';
COMMENT ON COLUMN menus.category_id IS 'Kategori menu; menjadi NULL bila kategori dihapus.';
COMMENT ON COLUMN menus.name IS 'Nama menu; unik.';
COMMENT ON COLUMN menus.price IS 'Harga jual per porsi (Rp), harus lebih dari 0.';
COMMENT ON COLUMN menus.image IS 'Path foto menu (opsional).';
COMMENT ON COLUMN menus.status IS 'active = dijual, inactive = disembunyikan dari kasir.';
COMMENT ON COLUMN menus.created_at IS 'Waktu menu dibuat.';
COMMENT ON COLUMN menus.updated_at IS 'Waktu menu terakhir diubah.';

COMMENT ON TABLE  ingredients IS 'Bahan baku beserta stok saat ini; stok hanya berubah lewat stock_movements.';
COMMENT ON COLUMN ingredients.id IS 'Kunci utama bahan.';
COMMENT ON COLUMN ingredients.name IS 'Nama bahan; unik.';
COMMENT ON COLUMN ingredients.unit IS 'Satuan stok (gram, ml, pcs, dst).';
COMMENT ON COLUMN ingredients.stock_qty IS 'Stok saat ini; diperbarui trigger dari stock_movements, tidak boleh diubah langsung.';
COMMENT ON COLUMN ingredients.min_stock IS 'Batas stok minimum; di bawah/sama dengan ini berstatus menipis.';
COMMENT ON COLUMN ingredients.cost_per_unit IS 'Harga pokok per satuan (Rp) untuk menghitung HPP menu.';
COMMENT ON COLUMN ingredients.is_active IS 'false = bahan dinonaktifkan (tidak dihapus agar riwayat stok utuh).';
COMMENT ON COLUMN ingredients.created_at IS 'Waktu bahan dibuat.';
COMMENT ON COLUMN ingredients.updated_at IS 'Waktu stok/data bahan terakhir berubah.';

COMMENT ON TABLE  menu_ingredients IS 'Resep: bahan dan takaran yang dipakai untuk satu porsi menu.';
COMMENT ON COLUMN menu_ingredients.id IS 'Kunci utama baris resep.';
COMMENT ON COLUMN menu_ingredients.menu_id IS 'Menu pemilik resep.';
COMMENT ON COLUMN menu_ingredients.ingredient_id IS 'Bahan yang dipakai.';
COMMENT ON COLUMN menu_ingredients.qty_needed IS 'Jumlah bahan per 1 porsi (dalam satuan bahan), harus lebih dari 0.';

COMMENT ON TABLE  transactions IS 'Pesanan/transaksi penjualan. Total dihitung trigger dari rincian dan diskon.';
COMMENT ON COLUMN transactions.id IS 'Kunci utama transaksi; nomor tampil INV-0001 diturunkan dari id.';
COMMENT ON COLUMN transactions.user_id IS 'Pengguna yang membuat pesanan; tidak boleh diubah.';
COMMENT ON COLUMN transactions.transaction_date IS 'Waktu pesanan dibuat; tidak boleh diubah.';
COMMENT ON COLUMN transactions.total IS 'Total bayar = subtotal - discount_amount; dihitung otomatis.';
COMMENT ON COLUMN transactions.payment_method IS 'Metode bayar: cash, qris, debit; wajib terisi saat status completed.';
COMMENT ON COLUMN transactions.status IS 'Status bayar: pending (belum), completed (lunas), cancelled (batal).';
COMMENT ON COLUMN transactions.order_status IS 'Tahap pesanan: waiting, preparing, ready (stok terpotong), served.';
COMMENT ON COLUMN transactions.table_no IS 'Nomor meja; NULL untuk bungkus/bawa pulang.';
COMMENT ON COLUMN transactions.subtotal IS 'Jumlah seluruh subtotal rincian; dihitung trigger.';
COMMENT ON COLUMN transactions.discount_type IS 'Jenis diskon: none, percent, atau amount.';
COMMENT ON COLUMN transactions.discount_value IS 'Nilai diskon (persen 0-100 atau nominal Rp).';
COMMENT ON COLUMN transactions.discount_amount IS 'Potongan harga hasil hitung fn_calc_discount; tidak melebihi subtotal.';

COMMENT ON TABLE  transaction_details IS 'Rincian menu yang dipesan dalam satu transaksi.';
COMMENT ON COLUMN transaction_details.id IS 'Kunci utama rincian.';
COMMENT ON COLUMN transaction_details.transaction_id IS 'Transaksi induk.';
COMMENT ON COLUMN transaction_details.menu_id IS 'Menu yang dipesan; menu yang pernah terjual tidak bisa dihapus.';
COMMENT ON COLUMN transaction_details.qty IS 'Jumlah porsi, lebih dari 0.';
COMMENT ON COLUMN transaction_details.subtotal IS 'Harga menu saat dipesan x qty; diisi trigger, tidak terpengaruh perubahan harga berikutnya.';

COMMENT ON TABLE  stock_movements IS 'Buku besar stok bersifat append-only: in (masuk), out (pemakaian pesanan), adjustment (opname).';
COMMENT ON COLUMN stock_movements.id IS 'Kunci utama pergerakan stok.';
COMMENT ON COLUMN stock_movements.ingredient_id IS 'Bahan yang bergerak.';
COMMENT ON COLUMN stock_movements.user_id IS 'Petugas yang mencatat (dari app.user_id); boleh kosong.';
COMMENT ON COLUMN stock_movements.transaction_id IS 'Transaksi penyebab pemakaian; wajib untuk type out.';
COMMENT ON COLUMN stock_movements.type IS 'in, out, atau adjustment.';
COMMENT ON COLUMN stock_movements.qty IS 'Jumlah; in/out > 0, adjustment boleh negatif (selisih opname).';
COMMENT ON COLUMN stock_movements.notes IS 'Catatan bebas.';
COMMENT ON COLUMN stock_movements.created_at IS 'Waktu pencatatan.';

COMMENT ON TABLE cache IS 'Tabel cache bawaan Laravel.';
COMMENT ON TABLE cache_locks IS 'Kunci atomic lock cache bawaan Laravel.';
COMMENT ON TABLE sessions IS 'Sesi login pengguna (driver database Laravel).';
COMMENT ON TABLE jobs IS 'Antrian pekerjaan latar belakang Laravel.';
COMMENT ON TABLE job_batches IS 'Kumpulan batch job Laravel.';
COMMENT ON TABLE failed_jobs IS 'Pekerjaan latar belakang yang gagal.';
COMMENT ON TABLE migrations IS 'Daftar migrasi Laravel yang sudah dijalankan.';
