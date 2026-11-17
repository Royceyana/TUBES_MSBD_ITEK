# Lampiran Laporan MSBD: Kafe Kaleng, III.5 sampai III.7

Dokumen ini dibuat otomatis dari katalog PostgreSQL (`information_schema`, `pg_catalog`) setelah semua migrasi dijalankan, jadi isinya sama dengan database yang sebenarnya. Keterangan kolom berasal dari `COMMENT ON` di `database/sql/2026_11_17_integrity.sql`.

## III.5 Daftar Tabel, Constraint, View, dan Relasi Antar Tabel

### III.5.1 Daftar tabel

**Tabel 1. `users`**: Akun pengguna aplikasi (admin, kasir, dapur, owner, waitress).

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | bigint | - | Kunci utama akun. [PK; NOT NULL] |
| 2 | name | varchar | 100 | Nama lengkap pengguna. [NOT NULL] |
| 3 | email | varchar | 100 | Email untuk login; unik tanpa membedakan huruf besar/kecil. [UNIQUE; NOT NULL] |
| 4 | email_verified_at | timestamp (tanpa zona) | - | Waktu verifikasi email (bawaan Laravel, tidak dipakai). |
| 5 | password | varchar | 255 | Hash kata sandi (bcrypt/argon); teks biasa ditolak constraint. [NOT NULL] |
| 6 | role | varchar | 20 | Peran: admin, kasir, owner, dapur, atau waitress. [NOT NULL] |
| 7 | status | varchar | 20 | active = boleh login, inactive = diblokir. [NOT NULL; default 'active'] |
| 8 | remember_token | varchar | 100 | Token "ingat saya" bawaan Laravel. |
| 9 | created_at | timestamp (tanpa zona) | - | Waktu akun dibuat. |
| 10 | updated_at | timestamp (tanpa zona) | - | Waktu akun terakhir diubah. |

**Tabel 2. `categories`**: Kategori menu (Nasi, Mie, Snack, dst).

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama kategori. [PK; NOT NULL] |
| 2 | name | varchar | 50 | Nama kategori; unik. [UNIQUE; NOT NULL] |
| 3 | created_at | timestamp (tanpa zona) | - | Waktu kategori dibuat. [default CURRENT_TIMESTAMP] |
| 4 | updated_at | timestamp (tanpa zona) | - | Waktu kategori terakhir diubah. [default CURRENT_TIMESTAMP] |

**Tabel 3. `menus`**: Daftar menu yang dijual beserta harga jual.

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama menu. [PK; NOT NULL] |
| 2 | category_id | integer | - | Kategori menu; menjadi NULL bila kategori dihapus. [FK → categories.id] |
| 3 | name | varchar | 100 | Nama menu; unik. [UNIQUE; NOT NULL] |
| 4 | price | numeric | (10,2) | Harga jual per porsi (Rp), harus lebih dari 0. [NOT NULL] |
| 5 | image | varchar | 255 | Path foto menu (opsional). |
| 6 | status | varchar | 20 | active = dijual, inactive = disembunyikan dari kasir. [NOT NULL] |
| 7 | created_at | timestamp (tanpa zona) | - | Waktu menu dibuat. [default CURRENT_TIMESTAMP] |
| 8 | updated_at | timestamp (tanpa zona) | - | Waktu menu terakhir diubah. [default CURRENT_TIMESTAMP] |

**Tabel 4. `ingredients`**: Bahan baku beserta stok saat ini; stok hanya berubah lewat stock_movements.

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama bahan. [PK; NOT NULL] |
| 2 | name | varchar | 100 | Nama bahan; unik. [UNIQUE; NOT NULL] |
| 3 | unit | varchar | 20 | Satuan stok (gram, ml, pcs, dst). [NOT NULL] |
| 4 | stock_qty | numeric | (10,2) | Stok saat ini; diperbarui trigger dari stock_movements, tidak boleh diubah langsung. [NOT NULL; default 0.00] |
| 5 | min_stock | numeric | (10,2) | Batas stok minimum; di bawah/sama dengan ini berstatus menipis. [NOT NULL; default 0.00] |
| 6 | cost_per_unit | numeric | (12,4) | Harga pokok per satuan (Rp) untuk menghitung HPP menu. [NOT NULL; default 0] |
| 7 | is_active | boolean | - | false = bahan dinonaktifkan (tidak dihapus agar riwayat stok utuh). [NOT NULL; default true] |
| 8 | created_at | timestamp (tanpa zona) | - | Waktu bahan dibuat. [default CURRENT_TIMESTAMP] |
| 9 | updated_at | timestamp (tanpa zona) | - | Waktu stok/data bahan terakhir berubah. [default CURRENT_TIMESTAMP] |

**Tabel 5. `menu_ingredients`**: Resep: bahan dan takaran yang dipakai untuk satu porsi menu.

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama baris resep. [PK; NOT NULL] |
| 2 | menu_id | integer | - | Menu pemilik resep. [FK → menus.id; UNIQUE; NOT NULL] |
| 3 | ingredient_id | integer | - | Bahan yang dipakai. [FK → ingredients.id; UNIQUE; NOT NULL] |
| 4 | qty_needed | numeric | (10,2) | Jumlah bahan per 1 porsi (dalam satuan bahan), harus lebih dari 0. [NOT NULL] |

