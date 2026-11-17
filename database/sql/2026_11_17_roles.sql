-- =====================================================================================
-- Kafe Kaleng | III.7 Manajemen user dan hak akses database (prinsip hak minimum)
-- DIJALANKAN MANUAL oleh pemilik database (mis. postgres), BUKAN oleh Laravel/migrasi:
--   psql -U postgres -d kafe_kaleng -f database/sql/2026_11_17_roles.sql
-- Jalankan setelah `php artisan migrate` (dan ulangi setelah `migrate:fresh`, karena hak ikut terhapus
-- bersama tabel). Idempotent: hak lama direset lalu diberikan ulang.
-- Kata sandi TIDAK diatur di sini; atur manual (lihat 2026_11_17_roles_passwords.sql.example).
--
--   Akun login (dipakai program)          Group role (diberikan ke akun SQL manual)
--   kafe_app      aplikasi Laravel        kafe_role_admin, kafe_role_kasir, kafe_role_dapur,
--   kafe_report   laporan baca-saja       kafe_role_waitress, kafe_role_owner
--   kafe_auditor  pemeriksa audit log     contoh: CREATE ROLE budi LOGIN IN ROLE kafe_role_kasir;
--   Akun per peran (akses langsung lewat SQL client, hak diwarisi dari group role-nya):
--   owner_kafe, admin_kafe, staff_kasir, staff_chef, staff_waitress
-- =====================================================================================

-- ---- 1. Buat role bila belum ada ------------------------------------------------------
DO $$
DECLARE r text;
BEGIN
    FOREACH r IN ARRAY ARRAY['kafe_app', 'kafe_report', 'kafe_auditor'] LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = r) THEN
            EXECUTE format('CREATE ROLE %I LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION', r);
        END IF;
    END LOOP;
    FOREACH r IN ARRAY ARRAY['kafe_role_admin', 'kafe_role_kasir', 'kafe_role_dapur', 'kafe_role_waitress', 'kafe_role_owner',
                             'kafe_web_admin', 'kafe_web_kasir', 'kafe_web_dapur', 'kafe_web_waitress', 'kafe_web_owner'] LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = r) THEN
            EXECUTE format('CREATE ROLE %I NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION', r);
        END IF;
    END LOOP;
END $$;

COMMENT ON ROLE kafe_app          IS 'Akun runtime aplikasi Laravel: DML terbatas, tanpa DDL, tanpa DELETE data transaksi.';
COMMENT ON ROLE kafe_report       IS 'Akun laporan: hanya SELECT pada view laporan (tanpa data akun).';
COMMENT ON ROLE kafe_auditor      IS 'Akun pemeriksa: hanya membaca audit log dan riwayat stok.';
COMMENT ON ROLE kafe_role_admin   IS 'Group role admin: semua prosedur + view master/laporan.';
COMMENT ON ROLE kafe_role_kasir   IS 'Group role kasir: buat/bayar/batal pesanan lewat prosedur.';
COMMENT ON ROLE kafe_role_dapur   IS 'Group role dapur: ubah tahap pesanan, opname dan restock.';
COMMENT ON ROLE kafe_role_waitress IS 'Group role waitress: buat pesanan dan tandai diantar.';
COMMENT ON ROLE kafe_role_owner   IS 'Group role owner: laporan penjualan dan stok (baca-saja).';
COMMENT ON ROLE kafe_web_admin    IS 'Konteks SET ROLE aplikasi untuk pengguna admin (hak tabel sesuai fitur admin).';
COMMENT ON ROLE kafe_web_kasir    IS 'Konteks SET ROLE aplikasi untuk kasir: buat/bayar/batal pesanan.';
COMMENT ON ROLE kafe_web_dapur    IS 'Konteks SET ROLE aplikasi untuk dapur: ubah tahap pesanan, opname stok.';
COMMENT ON ROLE kafe_web_waitress IS 'Konteks SET ROLE aplikasi untuk waitress: tandai pesanan diantar.';
COMMENT ON ROLE kafe_web_owner    IS 'Konteks SET ROLE aplikasi untuk owner: laporan baca-saja.';

-- ---- 2. Tutup akses bawaan -------------------------------------------------------------
DO $$
BEGIN
    EXECUTE format('REVOKE ALL ON DATABASE %I FROM PUBLIC', current_database());
    EXECUTE format('GRANT CONNECT ON DATABASE %I TO kafe_app, kafe_report, kafe_auditor, kafe_role_admin, '
                   'kafe_role_kasir, kafe_role_dapur, kafe_role_waitress, kafe_role_owner', current_database());
