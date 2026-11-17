-- =====================================================================================
-- Kafe Kaleng | III.5 View tambahan dan III.6 Fungsi + Prosedur tersimpan
-- Idempotent (CREATE OR REPLACE). Prosedur memakai SECURITY DEFINER dan memeriksa peran
-- pemanggil sendiri, jadi pengguna database biasa cukup diberi hak EXECUTE (lihat file roles).
-- =====================================================================================

-- ------------------------------ VIEW -------------------------------------------------
CREATE OR REPLACE VIEW v_sales_daily AS
SELECT t.transaction_date::date                                   AS tanggal,
       count(*)                                                   AS jumlah_transaksi,
       sum(t.subtotal)                                            AS subtotal,
       sum(t.discount_amount)                                     AS diskon,
       sum(t.total)                                               AS omzet,
       COALESCE(sum(t.total) FILTER (WHERE t.payment_method = 'cash'),  0) AS omzet_tunai,
       COALESCE(sum(t.total) FILTER (WHERE t.payment_method = 'qris'),  0) AS omzet_qris,
       COALESCE(sum(t.total) FILTER (WHERE t.payment_method = 'debit'), 0) AS omzet_debit
  FROM transactions t
 WHERE t.status = 'completed'
 GROUP BY t.transaction_date::date;

CREATE OR REPLACE VIEW v_sales_by_menu AS
SELECT m.id                                  AS menu_id,
       m.name,
       sum(d.qty)::int                       AS qty_terjual,
       sum(d.subtotal)                       AS pendapatan,
       sum(d.qty * COALESCE(h.hpp, 0))       AS hpp_total,
       sum(d.subtotal) - sum(d.qty * COALESCE(h.hpp, 0)) AS laba_kotor
  FROM transaction_details d
  JOIN transactions t ON t.id = d.transaction_id AND t.status = 'completed'
  JOIN menus m        ON m.id = d.menu_id
  LEFT JOIN v_menu_hpp h ON h.menu_id = m.id
 GROUP BY m.id, m.name;

CREATE OR REPLACE VIEW v_transaction_summary AS
SELECT t.id,
       'INV-' || lpad(t.id::text, 4, '0')  AS nomor,
       t.transaction_date                  AS tanggal,
       u.name                              AS petugas,
       t.table_no,
       t.status,
       t.order_status,
       t.payment_method,
       COALESCE(sum(d.qty), 0)::int        AS jumlah_item,
       t.subtotal,
       t.discount_amount                   AS diskon,
       t.total
  FROM transactions t
  LEFT JOIN users u ON u.id = t.user_id
  LEFT JOIN transaction_details d ON d.transaction_id = t.id
 GROUP BY t.id, u.name;

-- Antrian dapur/pelayan: pesanan yang belum dibatalkan dan belum diantar.
CREATE OR REPLACE VIEW v_order_queue AS
SELECT t.id,
       'INV-' || lpad(t.id::text, 4, '0')  AS nomor,
       t.table_no,
       t.status,
       t.order_status,
       t.transaction_date                  AS tanggal,
       floor(extract(epoch FROM (now() - t.transaction_date)) / 60)::int AS menit_menunggu,
       string_agg(m.name || ' x' || d.qty, ', ' ORDER BY m.name)        AS item
  FROM transactions t
  JOIN transaction_details d ON d.transaction_id = t.id
  JOIN menus m ON m.id = d.menu_id
 WHERE t.status <> 'cancelled' AND t.order_status <> 'served'
 GROUP BY t.id;

CREATE OR REPLACE VIEW v_stock_history AS
SELECT s.id,
       s.created_at                        AS waktu,
       i.name                              AS bahan,
       i.unit                              AS satuan,
       s.type                              AS jenis,
       s.qty,
       s.notes                             AS catatan,
       u.name                              AS petugas,
       CASE WHEN s.transaction_id IS NOT NULL
            THEN 'INV-' || lpad(s.transaction_id::text, 4, '0') END AS nomor_transaksi
  FROM stock_movements s
  JOIN ingredients i ON i.id = s.ingredient_id
  LEFT JOIN users u  ON u.id = s.user_id;

-- Akun tanpa kolom password/remember_token, aman diberikan ke pengguna pelaporan.
CREATE OR REPLACE VIEW v_user_public AS
SELECT id, name, email, role, status, created_at, updated_at FROM users;

-- ------------------------------ FUNGSI -----------------------------------------------
CREATE OR REPLACE FUNCTION fn_order_number(p_trx_id integer) RETURNS text
LANGUAGE sql IMMUTABLE AS $$ SELECT 'INV-' || lpad(p_trx_id::text, 4, '0') $$;