**Tabel 6. `transactions`**: Pesanan/transaksi penjualan. Total dihitung trigger dari rincian dan diskon.

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama transaksi; nomor tampil INV-0001 diturunkan dari id. [PK; NOT NULL] |
| 2 | user_id | integer | - | Pengguna yang membuat pesanan; tidak boleh diubah. [FK → users.id; NOT NULL] |
| 3 | transaction_date | timestamp (tanpa zona) | - | Waktu pesanan dibuat; tidak boleh diubah. [NOT NULL; default CURRENT_TIMESTAMP] |
| 4 | total | numeric | (12,2) | Total bayar = subtotal - discount_amount; dihitung otomatis. [NOT NULL; default 0] |
| 5 | payment_method | varchar | 20 | Metode bayar: cash, qris, debit; wajib terisi saat status completed. |
| 6 | status | varchar | 20 | Status bayar: pending (belum), completed (lunas), cancelled (batal). [NOT NULL; default 'pending'] |
| 7 | order_status | varchar | 20 | Tahap pesanan: waiting, preparing, ready (stok terpotong), served. [NOT NULL; default 'waiting'] |
| 8 | table_no | varchar | 10 | Nomor meja; NULL untuk bungkus/bawa pulang. |
| 9 | subtotal | numeric | (12,2) | Jumlah seluruh subtotal rincian; dihitung trigger. [NOT NULL; default 0] |
| 10 | discount_type | varchar | 10 | Jenis diskon: none, percent, atau amount. [NOT NULL; default 'none'] |
| 11 | discount_value | numeric | (12,2) | Nilai diskon (persen 0-100 atau nominal Rp). [NOT NULL; default 0] |
| 12 | discount_amount | numeric | (12,2) | Potongan harga hasil hitung fn_calc_discount; tidak melebihi subtotal. [NOT NULL; default 0] |

**Tabel 7. `transaction_details`**: Rincian menu yang dipesan dalam satu transaksi.

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama rincian. [PK; NOT NULL] |
| 2 | transaction_id | integer | - | Transaksi induk. [FK → transactions.id; UNIQUE; NOT NULL] |
| 3 | menu_id | integer | - | Menu yang dipesan; menu yang pernah terjual tidak bisa dihapus. [FK → menus.id; UNIQUE; NOT NULL] |
| 4 | qty | integer | - | Jumlah porsi, lebih dari 0. [NOT NULL] |
| 5 | subtotal | numeric | (12,2) | Harga menu saat dipesan x qty; diisi trigger, tidak terpengaruh perubahan harga berikutnya. [NOT NULL] |

**Tabel 8. `stock_movements`**: Buku besar stok bersifat append-only: in (masuk), out (pemakaian pesanan), adjustment (opname).

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | integer | - | Kunci utama pergerakan stok. [PK; NOT NULL] |
| 2 | ingredient_id | integer | - | Bahan yang bergerak. [FK → ingredients.id; NOT NULL] |
| 3 | user_id | integer | - | Petugas yang mencatat (dari app.user_id); boleh kosong. [FK → users.id] |
| 4 | transaction_id | integer | - | Transaksi penyebab pemakaian; wajib untuk type out. [FK → transactions.id] |
| 5 | type | varchar | 20 | in, out, atau adjustment. [NOT NULL] |
| 6 | qty | numeric | (10,2) | Jumlah; in/out > 0, adjustment boleh negatif (selisih opname). [NOT NULL] |
| 7 | notes | text | - | Catatan bebas. |
| 8 | created_at | timestamp (tanpa zona) | - | Waktu pencatatan. [NOT NULL; default CURRENT_TIMESTAMP] |

**Tabel 9. `audit_logs`**: Jejak perubahan data penting (append-only). Diisi trigger, tidak bisa diubah/dihapus.

| No | Nama Field | Jenis Field | Ukuran | Keterangan |
|---|---|---|---|---|
| 1 | id | bigint | - | Kunci utama log. [PK; NOT NULL] |
| 2 | table_name | text | - | Tabel yang berubah. [NOT NULL] |
| 3 | record_id | text | - | Nilai id baris yang berubah. |
| 4 | action | text | - | INSERT, UPDATE, atau DELETE. [NOT NULL] |
| 5 | old_data | jsonb | - | Data lama (UPDATE: hanya kolom yang berubah). Password tidak disimpan. |
| 6 | new_data | jsonb | - | Data baru (UPDATE: hanya kolom yang berubah). Password tidak disimpan. |
| 7 | changed_columns | text[] | - | Daftar kolom yang berubah pada UPDATE. |
| 8 | app_user_id | integer | - | users.id pelaku menurut aplikasi (app.user_id); kosong bila tidak diset. |
| 9 | db_user | text | - | Akun login database yang menjalankan perubahan. [NOT NULL; default SESSION_USER] |
| 10 | client_addr | inet | - | Alamat IP klien database. [default inet_client_addr()] |
| 11 | note | text | - | Keterangan tambahan, mis. alasan pembatalan. |
| 12 | changed_at | timestamp | - | Waktu perubahan. [NOT NULL; default now()] |

