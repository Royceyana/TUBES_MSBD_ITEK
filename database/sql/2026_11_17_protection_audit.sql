-- =====================================================================================
-- Kafe Kaleng | III.6 Trigger pengaman + III.7 Keamanan: audit log dan perlindungan data
-- Tujuan: data penting tidak bisa diubah/dihapus sembarangan, bahkan lewat SQL langsung.
-- Idempotent (CREATE OR REPLACE / DROP TRIGGER IF EXISTS).
-- =====================================================================================

-- ------------------------------ Tabel audit_logs --------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id            bigserial PRIMARY KEY,
    table_name    text        NOT NULL,
    record_id     text,
    action        text        NOT NULL CHECK (action IN ('INSERT', 'UPDATE', 'DELETE')),
    old_data      jsonb,
    new_data      jsonb,
    changed_columns text[],
    app_user_id   integer,
    db_user       text        NOT NULL DEFAULT session_user,
    client_addr   inet        DEFAULT inet_client_addr(),
    note          text,
    changed_at    timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_audit_table_record ON audit_logs (table_name, record_id);
CREATE INDEX IF NOT EXISTS idx_audit_changed_at   ON audit_logs (changed_at);

COMMENT ON TABLE  audit_logs IS 'Jejak perubahan data penting (append-only). Diisi trigger, tidak bisa diubah/dihapus.';
COMMENT ON COLUMN audit_logs.id IS 'Kunci utama log.';
COMMENT ON COLUMN audit_logs.table_name IS 'Tabel yang berubah.';
COMMENT ON COLUMN audit_logs.record_id IS 'Nilai id baris yang berubah.';
COMMENT ON COLUMN audit_logs.action IS 'INSERT, UPDATE, atau DELETE.';
COMMENT ON COLUMN audit_logs.old_data IS 'Data lama (UPDATE: hanya kolom yang berubah). Password tidak disimpan.';
COMMENT ON COLUMN audit_logs.new_data IS 'Data baru (UPDATE: hanya kolom yang berubah). Password tidak disimpan.';
COMMENT ON COLUMN audit_logs.changed_columns IS 'Daftar kolom yang berubah pada UPDATE.';
COMMENT ON COLUMN audit_logs.app_user_id IS 'users.id pelaku menurut aplikasi (app.user_id); kosong bila tidak diset.';
COMMENT ON COLUMN audit_logs.db_user IS 'Akun login database yang menjalankan perubahan.';
COMMENT ON COLUMN audit_logs.client_addr IS 'Alamat IP klien database.';
COMMENT ON COLUMN audit_logs.note IS 'Keterangan tambahan, mis. alasan pembatalan.';
COMMENT ON COLUMN audit_logs.changed_at IS 'Waktu perubahan.';

CREATE OR REPLACE VIEW v_audit_log AS
SELECT a.id, a.changed_at AS waktu, a.table_name AS tabel, a.record_id, a.action AS aksi,
       a.changed_columns AS kolom_berubah, a.old_data, a.new_data,
       u.name AS pelaku_aplikasi, a.db_user AS akun_database, a.note AS catatan
  FROM audit_logs a
  LEFT JOIN users u ON u.id = a.app_user_id;
COMMENT ON VIEW v_audit_log IS 'Audit log dengan nama pelaku aplikasi.';

-- SECURITY DEFINER: yang menulis log adalah pemilik tabel, jadi pengguna biasa tidak perlu
-- (dan tidak boleh) punya hak INSERT ke audit_logs; log tidak bisa dipalsukan langsung.
CREATE OR REPLACE FUNCTION fn_audit_row() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE
    v_old  jsonb;
    v_new  jsonb;
    v_cols text[];
    v_id   text;
BEGIN
    IF TG_OP <> 'INSERT' THEN v_old := to_jsonb(OLD); END IF;
    IF TG_OP <> 'DELETE' THEN v_new := to_jsonb(NEW); END IF;
    v_id := COALESCE(v_new->>'id', v_old->>'id');

    IF TG_OP = 'UPDATE' THEN
        SELECT array_agg(n.key ORDER BY n.key) INTO v_cols
          FROM jsonb_each(v_new) n WHERE v_old->n.key IS DISTINCT FROM n.value;
        IF v_cols IS NULL THEN
            RETURN NULL;                                   -- tidak ada perubahan nyata
        END IF;
        SELECT jsonb_object_agg(k, v_old->k), jsonb_object_agg(k, v_new->k) INTO v_old, v_new
          FROM unnest(v_cols) AS k;
    END IF;

    IF TG_TABLE_NAME = 'users' THEN                        -- jangan simpan hash kata sandi
        v_old := v_old - 'password' - 'remember_token';
        v_new := v_new - 'password' - 'remember_token';
    END IF;

    INSERT INTO audit_logs (table_name, record_id, action, old_data, new_data, changed_columns, app_user_id, note)
    VALUES (TG_TABLE_NAME, v_id, TG_OP, v_old, v_new, v_cols,
            NULLIF(current_setting('app.user_id', true), '')::integer,
            NULLIF(current_setting('app.audit_note', true), ''));
    RETURN NULL;