END $$;

REVOKE CREATE ON SCHEMA public FROM PUBLIC;
GRANT USAGE ON SCHEMA public TO kafe_app, kafe_report, kafe_auditor, kafe_role_admin, kafe_role_kasir,
                                 kafe_role_dapur, kafe_role_waitress, kafe_role_owner,
                                 kafe_web_admin, kafe_web_kasir, kafe_web_dapur, kafe_web_waitress, kafe_web_owner;

-- Prosedur dan fungsi sensitif tidak boleh dipanggil sembarang pengguna.
DO $$
DECLARE r record;
BEGIN
    FOR r IN SELECT p.oid::regprocedure AS sig
               FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
              WHERE n.nspname = 'public'
                AND (p.proname LIKE 'sp\_%' OR p.proname IN ('fn_assert_role', 'fn_stock_adjust_to'))
    LOOP
        EXECUTE format('REVOKE ALL ON ROUTINE %s FROM PUBLIC', r.sig);
    END LOOP;
END $$;

-- ---- 3. Reset hak lama ---------------------------------------------------------------
REVOKE ALL ON ALL TABLES    IN SCHEMA public FROM kafe_app, kafe_report, kafe_auditor, kafe_role_admin,
    kafe_role_kasir, kafe_role_dapur, kafe_role_waitress, kafe_role_owner, kafe_web_admin, kafe_web_kasir, kafe_web_dapur, kafe_web_waitress, kafe_web_owner;
REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM kafe_app, kafe_report, kafe_auditor, kafe_role_admin,
    kafe_role_kasir, kafe_role_dapur, kafe_role_waitress, kafe_role_owner, kafe_web_admin, kafe_web_kasir, kafe_web_dapur, kafe_web_waitress, kafe_web_owner;
-- Kolom: REVOKE ALL ON TABLE di atas ikut mencabut hak tingkat kolom.

-- ---- 4. kafe_app: runtime aplikasi ------------------------------------------------------
-- Data master: boleh tambah/ubah (hapus hanya untuk yang memang dihapus aplikasi).
GRANT SELECT, INSERT, UPDATE, DELETE ON categories, menus, menu_ingredients TO kafe_app;
GRANT SELECT, INSERT, UPDATE         ON ingredients, users TO kafe_app;   -- bahan dan akun dinonaktifkan, tidak dihapus
-- Data transaksi: tidak ada DELETE/TRUNCATE; rincian dan riwayat stok hanya bisa ditambah.
GRANT SELECT, INSERT, UPDATE         ON transactions TO kafe_app;
GRANT SELECT, INSERT                 ON transaction_details, stock_movements TO kafe_app;
-- Infrastruktur Laravel (sesi, cache, antrian).
GRANT SELECT, INSERT, UPDATE, DELETE ON sessions, cache, cache_locks, jobs, job_batches, failed_jobs TO kafe_app;
GRANT SELECT                         ON migrations TO kafe_app;
-- audit_logs tidak diberi hak tulis; log hanya terbaca lewat view.
GRANT SELECT ON v_ingredient_stock, v_menu_availability, v_menu_hpp, v_sales_daily, v_sales_by_menu,
                v_transaction_summary, v_order_queue, v_stock_history, v_user_public, v_audit_log TO kafe_app;

-- UPDATE pada sequence diperlukan setval() milik seeder (SyncsSequence); sequence audit_logs tetap tertutup.
GRANT USAGE, SELECT, UPDATE ON ALL SEQUENCES IN SCHEMA public TO kafe_app;
REVOKE ALL ON SEQUENCE audit_logs_id_seq FROM kafe_app;

GRANT EXECUTE ON FUNCTION fn_stock_adjust_to(integer, numeric, integer, text) TO kafe_app;
GRANT EXECUTE ON FUNCTION fn_sales_report(date, date), fn_menu_portions(integer), fn_order_number(integer) TO kafe_app;
GRANT EXECUTE ON PROCEDURE sp_create_order(integer, text, jsonb, integer),
                           sp_pay_order(integer, text, integer, text, numeric),
                           sp_cancel_order(integer, integer, text),
                           sp_set_order_status(integer, text, integer),
                           sp_restock(integer, numeric, integer, text),
                           sp_stock_opname(integer, numeric, integer, text, numeric) TO kafe_app;