### III.5.2 Constraint

| Tabel | Nama Constraint | Jenis | Definisi |
|---|---|---|---|
| users | `users_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| users | `users_email_unique` | UNIQUE | `UNIQUE (email)` |
| users | `users_email_format_check` | CHECK | `CHECK (((email)::text ~ '^[^@[:space:]]+@[^@[:space:]]+\.[^@[:space:]]+$'::text))` |
| users | `users_name_not_blank` | CHECK | `CHECK ((btrim((name)::text) <> ''::text))` |
| users | `users_password_hashed_check` | CHECK | `CHECK ((((password)::text ~ '^\$2[abxy]\$[0-9]{2}\$.{53}$'::text) OR ((password)::text ~ '^\$argon2(id\|i\|d)\$'::text)))` |
| users | `users_role_check` | CHECK | `CHECK (((role)::text = ANY (ARRAY['admin'::text, 'kasir'::text, 'owner'::text, 'dapur'::text, 'waitress'::text])))` |
| users | `users_status_check` | CHECK | `CHECK (((status)::text = ANY (ARRAY['active'::text, 'inactive'::text])))` |
| categories | `categories_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| categories | `categories_name_key` | UNIQUE | `UNIQUE (name)` |
| categories | `categories_name_not_blank` | CHECK | `CHECK ((btrim((name)::text) <> ''::text))` |
| menus | `menus_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| menus | `menus_category_id_fkey` | FOREIGN KEY | `FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL` |
| menus | `menus_name_key` | UNIQUE | `UNIQUE (name)` |
| menus | `menus_name_not_blank` | CHECK | `CHECK ((btrim((name)::text) <> ''::text))` |
| menus | `menus_price_check` | CHECK | `CHECK ((price > (0)::numeric))` |
| menus | `menus_status_check` | CHECK | `CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('inactive'::character varying)::text])))` |
| ingredients | `ingredients_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| ingredients | `ingredients_name_key` | UNIQUE | `UNIQUE (name)` |
| ingredients | `ingredients_cost_per_unit_check` | CHECK | `CHECK ((cost_per_unit >= (0)::numeric))` |
| ingredients | `ingredients_min_stock_check` | CHECK | `CHECK ((min_stock >= (0)::numeric))` |
| ingredients | `ingredients_name_unit_not_blank` | CHECK | `CHECK (((btrim((name)::text) <> ''::text) AND (btrim((unit)::text) <> ''::text)))` |
| menu_ingredients | `menu_ingredients_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| menu_ingredients | `menu_ingredients_ingredient_id_fkey` | FOREIGN KEY | `FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE` |
| menu_ingredients | `menu_ingredients_menu_id_fkey` | FOREIGN KEY | `FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE` |
| menu_ingredients | `menu_ingredients_menu_id_ingredient_id_key` | UNIQUE | `UNIQUE (menu_id, ingredient_id)` |
| menu_ingredients | `menu_ingredients_qty_needed_check` | CHECK | `CHECK ((qty_needed > (0)::numeric))` |
| transactions | `transactions_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| transactions | `transactions_user_id_fkey` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| transactions | `transactions_amounts_check` | CHECK | `CHECK (((subtotal >= (0)::numeric) AND (total >= (0)::numeric) AND (total = (subtotal - discount_amount))))` |
| transactions | `transactions_discount_amount_check` | CHECK | `CHECK (((discount_amount >= (0)::numeric) AND (discount_amount <= subtotal)))` |
| transactions | `transactions_discount_calc_check` | CHECK | `CHECK ((discount_amount = fn_calc_discount(subtotal, (discount_type)::text, discount_value)))` |
| transactions | `transactions_discount_type_check` | CHECK | `CHECK (((discount_type)::text = ANY (ARRAY['none'::text, 'percent'::text, 'amount'::text])))` |
| transactions | `transactions_discount_value_check` | CHECK | `CHECK (((discount_value >= (0)::numeric) AND (((discount_type)::text <> 'percent'::text) OR (discount_value <= (100)::numeric)) AND (((discount_type)::text <> 'none'::text) OR (discount_value = (0)::numeric))))` |
| transactions | `transactions_order_status_check` | CHECK | `CHECK (((order_status)::text = ANY (ARRAY['waiting'::text, 'preparing'::text, 'ready'::text, 'served'::text])))` |
| transactions | `transactions_paid_needs_method` | CHECK | `CHECK ((((status)::text <> 'completed'::text) OR (payment_method IS NOT NULL)))` |
| transactions | `transactions_payment_method_check` | CHECK | `CHECK (((payment_method)::text = ANY (ARRAY['cash'::text, 'qris'::text, 'debit'::text])))` |
| transactions | `transactions_status_check` | CHECK | `CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text])))` |
| transactions | `transactions_table_no_check` | CHECK | `CHECK (((table_no IS NULL) OR (btrim((table_no)::text) <> ''::text)))` |
| transaction_details | `transaction_details_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| transaction_details | `transaction_details_menu_id_fkey` | FOREIGN KEY | `FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE RESTRICT` |
| transaction_details | `transaction_details_transaction_id_fkey` | FOREIGN KEY | `FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE` |
| transaction_details | `uq_td_trx_menu` | UNIQUE | `UNIQUE (transaction_id, menu_id)` |
| transaction_details | `transaction_details_qty_check` | CHECK | `CHECK ((qty > 0))` |
| transaction_details | `transaction_details_subtotal_check` | CHECK | `CHECK ((subtotal >= (0)::numeric))` |
| stock_movements | `stock_movements_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| stock_movements | `stock_movements_ingredient_id_fkey` | FOREIGN KEY | `FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT` |
| stock_movements | `stock_movements_transaction_id_fkey` | FOREIGN KEY | `FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE RESTRICT` |
| stock_movements | `stock_movements_user_id_fkey` | FOREIGN KEY | `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT` |
| stock_movements | `stock_movements_out_needs_trx` | CHECK | `CHECK ((((type)::text <> 'out'::text) OR (transaction_id IS NOT NULL)))` |
| stock_movements | `stock_movements_qty_check` | CHECK | `CHECK (((((type)::text = 'adjustment'::text) AND (qty <> (0)::numeric)) OR (((type)::text = ANY (ARRAY[('in'::character varying)::text, ('out'::character varying)::text])) AND (qty > (0)::numeric))))` |
| stock_movements | `stock_movements_type_check` | CHECK | `CHECK (((type)::text = ANY (ARRAY[('in'::character varying)::text, ('out'::character varying)::text, ('adjustment'::character varying)::text])))` |
| audit_logs | `audit_logs_pkey` | PRIMARY KEY | `PRIMARY KEY (id)` |
| audit_logs | `audit_logs_action_check` | CHECK | `CHECK ((action = ANY (ARRAY['INSERT'::text, 'UPDATE'::text, 'DELETE'::text])))` |

