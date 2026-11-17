-- =====================================================================================
-- Kafe Kaleng | Dukungan mode SET ROLE (opsional, DB_SET_ROLE=true)
-- Saat mode ini nyala, aplikasi menjalankan SET ROLE kafe_web_<peran> untuk tiap request
-- pengguna yang login, jadi database memakai hak akses peran tersebut (bukan hak kafe_app penuh).
-- File ini hanya menyiapkan objek di dalam database; role-nya dibuat manual (2026_11_17_roles.sql).
-- Idempotent.
-- =====================================================================================

-- Fungsi yang menulis lintas tabel dijalankan sebagai pemilik, supaya peran web cukup punya
-- hak minimum (mis. dapur hanya boleh UPDATE order_status, tetapi stok tetap terpotong otomatis).
ALTER FUNCTION fn_apply_stock_movement()      SECURITY DEFINER SET search_path = public, pg_temp;
ALTER FUNCTION fn_consume_stock_on_ready()    SECURITY DEFINER SET search_path = public, pg_temp;
ALTER FUNCTION fn_sync_transaction_total()    SECURITY DEFINER SET search_path = public, pg_temp;
ALTER FUNCTION fn_stock_adjust_to(integer, numeric, integer, text) SECURITY DEFINER SET search_path = public, pg_temp;

-- Audit mencatat role database yang aktif saat perubahan terjadi (hasil SET ROLE).
ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS db_role text;
COMMENT ON COLUMN audit_logs.db_role IS 'Role database aktif (hasil SET ROLE, mis. kafe_web_kasir); kosong bila tanpa SET ROLE.';

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
            RETURN NULL;
        END IF;
        SELECT jsonb_object_agg(k, v_old->k), jsonb_object_agg(k, v_new->k) INTO v_old, v_new
          FROM unnest(v_cols) AS k;
    END IF;

    IF TG_TABLE_NAME = 'users' THEN
        v_old := v_old - 'password' - 'remember_token';
        v_new := v_new - 'password' - 'remember_token';
    END IF;

    INSERT INTO audit_logs (table_name, record_id, action, old_data, new_data, changed_columns, app_user_id, note, db_role)
    VALUES (TG_TABLE_NAME, v_id, TG_OP, v_old, v_new, v_cols,
            NULLIF(current_setting('app.user_id', true), '')::integer,
            NULLIF(current_setting('app.audit_note', true), ''),
            NULLIF(NULLIF(current_setting('role', true), 'none'), ''));
    RETURN NULL;
END;
$$;

CREATE OR REPLACE VIEW v_audit_log AS
SELECT a.id, a.changed_at AS waktu, a.table_name AS tabel, a.record_id, a.action AS aksi,
       a.changed_columns AS kolom_berubah, a.old_data, a.new_data,
       u.name AS pelaku_aplikasi, a.db_user AS akun_database, a.note AS catatan,
       a.db_role AS peran_database
  FROM audit_logs a
  LEFT JOIN users u ON u.id = a.app_user_id;