CREATE OR REPLACE FUNCTION fn_menu_portions(p_menu_id integer) RETURNS integer
LANGUAGE sql STABLE AS $$
    SELECT CASE WHEN jumlah_bahan = 0 THEN NULL ELSE GREATEST(porsi_tersedia, 0) END
      FROM v_menu_availability WHERE menu_id = p_menu_id
$$;

-- Laporan penjualan dan laba kotor per hari (HPP dihitung dari resep x biaya bahan saat ini).
-- SECURITY DEFINER: akun laporan boleh memanggilnya tanpa hak baca langsung ke tabel transactions.
CREATE OR REPLACE FUNCTION fn_sales_report(p_from date, p_to date)
RETURNS TABLE (tanggal date, jumlah_transaksi bigint, subtotal numeric, diskon numeric,
               omzet numeric, hpp numeric, laba_kotor numeric)
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public, pg_temp AS $$
    WITH trx AS (
        SELECT t.id, t.transaction_date::date AS tgl, t.subtotal, t.discount_amount, t.total
          FROM transactions t
         WHERE t.status = 'completed'
           AND t.transaction_date >= p_from
           AND t.transaction_date <  (p_to + 1)
    ), cogs AS (
        SELECT d.transaction_id, sum(d.qty * COALESCE(h.hpp, 0)) AS hpp
          FROM transaction_details d
          JOIN trx ON trx.id = d.transaction_id
          LEFT JOIN v_menu_hpp h ON h.menu_id = d.menu_id
         GROUP BY d.transaction_id
    )
    SELECT trx.tgl, count(*), sum(trx.subtotal), sum(trx.discount_amount), sum(trx.total),
           COALESCE(sum(cogs.hpp), 0), sum(trx.total) - COALESCE(sum(cogs.hpp), 0)
      FROM trx LEFT JOIN cogs ON cogs.transaction_id = trx.id
     GROUP BY trx.tgl
     ORDER BY trx.tgl
$$;

-- Pemeriksa peran: dipakai semua prosedur. Pemanggil harus akun aktif dengan peran yang diizinkan.
CREATE OR REPLACE FUNCTION fn_assert_role(p_user_id integer, p_roles text[]) RETURNS void
LANGUAGE plpgsql STABLE AS $$
DECLARE v_role text; v_status text;
BEGIN
    SELECT role, status INTO v_role, v_status FROM users WHERE id = p_user_id;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Pengguna % tidak ditemukan', p_user_id USING ERRCODE = '42501';
    END IF;
    IF v_status <> 'active' THEN
        RAISE EXCEPTION 'Akun % tidak aktif', p_user_id USING ERRCODE = '42501';
    END IF;
    IF NOT (v_role = ANY (p_roles)) THEN
        RAISE EXCEPTION 'Peran "%" tidak berwenang untuk operasi ini', v_role USING ERRCODE = '42501';
    END IF;
END;
$$;

-- ------------------------------ PROSEDUR (satu CALL = satu transaksi atomik) ----------
-- Membuat pesanan baru. p_items contoh: [{"menu_id":1,"qty":2},{"menu_id":5,"qty":1}]
CREATE OR REPLACE PROCEDURE sp_create_order(p_user_id integer, p_table_no text, p_items jsonb,
                                            INOUT p_trx_id integer DEFAULT NULL)
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE
    r        record;
    v_avail  boolean;
    v_porsi  integer;
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['kasir', 'waitress', 'admin']);
    IF p_items IS NULL OR jsonb_typeof(p_items) <> 'array' OR jsonb_array_length(p_items) = 0 THEN
        RAISE EXCEPTION 'Pesanan harus berisi minimal satu menu';
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);

    INSERT INTO transactions (user_id, table_no, status, order_status, transaction_date)
    VALUES (p_user_id, NULLIF(btrim(p_table_no), ''), 'pending', 'waiting', now())
    RETURNING id INTO p_trx_id;

    FOR r IN
        SELECT (i->>'menu_id')::int AS menu_id, sum((i->>'qty')::int)::int AS qty
          FROM jsonb_array_elements(p_items) AS i
         GROUP BY 1
         ORDER BY 1
    LOOP
        IF r.qty < 1 OR r.qty > 99 THEN
            RAISE EXCEPTION 'Jumlah untuk menu % harus 1-99', r.menu_id;
        END IF;

        SELECT is_available, porsi_tersedia INTO v_avail, v_porsi
          FROM v_menu_availability WHERE menu_id = r.menu_id;
        IF NOT FOUND OR NOT v_avail THEN
            RAISE EXCEPTION 'Menu % tidak tersedia', r.menu_id;
        END IF;
        IF v_porsi IS NOT NULL AND v_porsi < r.qty THEN
            RAISE EXCEPTION 'Stok bahan menu % tidak cukup (tersisa % porsi)', r.menu_id, v_porsi;
        END IF;

        -- subtotal dihitung ulang oleh trigger dari harga menu; nilai 0 hanya pengisi.
        INSERT INTO transaction_details (transaction_id, menu_id, qty, subtotal)
        VALUES (p_trx_id, r.menu_id, r.qty, 0);
    END LOOP;