Indeks tambahan (III.9):

| Tabel | Indeks | Definisi |
|---|---|---|
| audit_logs | `idx_audit_changed_at` | `CREATE INDEX idx_audit_changed_at ON public.audit_logs USING btree (changed_at)` |
| audit_logs | `idx_audit_table_record` | `CREATE INDEX idx_audit_table_record ON public.audit_logs USING btree (table_name, record_id)` |
| menu_ingredients | `idx_menu_ingredients_ing` | `CREATE INDEX idx_menu_ingredients_ing ON public.menu_ingredients USING btree (ingredient_id)` |
| menus | `idx_menus_category` | `CREATE INDEX idx_menus_category ON public.menus USING btree (category_id)` |
| stock_movements | `idx_sm_user` | `CREATE INDEX idx_sm_user ON public.stock_movements USING btree (user_id)` |
| stock_movements | `idx_stock_mov_ing` | `CREATE INDEX idx_stock_mov_ing ON public.stock_movements USING btree (ingredient_id, created_at)` |
| stock_movements | `idx_stock_mov_trx` | `CREATE INDEX idx_stock_mov_trx ON public.stock_movements USING btree (transaction_id)` |
| stock_movements | `uq_sm_out_per_trx` | `CREATE UNIQUE INDEX uq_sm_out_per_trx ON public.stock_movements USING btree (transaction_id, ingredient_id) WHERE ((type)::text = 'out'::text)` |
| transaction_details | `idx_td_menu` | `CREATE INDEX idx_td_menu ON public.transaction_details USING btree (menu_id)` |
| transaction_details | `idx_td_trx` | `CREATE INDEX idx_td_trx ON public.transaction_details USING btree (transaction_id)` |
| transaction_details | `uq_td_trx_menu` | `CREATE UNIQUE INDEX uq_td_trx_menu ON public.transaction_details USING btree (transaction_id, menu_id)` |
| transactions | `idx_trx_date` | `CREATE INDEX idx_trx_date ON public.transactions USING btree (transaction_date)` |
| transactions | `idx_trx_open_orders` | `CREATE INDEX idx_trx_open_orders ON public.transactions USING btree (order_status, transaction_date) WHERE (((status)::text <> 'cancelled'::text) AND ((order_status)::text <> 'served'::text))` |
| transactions | `idx_trx_status` | `CREATE INDEX idx_trx_status ON public.transactions USING btree (status, order_status)` |
| transactions | `idx_trx_user` | `CREATE INDEX idx_trx_user ON public.transactions USING btree (user_id)` |
| users | `uq_users_email_lower` | `CREATE UNIQUE INDEX uq_users_email_lower ON public.users USING btree (lower((email)::text))` |
| users | `users_email_unique` | `CREATE UNIQUE INDEX users_email_unique ON public.users USING btree (email)` |

### III.5.3 Relasi antar tabel (foreign key)

| Tabel anak | Kolom | Tabel induk | Kolom induk | ON DELETE |
|---|---|---|---|---|
| menu_ingredients | ingredient_id | ingredients | id | CASCADE |
| menu_ingredients | menu_id | menus | id | CASCADE |
| menus | category_id | categories | id | SET NULL |
| stock_movements | ingredient_id | ingredients | id | RESTRICT |
| stock_movements | transaction_id | transactions | id | RESTRICT |
| stock_movements | user_id | users | id | RESTRICT |
| transaction_details | menu_id | menus | id | RESTRICT |
| transaction_details | transaction_id | transactions | id | CASCADE |
| transactions | user_id | users | id | RESTRICT |