-- ---- 5. kafe_report dan kafe_auditor --------------------------------------------------
GRANT SELECT ON v_sales_daily, v_sales_by_menu, v_menu_hpp, v_ingredient_stock, v_menu_availability,
                v_transaction_summary TO kafe_report;
GRANT EXECUTE ON FUNCTION fn_sales_report(date, date) TO kafe_report;

GRANT SELECT ON audit_logs, v_audit_log, v_stock_history, v_user_public TO kafe_auditor;

-- ---- 6. Group role per peran aplikasi (hanya lewat view dan prosedur) -----------------
GRANT SELECT ON v_menu_availability, v_order_queue, v_transaction_summary TO kafe_role_kasir;
GRANT EXECUTE ON PROCEDURE sp_create_order(integer, text, jsonb, integer),
                           sp_pay_order(integer, text, integer, text, numeric),
                           sp_cancel_order(integer, integer, text) TO kafe_role_kasir;

GRANT SELECT ON v_menu_availability, v_order_queue TO kafe_role_waitress;
GRANT EXECUTE ON PROCEDURE sp_create_order(integer, text, jsonb, integer),
                           sp_set_order_status(integer, text, integer) TO kafe_role_waitress;

GRANT SELECT ON v_order_queue, v_ingredient_stock, v_menu_availability, v_stock_history TO kafe_role_dapur;
GRANT EXECUTE ON PROCEDURE sp_set_order_status(integer, text, integer),
                           sp_restock(integer, numeric, integer, text),
                           sp_stock_opname(integer, numeric, integer, text, numeric) TO kafe_role_dapur;

GRANT SELECT ON v_sales_daily, v_sales_by_menu, v_menu_hpp, v_ingredient_stock, v_menu_availability,
                v_transaction_summary, v_order_queue, v_stock_history, v_user_public, v_audit_log TO kafe_role_admin;
GRANT EXECUTE ON FUNCTION fn_sales_report(date, date) TO kafe_role_admin, kafe_role_owner;
GRANT EXECUTE ON PROCEDURE sp_create_order(integer, text, jsonb, integer),
                           sp_pay_order(integer, text, integer, text, numeric),
                           sp_cancel_order(integer, integer, text),
                           sp_set_order_status(integer, text, integer),
                           sp_restock(integer, numeric, integer, text),
                           sp_stock_opname(integer, numeric, integer, text, numeric) TO kafe_role_admin;

GRANT SELECT ON v_sales_daily, v_sales_by_menu, v_menu_hpp, v_ingredient_stock,
                v_transaction_summary TO kafe_role_owner;

-- ---- 7. Batas penggunaan ---------------------------------------------------------------
ALTER ROLE kafe_app     SET search_path = public;
ALTER ROLE kafe_report  SET search_path = public;
ALTER ROLE kafe_auditor SET search_path = public;
ALTER ROLE kafe_app     SET statement_timeout = '30s';
ALTER ROLE kafe_app     SET idle_in_transaction_session_timeout = '60s';
ALTER ROLE kafe_report  SET statement_timeout = '15s';
ALTER ROLE kafe_auditor SET statement_timeout = '15s';
ALTER ROLE kafe_report  CONNECTION LIMIT 5;
ALTER ROLE kafe_auditor CONNECTION LIMIT 3;

-- ---- 8. Akun login per peran (anggota group role) -----------------------------------------
DO $$
DECLARE r record;
BEGIN
    FOR r IN SELECT * FROM (VALUES
        ('owner_kafe',     'kafe_role_owner'),
        ('admin_kafe',     'kafe_role_admin'),
        ('staff_kasir',    'kafe_role_kasir'),
        ('staff_chef',     'kafe_role_dapur'),
        ('staff_waitress', 'kafe_role_waitress')) AS v(akun, grp)
    LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = r.akun) THEN
            EXECUTE format('CREATE ROLE %I LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION', r.akun);
        END IF;
        EXECUTE format('GRANT %I TO %I', r.grp, r.akun);
        EXECUTE format('ALTER ROLE %I SET search_path = public', r.akun);
        EXECUTE format('ALTER ROLE %I SET statement_timeout = ''30s''', r.akun);
        EXECUTE format('ALTER ROLE %I CONNECTION LIMIT 5', r.akun);
        EXECUTE format('COMMENT ON ROLE %I IS %L', r.akun, 'Akun login peran aplikasi, anggota ' || r.grp);
    END LOOP;
