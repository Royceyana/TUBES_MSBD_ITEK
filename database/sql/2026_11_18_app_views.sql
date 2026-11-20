-- =====================================================================================
-- Kafe Kaleng | III.5 view tambahan yang dipakai langsung oleh halaman aplikasi
-- Dijalankan oleh migrasi 2026_11_18_000001_add_app_views. Semua view hanya-baca.
-- Hak SELECT-nya ada di database/sql/2026_11_17_roles.sql (jalankan ulang setelah migrate).
-- =====================================================================================

-- Rincian item per pesanan (papan Dapur dan Pelayan).
CREATE OR REPLACE VIEW v_order_lines AS
SELECT d.id            AS detail_id,
       d.transaction_id,
       d.menu_id,
       COALESCE(m.name, 'Menu dihapus') AS menu,
       d.qty,
       d.subtotal
FROM transaction_details d
LEFT JOIN menus m ON m.id = d.menu_id;

-- Riwayat transaksi kasir: satu baris per transaksi lengkap dengan daftar item.
CREATE OR REPLACE VIEW v_transaction_history AS
SELECT t.id,
       ('INV-' || lpad(t.id::text, 4, '0')) AS nomor,
       t.transaction_date                   AS tanggal,
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

-- Penjualan per menu per hari (transaksi completed): bahan laporan laba dan menu terlaris.
CREATE OR REPLACE VIEW v_sales_by_menu_daily AS
SELECT t.transaction_date::date AS tanggal,
       m.id                     AS menu_id,
       m.name,
       sum(d.qty)::integer      AS qty_terjual,
       sum(d.subtotal)          AS pendapatan,
       sum(d.qty::numeric * COALESCE(h.hpp, 0)) AS hpp_total
FROM transaction_details d
JOIN transactions t ON t.id = d.transaction_id AND t.status = 'completed'
JOIN menus m        ON m.id = d.menu_id
LEFT JOIN v_menu_hpp h ON h.menu_id = m.id
GROUP BY t.transaction_date::date, m.id, m.name;

-- Arus stok per bahan per hari: bahan laporan stok pemilik.
CREATE OR REPLACE VIEW v_stock_flow_daily AS
SELECT s.created_at::date AS tanggal,
       s.ingredient_id,
       i.name             AS bahan,
       i.unit             AS satuan,
       COALESCE(sum(s.qty) FILTER (WHERE s.type = 'in'),  0) AS qty_masuk,
       COALESCE(sum(s.qty) FILTER (WHERE s.type = 'out'), 0) AS qty_keluar
FROM stock_movements s
JOIN ingredients i ON i.id = s.ingredient_id
GROUP BY s.created_at::date, s.ingredient_id, i.name, i.unit;

-- Resep per menu (menu tanpa resep tetap muncul dengan bahan NULL).
CREATE OR REPLACE VIEW v_menu_recipe AS
SELECT m.id     AS menu_id,
       m.name   AS menu,
       m.status AS menu_status,
       c.name   AS kategori,
       mi.ingredient_id,
       i.name   AS bahan,
       i.unit   AS satuan,
       mi.qty_needed
FROM menus m
LEFT JOIN categories c        ON c.id = m.category_id
LEFT JOIN menu_ingredients mi ON mi.menu_id = m.id
LEFT JOIN ingredients i       ON i.id = mi.ingredient_id;

-- Ringkasan data master untuk dashboard admin (satu baris).
CREATE OR REPLACE VIEW v_admin_summary AS
SELECT (SELECT count(*) FROM menus WHERE status = 'active')::integer AS menu_aktif,
       (SELECT count(*) FROM categories)::integer                    AS kategori,
       (SELECT count(*) FROM ingredients WHERE is_active)::integer   AS bahan_aktif;

-- Dua view lama diperluas dengan kolom di AKHIR (aman untuk CREATE OR REPLACE):
-- order_type (jenis pesanan: dine_in | takeaway), served_at, dan nama pelayan yang mengantar.
CREATE OR REPLACE VIEW v_order_queue AS
SELECT t.id,
       'INV-' || lpad(t.id::text, 4, '0')  AS nomor,
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
       t.total,
       t.order_type,
       t.served_at,
       sv.name                             AS pelayan
  FROM transactions t
  LEFT JOIN users u  ON u.id  = t.user_id
  LEFT JOIN users sv ON sv.id = t.served_by
  LEFT JOIN transaction_details d ON d.transaction_id = t.id
 GROUP BY t.id, u.name, sv.name;