Ringkasan kardinalitas: satu `categories` memiliki banyak `menus`; satu `menus` memiliki banyak `menu_ingredients` (resep) dan banyak `transaction_details`; satu `ingredients` ada di banyak resep dan banyak `stock_movements`; satu `transactions` memiliki banyak `transaction_details` dan banyak `stock_movements` (pemakaian bahan); satu `users` membuat banyak `transactions` dan `stock_movements`.

### III.5.4 View

| View | Fungsi | Kolom |
|---|---|---|
| `v_audit_log` | Audit log dengan nama pelaku aplikasi. | id, waktu, tabel, record_id, aksi, kolom_berubah, old_data, new_data, pelaku_aplikasi, akun_database, catatan |
| `v_ingredient_stock` | Stok bahan aktif beserta status aman/menipis/habis. | id, name, unit, stock_qty, min_stock, stock_status, updated_at |
| `v_menu_availability` | Ketersediaan menu: porsi yang bisa dibuat dari stok bahan. | menu_id, name, category_id, price, status, jumlah_bahan, porsi_tersedia, is_available |
| `v_menu_hpp` | Harga pokok penjualan (HPP), margin, dan persen margin per menu dari resep. | menu_id, name, price, jumlah_bahan, hpp, margin, margin_persen |
| `v_order_queue` | Antrian pesanan aktif (belum batal/diantar) beserta daftar item dan lama menunggu. | id, nomor, table_no, status, order_status, tanggal, menit_menunggu, item |
| `v_sales_by_menu` | Penjualan lunas per menu: qty, pendapatan, HPP, laba kotor. | menu_id, name, qty_terjual, pendapatan, hpp_total, laba_kotor |
| `v_sales_daily` | Rekap penjualan lunas per hari, termasuk omzet per metode bayar. | tanggal, jumlah_transaksi, subtotal, diskon, omzet, omzet_tunai, omzet_qris, omzet_debit |
| `v_stock_history` | Riwayat pergerakan stok dengan nama bahan dan petugas. | id, waktu, bahan, satuan, jenis, qty, catatan, petugas, nomor_transaksi |
| `v_transaction_summary` | Ringkasan satu baris per transaksi dengan nama petugas dan jumlah item. | id, nomor, tanggal, petugas, table_no, status, order_status, payment_method, jumlah_item, subtotal, diskon, total |
| `v_user_public` | Data akun tanpa password dan remember_token. | id, name, email, role, status, created_at, updated_at |

## III.6 Trigger, Fungsi, dan Prosedur Tersimpan

### III.6.1 Fungsi

| Fungsi | Parameter | Mengembalikan | Keterangan | Dijalankan sebagai |
|---|---|---|---|---|
| `fn_assert_role` | p_user_id integer, p_roles text[] | void | Menolak operasi bila akun tidak aktif atau perannya tidak diizinkan. | INVOKER |
| `fn_calc_discount` | p_subtotal numeric, p_type text, p_value numeric | numeric | Menghitung potongan harga (none/percent/amount), tidak melebihi subtotal. | INVOKER |
| `fn_menu_portions` | p_menu_id integer | integer | Porsi menu yang masih bisa dibuat dari stok (NULL bila tanpa resep). | INVOKER |
| `fn_order_number` | p_trx_id integer | text | Nomor tampil transaksi, mis. INV-0042. | INVOKER |
| `fn_sales_report` | p_from date, p_to date | TABLE(tanggal date, jumlah_transaksi bigint, subtotal numeric, diskon numeric, omzet numeric, hpp numeric, laba_kotor numeric) | Laporan penjualan, HPP, dan laba kotor per hari pada rentang tanggal. | DEFINER |
| `fn_stock_adjust_to` | p_ingredient_id integer, p_actual_qty numeric, p_user_id integer, p_notes text | numeric | Stok opname: menyamakan stok sistem dengan stok fisik lewat stock_movements adjustment. | INVOKER |

### III.6.2 Prosedur tersimpan

| Prosedur | Parameter | Keterangan | Eksekusi sebagai |
|---|---|---|---|
| `sp_cancel_order` | IN p_trx_id integer, IN p_user_id integer, IN p_reason text | Membatalkan pesanan dan mencatat alasan di audit_logs. | DEFINER |
| `sp_create_order` | IN p_user_id integer, IN p_table_no text, IN p_items jsonb, INOUT p_trx_id integer | Membuat pesanan beserta rincian, memeriksa menu aktif dan stok, atomik. | DEFINER |
| `sp_pay_order` | IN p_trx_id integer, IN p_method text, IN p_user_id integer, IN p_discount_type text, IN p_discount_value numeric | Membayar pesanan pending dengan diskon opsional. | DEFINER |
| `sp_restock` | IN p_ingredient_id integer, IN p_qty numeric, IN p_user_id integer, IN p_notes text | Mencatat barang masuk. | DEFINER |
| `sp_set_order_status` | IN p_trx_id integer, IN p_new_status text, IN p_user_id integer | Memajukan tahap pesanan sesuai peran (dapur/pelayan/admin). | DEFINER |
| `sp_stock_opname` | IN p_ingredient_id integer, IN p_actual_qty numeric, IN p_user_id integer, IN p_notes text, INOUT p_delta numeric | Menyamakan stok sistem dengan stok fisik. | DEFINER |