END;
$$;

-- Membayar pesanan (pending -> completed), diskon opsional.
CREATE OR REPLACE PROCEDURE sp_pay_order(p_trx_id integer, p_method text, p_user_id integer,
                                         p_discount_type text DEFAULT 'none',
                                         p_discount_value numeric DEFAULT 0)
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE v_status text;
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['kasir', 'admin']);
    IF p_method NOT IN ('cash', 'qris', 'debit') THEN
        RAISE EXCEPTION 'Metode bayar tidak dikenal: %', p_method;
    END IF;

    SELECT status INTO v_status FROM transactions WHERE id = p_trx_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Transaksi % tidak ditemukan', p_trx_id;
    END IF;
    IF v_status <> 'pending' THEN
        RAISE EXCEPTION 'Pesanan ini sudah dibayar atau dibatalkan';
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);
    UPDATE transactions
       SET discount_type  = COALESCE(p_discount_type, 'none'),
           discount_value = CASE WHEN COALESCE(p_discount_type, 'none') = 'none' THEN 0
                                 ELSE COALESCE(p_discount_value, 0) END
     WHERE id = p_trx_id;
    UPDATE transactions SET status = 'completed', payment_method = p_method WHERE id = p_trx_id;
END;
$$;

-- Membatalkan pesanan; aturan "sudah diantar/siap tidak boleh batal" dijaga trigger.
CREATE OR REPLACE PROCEDURE sp_cancel_order(p_trx_id integer, p_user_id integer, p_reason text DEFAULT NULL)
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE v_status text;
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['kasir', 'admin']);

    SELECT status INTO v_status FROM transactions WHERE id = p_trx_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Transaksi % tidak ditemukan', p_trx_id;
    END IF;
    IF v_status = 'cancelled' THEN
        RAISE EXCEPTION 'Pesanan ini sudah dibatalkan';
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);
    PERFORM set_config('app.audit_note', COALESCE(p_reason, ''), true);
    UPDATE transactions SET status = 'cancelled' WHERE id = p_trx_id;
END;
$$;

-- Memajukan tahap pesanan satu langkah: waiting -> preparing -> ready -> served.
-- Dapur: preparing/ready (ready memotong stok lewat trigger). Pelayan: served. Admin: semuanya.
CREATE OR REPLACE PROCEDURE sp_set_order_status(p_trx_id integer, p_new_status text, p_user_id integer)
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
DECLARE
    v_status text;
    v_order  text;
    v_steps  text[] := ARRAY['waiting', 'preparing', 'ready', 'served'];
BEGIN
    IF p_new_status = 'served' THEN
        PERFORM fn_assert_role(p_user_id, ARRAY['waitress', 'admin']);
    ELSIF p_new_status IN ('preparing', 'ready') THEN
        PERFORM fn_assert_role(p_user_id, ARRAY['dapur', 'admin']);
    ELSE
        RAISE EXCEPTION 'Status pesanan tidak valid: %', p_new_status;
    END IF;

    SELECT status, order_status INTO v_status, v_order FROM transactions WHERE id = p_trx_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Transaksi % tidak ditemukan', p_trx_id;
    END IF;
    IF v_status = 'cancelled' THEN
        RAISE EXCEPTION 'Pesanan sudah dibatalkan';
    END IF;
    IF array_position(v_steps, p_new_status) <> array_position(v_steps, v_order) + 1 THEN
        RAISE EXCEPTION 'Pesanan berstatus % tidak bisa langsung menjadi %', v_order, p_new_status;
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);
    UPDATE transactions SET order_status = p_new_status WHERE id = p_trx_id;
END;
$$;

-- Barang masuk (restock).
CREATE OR REPLACE PROCEDURE sp_restock(p_ingredient_id integer, p_qty numeric, p_user_id integer,
                                       p_notes text DEFAULT 'Restock')
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['admin', 'dapur']);
    IF p_qty IS NULL OR p_qty <= 0 THEN
        RAISE EXCEPTION 'Jumlah barang masuk harus lebih dari 0';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM ingredients WHERE id = p_ingredient_id AND is_active) THEN
        RAISE EXCEPTION 'Bahan % tidak ditemukan atau tidak aktif', p_ingredient_id;
    END IF;

    INSERT INTO stock_movements (ingredient_id, user_id, type, qty, notes)
    VALUES (p_ingredient_id, p_user_id, 'in', p_qty, p_notes);
END;
$$;

