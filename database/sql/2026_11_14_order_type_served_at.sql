-- Perubahan skema Kafe Kaleng
--   1. transactions.order_type : 'dine_in' (makan di tempat, wajib nomor meja) | 'takeaway' (bawa pulang)
--   2. transactions.served_at / served_by : kapan & siapa pelayan yang mengonfirmasi pesanan diantar
--      (dasar halaman Riwayat pelayan)
--
-- Aman dijalankan berulang (idempotent). Untuk database hasil restore kafe_kaleng.sql:
--   php artisan migrate
-- atau manual:
--   psql --single-transaction -d kafe_kaleng -f database/sql/2026_11_14_order_type_served_at.sql

-- ---------------------------------------------------------------------------
-- 1. order_type
-- ---------------------------------------------------------------------------
ALTER TABLE transactions
    ADD COLUMN IF NOT EXISTS order_type character varying(10) NOT NULL DEFAULT 'dine_in';

-- Data lama: tanpa nomor meja berarti bawa pulang.
UPDATE transactions
   SET order_type = 'takeaway'
 WHERE order_type = 'dine_in'
   AND (table_no IS NULL OR btrim(table_no) = '');

-- Bawa pulang tidak punya nomor meja.
UPDATE transactions SET table_no = NULL WHERE order_type = 'takeaway' AND table_no IS NOT NULL;

ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_order_type_check;
ALTER TABLE transactions ADD CONSTRAINT transactions_order_type_check
    CHECK (order_type::text = ANY (ARRAY['dine_in', 'takeaway']::text[]));

-- Makan di tempat wajib punya nomor meja.
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_dine_in_needs_table;
ALTER TABLE transactions ADD CONSTRAINT transactions_dine_in_needs_table
    CHECK (order_type::text <> 'dine_in' OR (table_no IS NOT NULL AND btrim(table_no) <> ''));

-- ---------------------------------------------------------------------------
-- 2. served_at / served_by
-- ---------------------------------------------------------------------------
ALTER TABLE transactions
    ADD COLUMN IF NOT EXISTS served_at timestamp without time zone,
    ADD COLUMN IF NOT EXISTS served_by integer;

ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_served_by_fkey;
ALTER TABLE transactions ADD CONSTRAINT transactions_served_by_fkey
    FOREIGN KEY (served_by) REFERENCES users (id) ON DELETE SET NULL;

-- Pesanan yang sudah diantar sebelum kolom ini ada: pakai waktu transaksi.
UPDATE transactions SET served_at = transaction_date WHERE order_status = 'served' AND served_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_transactions_served_at ON transactions (served_at) WHERE order_status = 'served';