Contoh pemakaian:

```sql
CALL sp_create_order(4, '5', '[{"menu_id":1,"qty":2},{"menu_id":9,"qty":1}]'::jsonb, NULL);  -- waitress id 4, meja 5
CALL sp_pay_order(98, 'qris', 2, 'percent', 10);                                             -- kasir id 2, diskon 10%
CALL sp_set_order_status(98, 'preparing', 3);                                                -- dapur id 3
CALL sp_cancel_order(99, 2, 'pelanggan batal');
SELECT * FROM fn_sales_report(CURRENT_DATE - 7, CURRENT_DATE);
```

### III.6.3 Trigger

| Tabel | Trigger | Waktu | Peristiwa | Level | Fungsi | Keterangan |
|---|---|---|---|---|---|---|
| audit_logs | `trg_audit_immutable` | BEFORE | DELETE OR UPDATE | ROW | `fn_block_modification` | Trigger: menolak UPDATE/DELETE pada tabel append-only. |
| audit_logs | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| categories | `trg_audit_categories` | AFTER | INSERT OR DELETE OR UPDATE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| categories | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| ingredients | `trg_audit_ingredients_iud` | AFTER | INSERT OR DELETE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| ingredients | `trg_audit_ingredients_upd` | AFTER | UPDATE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| ingredients | `trg_ing_protect_stock_ins` | BEFORE | INSERT | ROW | `fn_protect_ingredient_stock` | Trigger: stok bahan hanya boleh berubah lewat stock_movements. |
| ingredients | `trg_ing_protect_stock_upd` | BEFORE | UPDATE | ROW | `fn_protect_ingredient_stock` | Trigger: stok bahan hanya boleh berubah lewat stock_movements. |
| ingredients | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| menu_ingredients | `trg_audit_menu_ingredients` | AFTER | INSERT OR DELETE OR UPDATE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| menu_ingredients | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| menus | `trg_audit_menus` | AFTER | INSERT OR DELETE OR UPDATE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| menus | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| stock_movements | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| stock_movements | `trg_sm_immutable` | BEFORE | DELETE OR UPDATE | ROW | `fn_block_modification` | Trigger: menolak UPDATE/DELETE pada tabel append-only. |
| stock_movements | `trg_stock_movement_apply` | AFTER | INSERT | ROW | `fn_apply_stock_movement` | Trigger: menambah/mengurangi ingredients.stock_qty dari stock_movements. |
| transaction_details | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| transaction_details | `trg_td_protect` | BEFORE | INSERT OR DELETE OR UPDATE | ROW | `fn_protect_transaction_details` | Trigger: rincian hanya bisa berubah saat pending; subtotal = harga menu x qty. |
| transaction_details | `trg_td_sync_total` | AFTER | INSERT OR DELETE OR UPDATE | ROW | `fn_sync_transaction_total` | Trigger: menghitung ulang subtotal, diskon, dan total dari transaction_details. |
| transactions | `trg_audit_transactions_id` | AFTER | INSERT OR DELETE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| transactions | `trg_audit_transactions_upd` | AFTER | UPDATE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| transactions | `trg_consume_stock_on_ready` | AFTER | UPDATE | ROW | `fn_consume_stock_on_ready` | Trigger: memotong stok bahan sesuai resep saat order_status menjadi ready (idempotent). |
| transactions | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| transactions | `trg_trx_apply_discount` | BEFORE | INSERT OR UPDATE | ROW | `fn_apply_discount` | Trigger: menghitung discount_amount dan total; diskon terkunci setelah lunas. |
| transactions | `trg_trx_guard_cancel` | BEFORE | UPDATE | ROW | `fn_guard_cancel` | Trigger: aturan pembatalan pesanan (sudah diantar/siap dan lunas tidak bisa batal). |
| transactions | `trg_trx_guard_order_status` | BEFORE | UPDATE | ROW | `fn_guard_order_status` | Trigger: urutan tahap pesanan; served hanya setelah ready dan tidak bisa mundur. |
| transactions | `trg_trx_protect` | BEFORE | INSERT OR DELETE OR UPDATE | ROW | `fn_protect_transactions` | Trigger: transaksi tidak boleh dihapus, pembuat/waktu/metode bayar terkunci. |
| transactions | `trg_trx_verify_amounts` | BEFORE | UPDATE | ROW | `fn_verify_transaction_amounts` | Trigger: subtotal harus sama dengan jumlah rincian. |
| users | `trg_audit_users` | AFTER | INSERT OR DELETE OR UPDATE | ROW | `fn_audit_row` | Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan). |
| users | `trg_no_truncate` | BEFORE | TRUNCATE | STATEMENT | `fn_block_truncate` | Trigger: menolak TRUNCATE pada tabel inti. |
| users | `trg_users_keep_admin` | BEFORE | DELETE OR UPDATE | ROW | `fn_protect_users` | Trigger: menjaga minimal satu admin aktif. |

## III.7 Manajemen User, Privilege, Keamanan, dan Transaksi Database

### III.7.1 Pengguna database