-- Stok opname: menyamakan stok sistem dengan stok fisik; p_delta menerima selisih yang dicatat.
CREATE OR REPLACE PROCEDURE sp_stock_opname(p_ingredient_id integer, p_actual_qty numeric, p_user_id integer,
                                            p_notes text DEFAULT 'Stok opname', INOUT p_delta numeric DEFAULT NULL)
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public, pg_temp AS $$
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['admin', 'dapur']);
    p_delta := fn_stock_adjust_to(p_ingredient_id, p_actual_qty, p_user_id, p_notes);
END;
$$;

COMMENT ON VIEW v_sales_daily        IS 'Rekap penjualan lunas per hari, termasuk omzet per metode bayar.';
COMMENT ON VIEW v_sales_by_menu      IS 'Penjualan lunas per menu: qty, pendapatan, HPP, laba kotor.';
COMMENT ON VIEW v_transaction_summary IS 'Ringkasan satu baris per transaksi dengan nama petugas dan jumlah item.';
COMMENT ON VIEW v_order_queue        IS 'Antrian pesanan aktif (belum batal/diantar) beserta daftar item dan lama menunggu.';
COMMENT ON VIEW v_stock_history      IS 'Riwayat pergerakan stok dengan nama bahan dan petugas.';
COMMENT ON VIEW v_user_public        IS 'Data akun tanpa password dan remember_token.';
COMMENT ON FUNCTION fn_order_number(integer)          IS 'Nomor tampil transaksi, mis. INV-0042.';
COMMENT ON FUNCTION fn_menu_portions(integer)         IS 'Porsi menu yang masih bisa dibuat dari stok (NULL bila tanpa resep).';
COMMENT ON FUNCTION fn_sales_report(date, date)       IS 'Laporan penjualan, HPP, dan laba kotor per hari pada rentang tanggal.';
COMMENT ON FUNCTION fn_assert_role(integer, text[])   IS 'Menolak operasi bila akun tidak aktif atau perannya tidak diizinkan.';
COMMENT ON PROCEDURE sp_create_order(integer, text, jsonb, integer) IS 'Membuat pesanan beserta rincian, memeriksa menu aktif dan stok, atomik.';
COMMENT ON PROCEDURE sp_pay_order(integer, text, integer, text, numeric) IS 'Membayar pesanan pending dengan diskon opsional.';
COMMENT ON PROCEDURE sp_cancel_order(integer, integer, text)         IS 'Membatalkan pesanan dan mencatat alasan di audit_logs.';
COMMENT ON PROCEDURE sp_set_order_status(integer, text, integer)     IS 'Memajukan tahap pesanan sesuai peran (dapur/pelayan/admin).';
COMMENT ON PROCEDURE sp_restock(integer, numeric, integer, text)     IS 'Mencatat barang masuk.';
COMMENT ON PROCEDURE sp_stock_opname(integer, numeric, integer, text, numeric) IS 'Menyamakan stok sistem dengan stok fisik.';

-- Keterangan objek lama (dipakai dokumentasi III.5/III.6)
COMMENT ON VIEW v_ingredient_stock    IS 'Stok bahan aktif beserta status aman/menipis/habis.';
COMMENT ON VIEW v_menu_availability   IS 'Ketersediaan menu: porsi yang bisa dibuat dari stok bahan.';
COMMENT ON VIEW v_menu_hpp            IS 'Harga pokok penjualan (HPP), margin, dan persen margin per menu dari resep.';
COMMENT ON FUNCTION fn_calc_discount(numeric, text, numeric)            IS 'Menghitung potongan harga (none/percent/amount), tidak melebihi subtotal.';
COMMENT ON FUNCTION fn_stock_adjust_to(integer, numeric, integer, text) IS 'Stok opname: menyamakan stok sistem dengan stok fisik lewat stock_movements adjustment.';
COMMENT ON FUNCTION fn_apply_discount()          IS 'Trigger: menghitung discount_amount dan total; diskon terkunci setelah lunas.';
COMMENT ON FUNCTION fn_apply_stock_movement()    IS 'Trigger: menambah/mengurangi ingredients.stock_qty dari stock_movements.';
COMMENT ON FUNCTION fn_consume_stock_on_ready()  IS 'Trigger: memotong stok bahan sesuai resep saat order_status menjadi ready (idempotent).';
COMMENT ON FUNCTION fn_guard_cancel()            IS 'Trigger: aturan pembatalan pesanan (sudah diantar/siap dan lunas tidak bisa batal).';
COMMENT ON FUNCTION fn_guard_order_status()      IS 'Trigger: urutan tahap pesanan; served hanya setelah ready dan tidak bisa mundur.';
COMMENT ON FUNCTION fn_sync_transaction_total()  IS 'Trigger: menghitung ulang subtotal, diskon, dan total dari transaction_details.';
