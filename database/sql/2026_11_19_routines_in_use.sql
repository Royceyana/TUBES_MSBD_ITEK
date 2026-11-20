-- =====================================================================================
-- Kafe Kaleng | III.6 function dan prosedur yang dipakai langsung oleh aplikasi
--   fn_order_number     -> kolom nomor di v_order_queue, v_transaction_summary, v_transaction_history
--   sp_set_order_status -> DapurController::advance dan WaitressController::serve
--                          (sekarang juga mengisi served_at dan served_by saat status served)
-- Dijalankan oleh migrasi 2026_11_19_000001_use_routines_in_app (jalankan sebagai pemilik tabel).
-- =====================================================================================

CREATE OR REPLACE VIEW v_order_queue AS
SELECT t.id,
       fn_order_number(t.id)               AS nomor,
       t.table_no,
       t.status,
       t.order_status,
       t.transaction_date                  AS tanggal,
       floor(extract(epoch FROM (now() - t.transaction_date)) / 60)::int AS menit_menunggu,
       string_agg(m.name || ' x' || d.qty, ', ' ORDER BY m.name)        AS item,
       t.order_type
  FROM transactions t
  JOIN transaction_details d ON d.transaction_id = t.id
  JOIN menus m ON m.id = d.menu_id
 WHERE t.status <> 'cancelled' AND t.order_status <> 'served'
 GROUP BY t.id;

CREATE OR REPLACE VIEW v_transaction_summary AS
SELECT t.id,
       fn_order_number(t.id)               AS nomor,
       t.transaction_date                  AS tanggal,
       u.name                              AS petugas,
       t.table_no,
       t.status,
       t.order_status,
       t.payment_method,
       COALESCE(sum(d.qty), 0)::int        AS jumlah_item,
       t.subtotal,
       t.discount_amount                   AS diskon,
       t.total,
       t.order_type,
       t.served_at,
       sv.name                             AS pelayan
  FROM transactions t
  LEFT JOIN users u  ON u.id  = t.user_id
  LEFT JOIN users sv ON sv.id = t.served_by
  LEFT JOIN transaction_details d ON d.transaction_id = t.id
 GROUP BY t.id, u.name, sv.name;

CREATE OR REPLACE VIEW v_transaction_history AS
SELECT t.id,
       fn_order_number(t.id)               AS nomor,
       t.transaction_date                  AS tanggal,
       t.user_id,
       t.table_no,
       t.status,
       t.order_status,
       t.payment_method,
       t.total,
       t.order_type,
       COALESCE(string_agg(d.qty::text || '× ' || COALESCE(m.name, 'Menu dihapus'), ', ' ORDER BY d.id), '') AS item
FROM transactions t
LEFT JOIN transaction_details d ON d.transaction_id = t.id
LEFT JOIN menus m ON m.id = d.menu_id
GROUP BY t.id;

-- Status pesanan berurutan waiting > preparing > ready > served. Saat served, sekaligus mencatat
-- waktu dan pelayan yang mengantar (waktu memakai zona Asia/Jakarta, sama dengan aplikasi).
CREATE OR REPLACE PROCEDURE sp_set_order_status(IN p_trx_id integer, IN p_new_status text, IN p_user_id integer)
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'pg_temp'
AS $$
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
    IF v_status <> 'completed' THEN
        RAISE EXCEPTION 'Pesanan belum dibayar';
    END IF;
    IF array_position(v_steps, p_new_status) <> array_position(v_steps, v_order) + 1 THEN
        RAISE EXCEPTION 'Pesanan berstatus % tidak bisa langsung menjadi %', v_order, p_new_status;
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);
    UPDATE transactions
       SET order_status = p_new_status,
           served_at = CASE WHEN p_new_status = 'served' THEN (now() AT TIME ZONE 'Asia/Jakarta') ELSE served_at END,
           served_by = CASE WHEN p_new_status = 'served' THEN p_user_id ELSE served_by END
     WHERE id = p_trx_id;
END;
$$;