| Role | Jenis | Keterangan | Superuser | Batas penggunaan |
|---|---|---|---|---|
| `admin_kafe` | LOGIN | Akun login peran aplikasi, anggota kafe_role_admin | false | search_path=public; statement_timeout=30s |
| `kafe_app` | LOGIN | Akun runtime aplikasi Laravel: DML terbatas, tanpa DDL, tanpa DELETE data transaksi. | false | search_path=public; statement_timeout=30s; idle_in_transaction_session_timeout=60s |
| `kafe_auditor` | LOGIN | Akun pemeriksa: hanya membaca audit log dan riwayat stok. | false | search_path=public; statement_timeout=15s |
| `kafe_report` | LOGIN | Akun laporan: hanya SELECT pada view laporan (tanpa data akun). | false | search_path=public; statement_timeout=15s |
| `owner_kafe` | LOGIN | Akun login peran aplikasi, anggota kafe_role_owner | false | search_path=public; statement_timeout=30s |
| `staff_chef` | LOGIN | Akun login peran aplikasi, anggota kafe_role_dapur | false | search_path=public; statement_timeout=30s |
| `staff_kasir` | LOGIN | Akun login peran aplikasi, anggota kafe_role_kasir | false | search_path=public; statement_timeout=30s |
| `staff_waitress` | LOGIN | Akun login peran aplikasi, anggota kafe_role_waitress | false | search_path=public; statement_timeout=30s |
| `kafe_role_admin` | NOLOGIN (group) | Group role admin: semua prosedur + view master/laporan. | false |  |
| `kafe_role_dapur` | NOLOGIN (group) | Group role dapur: ubah tahap pesanan, opname dan restock. | false |  |
| `kafe_role_kasir` | NOLOGIN (group) | Group role kasir: buat/bayar/batal pesanan lewat prosedur. | false |  |
| `kafe_role_owner` | NOLOGIN (group) | Group role owner: laporan penjualan dan stok (baca-saja). | false |  |
| `kafe_role_waitress` | NOLOGIN (group) | Group role waitress: buat pesanan dan tandai diantar. | false |  |

Selain itu ada `postgres` (superuser, pemilik objek) yang hanya dipakai untuk migrasi/seeder/backup lewat koneksi `pgsql_admin`, bukan oleh aplikasi saat berjalan. Akun pengguna aplikasi (admin, kasir, dapur, owner, waitress) ada di tabel `users`; peran aplikasi dicek di middleware `role`, sedangkan prosedur tersimpan memeriksa ulang peran itu di sisi database.

### III.7.2 Hak akses pada tabel

| Tabel | kafe_app | kafe_report | kafe_auditor |
|---|---|---|---|
| `users` | RCU | - | - |
| `categories` | RCUD | - | - |
| `menus` | RCUD | - | - |
| `ingredients` | RCU | - | - |
| `menu_ingredients` | RCUD | - | - |
| `transactions` | RCU | - | - |
| `transaction_details` | RC | - | - |
| `stock_movements` | RC | - | - |
| `audit_logs` | - | - | R |

Keterangan: R = SELECT, C = INSERT, U = UPDATE, D = DELETE, T = TRUNCATE, `-` = tanpa akses. `kafe_report` dan `kafe_auditor` hanya membaca lewat view; `audit_logs` hanya terbaca oleh `kafe_auditor` dan lewat view `v_audit_log`. Tidak ada role selain pemilik yang boleh DDL (CREATE/ALTER/DROP).

### III.7.3 Hak akses pada view dan prosedur

| Objek | Hak | app | report | auditor | role_admin | role_kasir | role_dapur | role_waitress | role_owner |
|---|---|---|---|---|---|---|---|---|---|
| `v_audit_log` | SELECT | ✓ |  | ✓ | ✓ |  |  |  |  |
| `v_ingredient_stock` | SELECT | ✓ | ✓ |  | ✓ |  | ✓ |  | ✓ |
| `v_menu_availability` | SELECT | ✓ | ✓ |  | ✓ | ✓ | ✓ | ✓ |  |
| `v_menu_hpp` | SELECT | ✓ | ✓ |  | ✓ |  |  |  | ✓ |
| `v_order_queue` | SELECT | ✓ |  |  | ✓ | ✓ | ✓ | ✓ |  |
| `v_sales_by_menu` | SELECT | ✓ | ✓ |  | ✓ |  |  |  | ✓ |
| `v_sales_daily` | SELECT | ✓ | ✓ |  | ✓ |  |  |  | ✓ |
| `v_stock_history` | SELECT | ✓ |  | ✓ | ✓ |  | ✓ |  |  |
| `v_transaction_summary` | SELECT | ✓ | ✓ |  | ✓ | ✓ |  |  | ✓ |
| `v_user_public` | SELECT | ✓ |  | ✓ | ✓ |  |  |  |  |
| `sp_cancel_order` | EXECUTE |  |  |  |  |  |  |  |  |
| `sp_create_order` | EXECUTE |  |  |  |  |  |  |  |  |
| `sp_pay_order` | EXECUTE |  |  |  |  |  |  |  |  |
| `sp_restock` | EXECUTE |  |  |  |  |  |  |  |  |
| `sp_set_order_status` | EXECUTE |  |  |  |  |  |  |  |  |
| `sp_stock_opname` | EXECUTE |  |  |  |  |  |  |  |  |
| `fn_menu_portions` | EXECUTE |  |  |  |  |  |  |  |  |
| `fn_sales_report` | EXECUTE |  |  |  |  |  |  |  |  |
| `fn_stock_adjust_to` | EXECUTE |  |  |  |  |  |  |  |  |