END $$;

-- ---- 9. Role konteks aplikasi untuk SET ROLE (opsional: DB_SET_ROLE=true di .env) --------
-- kafe_app boleh "menjadi" kafe_web_<peran>; saat SET ROLE aktif, yang berlaku hanya hak peran itu.
-- Hak dihitung dari kode controller: tiap peran hanya diberi tabel/kolom yang benar-benar dipakai fiturnya.
-- Fungsi trigger lintas tabel berjalan sebagai pemilik (set_role_support.sql), jadi peran web tidak perlu
-- hak tulis ke ingredients/stock_movements/audit_logs.
GRANT kafe_web_admin, kafe_web_kasir, kafe_web_dapur, kafe_web_waitress, kafe_web_owner TO kafe_app;
ALTER ROLE kafe_app NOINHERIT;      -- hak peran web tidak otomatis menyatu ke kafe_app; hanya lewat SET ROLE

-- Semua peran: logout (Auth::logout memperbarui remember_token).
GRANT SELECT (id), UPDATE (remember_token, updated_at) ON users
    TO kafe_web_admin, kafe_web_kasir, kafe_web_dapur, kafe_web_waitress, kafe_web_owner;

-- Kasir (PosController)
GRANT SELECT ON categories, menus, transactions, transaction_details, users, v_menu_availability TO kafe_web_kasir;
GRANT INSERT ON transactions, transaction_details TO kafe_web_kasir;
GRANT UPDATE (status, payment_method, discount_type, discount_value) ON transactions TO kafe_web_kasir;

-- Dapur (DapurController, StockService)
GRANT SELECT ON transactions, transaction_details, menus, categories, menu_ingredients, ingredients,
                v_ingredient_stock, v_menu_availability TO kafe_web_dapur;
GRANT UPDATE (order_status) ON transactions TO kafe_web_dapur;
GRANT EXECUTE ON FUNCTION fn_stock_adjust_to(integer, numeric, integer, text) TO kafe_web_dapur, kafe_web_admin;

-- Waitress (WaitressController)
GRANT SELECT ON transactions, transaction_details, menus TO kafe_web_waitress;
GRANT UPDATE (order_status) ON transactions TO kafe_web_waitress;

-- Owner (OwnerController): baca-saja
GRANT SELECT ON transactions, transaction_details, menus, stock_movements,
                v_ingredient_stock, v_menu_hpp, v_menu_availability, v_sales_daily, v_sales_by_menu,
                v_transaction_summary TO kafe_web_owner;

-- Admin (Admin\*Controller): kelola master data, akun, dan stok; tidak boleh mengubah/menghapus transaksi.
GRANT SELECT, INSERT, UPDATE, DELETE ON categories, menus TO kafe_web_admin;
GRANT SELECT, INSERT, DELETE         ON menu_ingredients TO kafe_web_admin;
GRANT SELECT, INSERT, UPDATE         ON ingredients, users TO kafe_web_admin;
GRANT SELECT                         ON transactions, transaction_details TO kafe_web_admin;
GRANT SELECT, INSERT                 ON stock_movements TO kafe_web_admin;
GRANT SELECT ON v_ingredient_stock, v_menu_hpp, v_menu_availability, v_stock_history, v_sales_daily,
                v_sales_by_menu, v_transaction_summary, v_user_public, v_audit_log TO kafe_web_admin;

-- Sequence untuk INSERT (id otomatis).
DO $$
DECLARE r record;
BEGIN
    FOR r IN SELECT * FROM (VALUES
        ('transactions', 'kafe_web_kasir'), ('transaction_details', 'kafe_web_kasir'),
        ('categories', 'kafe_web_admin'), ('menus', 'kafe_web_admin'), ('menu_ingredients', 'kafe_web_admin'),
        ('ingredients', 'kafe_web_admin'), ('users', 'kafe_web_admin'), ('stock_movements', 'kafe_web_admin')
    ) AS v(tbl, rl)
    LOOP
        EXECUTE format('GRANT USAGE ON SEQUENCE %s TO %I', pg_get_serial_sequence('public.' || r.tbl, 'id'), r.rl);
    END LOOP;
END $$;
