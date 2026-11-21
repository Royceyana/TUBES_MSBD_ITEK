-- =====================================================================================
-- Kafe Kaleng | III.6 prosedur pesanan yang dipakai PosController
--   sp_create_order : membuat pesanan (jenis pesanan, meja, item) dalam satu transaksi
--   sp_pay_order    : diskon + pembayaran dalam satu transaksi (gagal di tengah = batal semua)
-- Menggantikan versi lama (tanpa order_type, dan menerima metode debit yang sudah tidak dipakai).
-- Dijalankan oleh migrasi 2026_11_20_000001_rewrite_order_procedures (sebagai pemilik tabel).
-- =====================================================================================

DROP PROCEDURE IF EXISTS sp_create_order(integer, text, jsonb, integer);
DROP PROCEDURE IF EXISTS sp_pay_order(integer, text, integer, text, numeric);
DROP PROCEDURE IF EXISTS sp_create_order(integer, text, text, jsonb, integer);
DROP PROCEDURE IF EXISTS sp_pay_order(integer, text, integer, text, numeric, numeric, numeric);

CREATE PROCEDURE sp_create_order(
    IN    p_user_id    integer,
    IN    p_order_type text,
    IN    p_table_no   text,
    IN    p_items      jsonb,
    INOUT p_trx_id     integer DEFAULT NULL)
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'pg_temp'
AS $$
DECLARE
    r        record;
    v_table  text;
    v_name   text;
    v_avail  boolean;
    v_porsi  integer;
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['kasir', 'admin']);

    IF p_order_type IS NULL OR p_order_type NOT IN ('dine_in', 'takeaway') THEN
        RAISE EXCEPTION 'Jenis pesanan tidak valid: %', COALESCE(p_order_type, 'kosong');
    END IF;
    v_table := NULLIF(btrim(p_table_no), '');
    IF p_order_type = 'dine_in' AND v_table IS NULL THEN
        RAISE EXCEPTION 'Nomor meja wajib diisi untuk pesanan makan di tempat';
    END IF;
    IF p_order_type = 'takeaway' THEN
        v_table := NULL;                                   -- bawa pulang: tanpa meja
    END IF;
    IF p_items IS NULL OR jsonb_typeof(p_items) <> 'array' OR jsonb_array_length(p_items) = 0 THEN
        RAISE EXCEPTION 'Pesanan harus berisi minimal satu menu';
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);

    INSERT INTO transactions (user_id, order_type, table_no, status, order_status, transaction_date)
    VALUES (p_user_id, p_order_type, v_table, 'pending', 'waiting', (now() AT TIME ZONE 'Asia/Jakarta'))
    RETURNING id INTO p_trx_id;

    FOR r IN
        SELECT (i->>'menu_id')::int AS menu_id, sum((i->>'qty')::int)::int AS qty
          FROM jsonb_array_elements(p_items) AS i
         GROUP BY 1
         ORDER BY 1
    LOOP
        SELECT name INTO v_name FROM menus WHERE id = r.menu_id AND status = 'active';
        IF NOT FOUND THEN
            RAISE EXCEPTION 'Menu % sudah tidak aktif. Muat ulang halaman kasir.', r.menu_id;
        END IF;
        IF r.qty < 1 OR r.qty > 99 THEN
            RAISE EXCEPTION 'Jumlah untuk "%" harus 1-99', v_name;
        END IF;

        SELECT is_available, porsi_tersedia INTO v_avail, v_porsi
          FROM v_menu_availability WHERE menu_id = r.menu_id;
        IF NOT FOUND OR NOT v_avail THEN
            RAISE EXCEPTION 'Stok bahan untuk "%" tidak cukup (tersisa 0 porsi).', v_name;
        END IF;
        IF v_porsi IS NOT NULL AND v_porsi < r.qty THEN
            RAISE EXCEPTION 'Stok bahan untuk "%" tidak cukup (tersisa % porsi).', v_name, v_porsi;
        END IF;

        -- subtotal dihitung ulang oleh trigger fn_protect_transaction_details dari harga menu.
        INSERT INTO transaction_details (transaction_id, menu_id, qty, subtotal)
        VALUES (p_trx_id, r.menu_id, r.qty, 0);
    END LOOP;
END;
$$;

CREATE PROCEDURE sp_pay_order(
    IN    p_trx_id         integer,
    IN    p_method         text,
    IN    p_user_id        integer,
    IN    p_discount_type  text    DEFAULT 'none',
    IN    p_discount_value numeric DEFAULT 0,
    IN    p_paid           numeric DEFAULT NULL,
    INOUT p_total          numeric DEFAULT NULL)
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'public', 'pg_temp'
AS $$
DECLARE
    v_status text;
    v_type   text := COALESCE(p_discount_type, 'none');
BEGIN
    PERFORM fn_assert_role(p_user_id, ARRAY['kasir', 'admin']);

    IF p_method IS NULL OR p_method NOT IN ('cash', 'qris') THEN
        RAISE EXCEPTION 'Metode bayar tidak dikenal: %', COALESCE(p_method, 'kosong');
    END IF;
    IF v_type NOT IN ('none', 'percent', 'amount') THEN
        RAISE EXCEPTION 'Jenis diskon tidak dikenal: %', v_type;
    END IF;
    IF v_type = 'percent' AND COALESCE(p_discount_value, 0) > 100 THEN
        RAISE EXCEPTION 'Diskon persen maksimal 100.';
    END IF;

    SELECT status INTO v_status FROM transactions WHERE id = p_trx_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Transaksi % tidak ditemukan', p_trx_id;
    END IF;
    IF v_status <> 'pending' THEN
        RAISE EXCEPTION 'Pesanan ini sudah dibayar atau dibatalkan.';
    END IF;

    PERFORM set_config('app.user_id', p_user_id::text, true);

    -- Trigger fn_apply_discount menghitung discount_amount dan total.
    UPDATE transactions
       SET discount_type  = v_type,
           discount_value = CASE WHEN v_type = 'none' THEN 0 ELSE COALESCE(p_discount_value, 0) END
     WHERE id = p_trx_id
    RETURNING total INTO p_total;

    IF p_method = 'cash' AND COALESCE(p_paid, 0) < p_total THEN
        RAISE EXCEPTION 'Uang diterima kurang dari total pembayaran.';   -- diskon ikut dibatalkan
    END IF;

    UPDATE transactions SET status = 'completed', payment_method = p_method WHERE id = p_trx_id;
END;
$$;

COMMENT ON PROCEDURE sp_create_order(integer, text, text, jsonb, integer) IS
    'Membuat pesanan baru (dine_in/takeaway) beserta rincian; stok dicek lewat v_menu_availability.';
COMMENT ON PROCEDURE sp_pay_order(integer, text, integer, text, numeric, numeric, numeric) IS
    'Menerapkan diskon lalu melunasi pesanan (cash/qris); mengembalikan total akhir lewat p_total.';