### III.7.4 Keamanan database (berlapis)

| Lapisan | Mekanisme | Mencegah |
|---|---|---|
| Akun | Aplikasi memakai `kafe_app` (bukan `postgres`): tanpa DDL, tanpa DELETE/TRUNCATE pada transaksi, tanpa tulis ke `audit_logs` | Aplikasi yang bocor/diretas tidak bisa menghapus tabel atau riwayat |
| Akses bawaan | `REVOKE ... FROM PUBLIC`, `REVOKE CREATE ON SCHEMA public`, `CONNECT` hanya untuk role terdaftar | Akun lain di server ikut masuk |
| Data transaksi | Trigger `trg_trx_protect`: transaksi tidak bisa dihapus, pembuat/waktu/metode bayar terkunci, lunas tidak bisa kembali pending | Pemalsuan atau penghapusan penjualan |
| Nominal | CHECK `total = subtotal - diskon`, diskon = `fn_calc_discount`, trigger memastikan subtotal = jumlah rincian, subtotal rincian = harga menu x qty | Mengubah angka total/harga langsung |
| Rincian | Trigger `trg_td_protect`: rincian hanya bisa berubah saat pesanan `pending` | Mengubah isi pesanan yang sudah dibayar |
| Stok | `stock_qty` hanya bisa berubah lewat `stock_movements` (trigger); `stock_movements` append-only; pemakaian ganda dicegah unique index | Mengubah stok tanpa jejak |
| Audit | `audit_logs` diisi trigger SECURITY DEFINER, append-only, mencatat pelaku (`app.user_id`), akun DB, IP; hash password tidak disimpan | Perubahan tanpa jejak, pemalsuan log |
| Akun aplikasi | CHECK password harus hash bcrypt/argon, email valid dan unik tanpa beda huruf, trigger menjaga minimal satu admin aktif | Password teks biasa, akun admin terkunci |
| Struktur | FK `RESTRICT` untuk riwayat, `TRUNCATE` diblokir trigger | Data induk terhapus berantai |
| Beban | `statement_timeout`, `idle_in_transaction_session_timeout`, batas koneksi untuk role laporan | Query liar menahan database |

Catatan batas: superuser (`postgres`) tetap dapat menonaktifkan trigger (`ALTER TABLE ... DISABLE TRIGGER`) atau membaca berkas data langsung. Karena itu `postgres` tidak dipakai aplikasi, kata sandinya harus kuat, dan akses jaringan ke port 5432 dibatasi (`pg_hba.conf`, firewall).

### III.7.5 Pengelolaan transaksi database

Prinsipnya ACID: sekumpulan operasi dijalankan lengkap atau dibatalkan seluruhnya. Penerapan di Kafe Kaleng:

| Kasus | Teknik | Lokasi |
|---|---|---|
| Buat pesanan + rincian | `DB::transaction` (BEGIN ... COMMIT; ROLLBACK otomatis bila error) | `PosController::store`, `sp_create_order` |
| Bayar pesanan | `SELECT ... FOR UPDATE` mengunci baris agar tidak dibayar dua kali, lalu `UPDATE` diskon dan status dalam satu transaksi | `PosController::pay`, `sp_pay_order` |
| Batalkan pesanan | `DB::transaction` sebagai savepoint agar error trigger tidak merusak transaksi luar | `PosController::void`, `sp_cancel_order` |
| Pemotongan stok | Trigger `fn_consume_stock_on_ready` berjalan di transaksi yang sama dengan perubahan `order_status`; bila gagal, status ikut batal | trigger |
| Ubah resep | Hapus dan masukkan ulang `menu_ingredients` dalam satu transaksi | `MenuController` |
| Stok opname | `SELECT ... FOR UPDATE` pada bahan sebelum menghitung selisih | `fn_stock_adjust_to` |
| Prosedur | Satu `CALL` berjalan sebagai satu transaksi atomik | `sp_*` |

Contoh TCL (dapat diperagakan di psql):

```sql
BEGIN;
  CALL sp_create_order(4, '7', '[{"menu_id":1,"qty":2}]'::jsonb, NULL);
  SAVEPOINT setelah_buat;
  CALL sp_pay_order(currval('transactions_id_seq')::int, 'cash', 2);
  -- ada yang salah? kembali ke titik sebelum bayar:
  ROLLBACK TO SAVEPOINT setelah_buat;
COMMIT;      -- pesanan tersimpan, belum dibayar

BEGIN; DELETE FROM transactions; ROLLBACK;   -- ditolak trigger: transaksi tidak boleh dihapus
```

Tingkat isolasi memakai bawaan PostgreSQL (READ COMMITTED), cukup karena operasi kritis dijaga `FOR UPDATE` dan unique index. `idle_in_transaction_session_timeout = 60s` untuk `kafe_app` menutup transaksi yang menggantung.