END;
$$;

-- Pasang trigger audit. Untuk UPDATE pada ingredients/transactions hanya perubahan bermakna yang dicatat
-- (stok dan total diturunkan otomatis; riwayatnya sudah ada di stock_movements dan rincian transaksi).
DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['users', 'categories', 'menus', 'menu_ingredients'] LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_audit_%1$s ON %1$I', t);
        EXECUTE format('CREATE TRIGGER trg_audit_%1$s AFTER INSERT OR UPDATE OR DELETE ON %1$I
                        FOR EACH ROW EXECUTE FUNCTION fn_audit_row()', t);
    END LOOP;
END $$;

DROP TRIGGER IF EXISTS trg_audit_ingredients_iud ON ingredients;
CREATE TRIGGER trg_audit_ingredients_iud AFTER INSERT OR DELETE ON ingredients
    FOR EACH ROW EXECUTE FUNCTION fn_audit_row();
DROP TRIGGER IF EXISTS trg_audit_ingredients_upd ON ingredients;
CREATE TRIGGER trg_audit_ingredients_upd AFTER UPDATE ON ingredients
    FOR EACH ROW WHEN ((OLD.name, OLD.unit, OLD.min_stock, OLD.cost_per_unit, OLD.is_active)
                       IS DISTINCT FROM (NEW.name, NEW.unit, NEW.min_stock, NEW.cost_per_unit, NEW.is_active))
    EXECUTE FUNCTION fn_audit_row();

DROP TRIGGER IF EXISTS trg_audit_transactions_id ON transactions;
CREATE TRIGGER trg_audit_transactions_id AFTER INSERT OR DELETE ON transactions
    FOR EACH ROW EXECUTE FUNCTION fn_audit_row();
DROP TRIGGER IF EXISTS trg_audit_transactions_upd ON transactions;
CREATE TRIGGER trg_audit_transactions_upd AFTER UPDATE ON transactions
    FOR EACH ROW WHEN ((OLD.status, OLD.order_status, OLD.payment_method, OLD.discount_type, OLD.discount_value, OLD.table_no)
                       IS DISTINCT FROM (NEW.status, NEW.order_status, NEW.payment_method, NEW.discount_type, NEW.discount_value, NEW.table_no))
    EXECUTE FUNCTION fn_audit_row();

-- ------------------------------ Data yang tidak boleh diubah --------------------------
CREATE OR REPLACE FUNCTION fn_block_modification() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION 'Tabel % bersifat append-only: % tidak diizinkan', TG_TABLE_NAME, TG_OP
        USING ERRCODE = '42501';
END;
$$;

CREATE OR REPLACE FUNCTION fn_block_truncate() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION 'TRUNCATE pada tabel % tidak diizinkan', TG_TABLE_NAME USING ERRCODE = '42501';
END;
$$;

-- Riwayat stok dan audit log: hanya boleh ditambah.
DROP TRIGGER IF EXISTS trg_sm_immutable ON stock_movements;
CREATE TRIGGER trg_sm_immutable BEFORE UPDATE OR DELETE ON stock_movements
    FOR EACH ROW EXECUTE FUNCTION fn_block_modification();
DROP TRIGGER IF EXISTS trg_audit_immutable ON audit_logs;
CREATE TRIGGER trg_audit_immutable BEFORE UPDATE OR DELETE ON audit_logs
    FOR EACH ROW EXECUTE FUNCTION fn_block_modification();

DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['users', 'categories', 'menus', 'menu_ingredients', 'ingredients',
                             'transactions', 'transaction_details', 'stock_movements', 'audit_logs'] LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_no_truncate ON %I', t);
        EXECUTE format('CREATE TRIGGER trg_no_truncate BEFORE TRUNCATE ON %I
                        FOR EACH STATEMENT EXECUTE FUNCTION fn_block_truncate()', t);
    END LOOP;
END $$;

-- ------------------------------ Stok bahan --------------------------------------------
-- ingredients.stock_qty hanya boleh diubah trigger fn_apply_stock_movement (kedalaman trigger >= 2).
-- UPDATE langsung (psql/aplikasi) ditolak; stok awal bahan baru selalu 0.
CREATE OR REPLACE FUNCTION fn_protect_ingredient_stock() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.stock_qty := 0;
        RETURN NEW;
    END IF;
    IF NEW.stock_qty IS DISTINCT FROM OLD.stock_qty AND pg_trigger_depth() < 2 THEN
        RAISE EXCEPTION 'Stok hanya boleh berubah lewat stock_movements (barang masuk, pemakaian, atau opname)'
            USING ERRCODE = '42501';
    END IF;
    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_ing_protect_stock_ins ON ingredients;
CREATE TRIGGER trg_ing_protect_stock_ins BEFORE INSERT ON ingredients
    FOR EACH ROW EXECUTE FUNCTION fn_protect_ingredient_stock();
DROP TRIGGER IF EXISTS trg_ing_protect_stock_upd ON ingredients;
CREATE TRIGGER trg_ing_protect_stock_upd BEFORE UPDATE OF stock_qty ON ingredients
    FOR EACH ROW EXECUTE FUNCTION fn_protect_ingredient_stock();

-- ------------------------------ Transaksi ---------------------------------------------
CREATE OR REPLACE FUNCTION fn_protect_transactions() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        RAISE EXCEPTION 'Transaksi tidak boleh dihapus; batalkan pesanan (status cancelled) agar riwayat tetap utuh'
            USING ERRCODE = '42501';
    END IF;

    IF TG_OP = 'INSERT' THEN
        IF NEW.status <> 'pending' OR NEW.order_status <> 'waiting' THEN
            RAISE EXCEPTION 'Pesanan baru harus berstatus pending dan waiting';
        END IF;
        IF NEW.subtotal <> 0 THEN
            RAISE EXCEPTION 'Subtotal diisi otomatis dari rincian pesanan, bukan dari input';
        END IF;
        RETURN NEW;
    END IF;

    -- UPDATE
    IF NEW.user_id IS DISTINCT FROM OLD.user_id OR NEW.transaction_date IS DISTINCT FROM OLD.transaction_date THEN
        RAISE EXCEPTION 'Pembuat dan waktu transaksi tidak dapat diubah' USING ERRCODE = '42501';
    END IF;

    IF OLD.status = 'completed' THEN
        IF NEW.status = 'pending' THEN
            RAISE EXCEPTION 'Pesanan lunas tidak dapat dikembalikan menjadi belum bayar';
        END IF;
        IF NEW.status = 'completed'
           AND (NEW.payment_method IS DISTINCT FROM OLD.payment_method OR NEW.table_no IS DISTINCT FROM OLD.table_no) THEN
            RAISE EXCEPTION 'Metode bayar dan meja pesanan lunas tidak dapat diubah';
        END IF;
    END IF;

    IF OLD.status = 'cancelled'
       AND (NEW.order_status, NEW.payment_method, NEW.table_no, NEW.discount_type, NEW.discount_value)
           IS DISTINCT FROM (OLD.order_status, OLD.payment_method, OLD.table_no, OLD.discount_type, OLD.discount_value) THEN
        RAISE EXCEPTION 'Pesanan yang sudah dibatalkan tidak dapat diubah lagi';
    END IF;

    IF OLD.status = 'pending' AND NEW.status = 'completed'
       AND NOT EXISTS (SELECT 1 FROM transaction_details WHERE transaction_id = NEW.id) THEN
        RAISE EXCEPTION 'Pesanan tanpa rincian tidak dapat dibayar';
    END IF;

    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_trx_protect ON transactions;
CREATE TRIGGER trg_trx_protect BEFORE INSERT OR UPDATE OR DELETE ON transactions
    FOR EACH ROW EXECUTE FUNCTION fn_protect_transactions();

-- subtotal harus selalu sama dengan jumlah rincian (total/diskon sudah dijaga CHECK).
CREATE OR REPLACE FUNCTION fn_verify_transaction_amounts() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE v_sum numeric;
BEGIN
    IF (NEW.subtotal, NEW.total, NEW.discount_amount) IS DISTINCT FROM (OLD.subtotal, OLD.total, OLD.discount_amount) THEN
        SELECT COALESCE(sum(subtotal), 0) INTO v_sum FROM transaction_details WHERE transaction_id = NEW.id;
        IF NEW.subtotal <> v_sum THEN
            RAISE EXCEPTION 'Subtotal transaksi % harus sama dengan jumlah rincian (%)', NEW.id, v_sum
                USING ERRCODE = '42501';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_trx_verify_amounts ON transactions;
CREATE TRIGGER trg_trx_verify_amounts BEFORE UPDATE ON transactions
    FOR EACH ROW EXECUTE FUNCTION fn_verify_transaction_amounts();

-- Rincian hanya boleh berubah saat pesanan masih pending; subtotal selalu = harga menu x qty.
CREATE OR REPLACE FUNCTION fn_protect_transaction_details() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE
    v_trx_status text;
    v_price      numeric;
    v_menu_state text;
BEGIN
    IF TG_OP <> 'INSERT' THEN
        SELECT status INTO v_trx_status FROM transactions WHERE id = OLD.transaction_id;
        IF v_trx_status IS NOT NULL AND v_trx_status <> 'pending' THEN
            RAISE EXCEPTION 'Rincian pesanan yang sudah dibayar atau dibatalkan tidak dapat diubah'
                USING ERRCODE = '42501';
        END IF;
    END IF;
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    SELECT status INTO v_trx_status FROM transactions WHERE id = NEW.transaction_id;
    IF v_trx_status IS DISTINCT FROM 'pending' THEN
        RAISE EXCEPTION 'Rincian hanya bisa ditambah pada pesanan yang belum dibayar' USING ERRCODE = '42501';
    END IF;
    IF TG_OP = 'UPDATE' AND NEW.transaction_id <> OLD.transaction_id THEN
        RAISE EXCEPTION 'Rincian tidak dapat dipindahkan ke transaksi lain' USING ERRCODE = '42501';
    END IF;

    SELECT price, status INTO v_price, v_menu_state FROM menus WHERE id = NEW.menu_id;
    IF (TG_OP = 'INSERT' OR NEW.menu_id <> OLD.menu_id) AND v_menu_state IS DISTINCT FROM 'active' THEN
        RAISE EXCEPTION 'Menu % tidak aktif dan tidak dapat dipesan', NEW.menu_id;
    END IF;
    NEW.subtotal := v_price * NEW.qty;
    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_td_protect ON transaction_details;
CREATE TRIGGER trg_td_protect BEFORE INSERT OR UPDATE OR DELETE ON transaction_details
    FOR EACH ROW EXECUTE FUNCTION fn_protect_transaction_details();

-- ------------------------------ Akun --------------------------------------------------
-- Sistem selalu harus punya minimal satu admin aktif (berlaku juga untuk SQL langsung).
CREATE OR REPLACE FUNCTION fn_protect_users() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    IF OLD.role = 'admin' AND OLD.status = 'active'
       AND (TG_OP = 'DELETE' OR NEW.role <> 'admin' OR NEW.status <> 'active') THEN
        IF NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin' AND status = 'active' AND id <> OLD.id) THEN
            RAISE EXCEPTION 'Harus tersisa minimal satu admin aktif';
        END IF;
    END IF;
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS trg_users_keep_admin ON users;
CREATE TRIGGER trg_users_keep_admin BEFORE UPDATE OF role, status OR DELETE ON users
    FOR EACH ROW EXECUTE FUNCTION fn_protect_users();

COMMENT ON FUNCTION fn_audit_row()                  IS 'Trigger audit: menulis perubahan baris ke audit_logs (password disamarkan).';
COMMENT ON FUNCTION fn_block_modification()         IS 'Trigger: menolak UPDATE/DELETE pada tabel append-only.';
COMMENT ON FUNCTION fn_block_truncate()             IS 'Trigger: menolak TRUNCATE pada tabel inti.';
COMMENT ON FUNCTION fn_protect_ingredient_stock()   IS 'Trigger: stok bahan hanya boleh berubah lewat stock_movements.';
COMMENT ON FUNCTION fn_protect_transactions()       IS 'Trigger: transaksi tidak boleh dihapus, pembuat/waktu/metode bayar terkunci.';
COMMENT ON FUNCTION fn_verify_transaction_amounts() IS 'Trigger: subtotal harus sama dengan jumlah rincian.';
COMMENT ON FUNCTION fn_protect_transaction_details() IS 'Trigger: rincian hanya bisa berubah saat pending; subtotal = harga menu x qty.';
COMMENT ON FUNCTION fn_protect_users()              IS 'Trigger: menjaga minimal satu admin aktif.';
