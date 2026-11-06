-- Perubahan skema Kafe Kaleng (Royce, PM + backend inti)
--   1. Role baru `waitress`
--   2. Status pesanan baru `served` (sudah diantar waitress)
--   3. Metode bayar baru `debit`
--   4. Diskon pada transaksi (subtotal, jenis, nilai, nominal)
--
-- Aman dijalankan berulang (idempotent). Untuk database yang sudah di-restore dari
-- kafe_kaleng.sql, jalankan lewat `php artisan migrate` atau:
--   psql --single-transaction -d kafe_kaleng -f database/sql/2026_11_06_waitress_served_debit_discount.sql


-- ---------------------------------------------------------------------------
-- 1. users: role waitress
-- ---------------------------------------------------------------------------
ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;
ALTER TABLE users ADD CONSTRAINT users_role_check
    CHECK (role::text = ANY (ARRAY['admin', 'kasir', 'owner', 'dapur', 'waitress']::text[]));

ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check;
ALTER TABLE users ADD CONSTRAINT users_status_check
    CHECK (status::text = ANY (ARRAY['active', 'inactive']::text[]));

-- Akun waitress contoh (memakai hash password yang sama dengan akun kasir).
INSERT INTO users (name, email, password, role, status)
SELECT 'Waitress 1', 'waitress@kafekaleng.test', k.password, 'waitress', 'active'
  FROM users k
 WHERE k.email = 'kasir@kafekaleng.test'
   AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'waitress@kafekaleng.test');


-- ---------------------------------------------------------------------------
-- 2 + 3. transactions: status `served` dan metode bayar `debit`
-- ---------------------------------------------------------------------------
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_order_status_check;
ALTER TABLE transactions ADD CONSTRAINT transactions_order_status_check
    CHECK (order_status::text = ANY (ARRAY['waiting', 'preparing', 'ready', 'served']::text[]));

ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_payment_method_check;
ALTER TABLE transactions ADD CONSTRAINT transactions_payment_method_check
    CHECK (payment_method::text = ANY (ARRAY['cash', 'qris', 'debit']::text[]));


-- ---------------------------------------------------------------------------
-- 4. transactions: diskon
--    total = subtotal - discount_amount; subtotal = jumlah transaction_details.subtotal
-- ---------------------------------------------------------------------------
ALTER TABLE transactions
    ADD COLUMN IF NOT EXISTS subtotal        numeric(12,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS discount_type   character varying(10) NOT NULL DEFAULT 'none',
    ADD COLUMN IF NOT EXISTS discount_value  numeric(12,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS discount_amount numeric(12,2) NOT NULL DEFAULT 0;

-- Transaksi lama belum punya diskon: subtotal = total.
UPDATE transactions SET subtotal = total WHERE subtotal = 0 AND total > 0;

ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_discount_type_check;
ALTER TABLE transactions ADD CONSTRAINT transactions_discount_type_check
    CHECK (discount_type::text = ANY (ARRAY['none', 'percent', 'amount']::text[]));

ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_discount_value_check;
ALTER TABLE transactions ADD CONSTRAINT transactions_discount_value_check
    CHECK (discount_value >= 0
           AND (discount_type <> 'percent' OR discount_value <= 100)
           AND (discount_type <> 'none' OR discount_value = 0));

ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_discount_amount_check;
ALTER TABLE transactions ADD CONSTRAINT transactions_discount_amount_check
    CHECK (discount_amount >= 0 AND discount_amount <= subtotal);

-- Nominal diskon (rupiah, dibulatkan) dari jenis dan nilainya; tidak pernah melebihi subtotal.
CREATE OR REPLACE FUNCTION fn_calc_discount(p_subtotal numeric, p_type text, p_value numeric)
RETURNS numeric
LANGUAGE sql IMMUTABLE
AS $$
    SELECT LEAST(
        GREATEST(COALESCE(p_subtotal, 0), 0),
        CASE p_type
            WHEN 'percent' THEN ROUND(COALESCE(p_subtotal, 0) * LEAST(p_value, 100) / 100, 0)
            WHEN 'amount'  THEN p_value
            ELSE 0
        END
    );
$$;

-- Jumlah detail berubah: hitung ulang subtotal, diskon, dan total.
CREATE OR REPLACE FUNCTION fn_sync_transaction_total() RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    v_id   integer;
    v_sub  numeric;
    v_type text;
    v_val  numeric;
    v_disc numeric;
BEGIN
    v_id := COALESCE(NEW.transaction_id, OLD.transaction_id);

    SELECT discount_type, discount_value INTO v_type, v_val
      FROM transactions WHERE id = v_id;
    IF NOT FOUND THEN
        RETURN NULL;   -- transaksi sedang dihapus (cascade)
    END IF;

    SELECT COALESCE(SUM(subtotal), 0) INTO v_sub
      FROM transaction_details WHERE transaction_id = v_id;

    v_disc := fn_calc_discount(v_sub, v_type, v_val);

    UPDATE transactions
       SET subtotal = v_sub, discount_amount = v_disc, total = v_sub - v_disc
     WHERE id = v_id;
    RETURN NULL;
END;
$$;

-- Jenis atau nilai diskon berubah: hitung ulang nominal diskon dan total.
CREATE OR REPLACE FUNCTION fn_apply_discount() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'UPDATE'
       AND OLD.status = 'completed'
       AND (NEW.discount_type, NEW.discount_value) IS DISTINCT FROM (OLD.discount_type, OLD.discount_value) THEN
        RAISE EXCEPTION 'Diskon tidak dapat diubah setelah pesanan dibayar';
    END IF;

    IF NEW.discount_type = 'none' THEN
        NEW.discount_value := 0;
    END IF;

    NEW.discount_amount := fn_calc_discount(NEW.subtotal, NEW.discount_type, NEW.discount_value);
    IF NEW.subtotal > 0 THEN
        NEW.total := NEW.subtotal - NEW.discount_amount;
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_trx_apply_discount ON transactions;
CREATE TRIGGER trg_trx_apply_discount
    BEFORE INSERT OR UPDATE OF discount_type, discount_value ON transactions
    FOR EACH ROW EXECUTE FUNCTION fn_apply_discount();


-- ---------------------------------------------------------------------------
-- Aturan pembatalan dan urutan status dapur / waitress
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_guard_cancel() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF OLD.status = 'cancelled' AND NEW.status <> 'cancelled' THEN
        RAISE EXCEPTION 'Pesanan yang sudah dibatalkan tidak dapat diubah lagi';
    END IF;
    IF NEW.status = 'cancelled' AND OLD.order_status = 'served' THEN
        RAISE EXCEPTION 'Pesanan sudah diantar ke pelanggan, tidak dapat dibatalkan';
    END IF;
    IF NEW.status = 'cancelled' AND OLD.status = 'completed' AND OLD.order_status = 'ready' THEN
        RAISE EXCEPTION 'Pesanan sudah dibayar dan sudah dikonfirmasi siap oleh dapur, tidak dapat dibatalkan';
    END IF;
    RETURN NEW;
END;
$$;

-- `served` hanya boleh setelah dapur menandai `ready` (supaya stok sudah terpotong),
-- dan pesanan yang sudah diantar tidak bisa mundur.
CREATE OR REPLACE FUNCTION fn_guard_order_status() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF OLD.order_status = 'served' AND NEW.order_status <> 'served' THEN
        RAISE EXCEPTION 'Pesanan yang sudah diantar tidak dapat diubah statusnya lagi';
    END IF;
    IF NEW.order_status = 'served' AND OLD.order_status NOT IN ('ready', 'served') THEN
        RAISE EXCEPTION 'Pesanan baru bisa ditandai diantar setelah dapur menandainya siap';
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_trx_guard_order_status ON transactions;
CREATE TRIGGER trg_trx_guard_order_status
    BEFORE UPDATE OF order_status ON transactions
    FOR EACH ROW EXECUTE FUNCTION fn_guard_order_status();
