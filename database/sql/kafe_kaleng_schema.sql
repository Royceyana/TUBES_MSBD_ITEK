-- Skema dasar Kafe Kaleng (diambil dari kafe_kaleng.sql, tanpa data dan tanpa tabel users).
-- Tabel users dibuat oleh migrasi Laravel; perubahan terbaru ada di
-- 2026_11_06_waitress_served_debit_discount.sql.
-- Name: fn_apply_stock_movement(); Type: FUNCTION

CREATE OR REPLACE FUNCTION public.fn_apply_stock_movement() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE public.ingredients
       SET stock_qty  = COALESCE(stock_qty, 0) +
                        CASE NEW.type WHEN 'out' THEN -NEW.qty ELSE NEW.qty END,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = NEW.ingredient_id;
    RETURN NULL;
END;
$$;

-- Name: fn_consume_stock_on_ready(); Type: FUNCTION

CREATE OR REPLACE FUNCTION public.fn_consume_stock_on_ready() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.status = 'cancelled' THEN
        RETURN NULL;
    END IF;

    -- idempotent: jangan potong dua kali untuk transaksi yang sama
    IF EXISTS (SELECT 1 FROM public.stock_movements
                WHERE transaction_id = NEW.id AND type = 'out') THEN
        RETURN NULL;
    END IF;

    INSERT INTO public.stock_movements (ingredient_id, user_id, type, qty, notes, transaction_id)
    SELECT mi.ingredient_id,
           NULLIF(current_setting('app.user_id', true), '')::integer,
           'out',
           ROUND(SUM(mi.qty_needed * td.qty), 2),
           'Pemakaian bahan transaksi #' || NEW.id,
           NEW.id
      FROM public.transaction_details td
      JOIN public.menu_ingredients mi ON mi.menu_id = td.menu_id
     WHERE td.transaction_id = NEW.id
     GROUP BY mi.ingredient_id;

    RETURN NULL;
END;
$$;

-- Name: fn_guard_cancel(); Type: FUNCTION

CREATE OR REPLACE FUNCTION public.fn_guard_cancel() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.status = 'cancelled' AND NEW.status <> 'cancelled' THEN
        RAISE EXCEPTION 'Pesanan yang sudah dibatalkan tidak dapat diubah lagi';
    END IF;
    IF NEW.status = 'cancelled' AND OLD.status = 'completed' AND OLD.order_status = 'ready' THEN
        RAISE EXCEPTION 'Pesanan sudah dibayar dan sudah dikonfirmasi siap oleh dapur, tidak dapat dibatalkan';
    END IF;
    RETURN NEW;
END;
$$;

-- Name: fn_stock_adjust_to(integer, numeric, integer, text); Type: FUNCTION

CREATE OR REPLACE FUNCTION public.fn_stock_adjust_to(p_ingredient_id integer, p_actual_qty numeric, p_user_id integer, p_notes text DEFAULT 'Stok opname'::text) RETURNS numeric
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_current numeric;
    v_delta   numeric;
BEGIN
    IF p_actual_qty < 0 THEN
        RAISE EXCEPTION 'Stok fisik tidak boleh negatif';
    END IF;

    SELECT COALESCE(stock_qty, 0) INTO v_current
      FROM public.ingredients WHERE id = p_ingredient_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Bahan % tidak ditemukan', p_ingredient_id;
    END IF;

    v_delta := p_actual_qty - v_current;
    IF v_delta <> 0 THEN
        INSERT INTO public.stock_movements (ingredient_id, user_id, type, qty, notes)
        VALUES (p_ingredient_id, p_user_id, 'adjustment', v_delta, p_notes);
    END IF;
    RETURN v_delta;
END;
$$;

-- Name: fn_sync_transaction_total(); Type: FUNCTION

CREATE OR REPLACE FUNCTION public.fn_sync_transaction_total() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE v_id integer;
BEGIN
    v_id := COALESCE(NEW.transaction_id, OLD.transaction_id);
    UPDATE transactions
       SET total = COALESCE((SELECT SUM(subtotal) FROM transaction_details WHERE transaction_id = v_id), 0)
     WHERE id = v_id;
    RETURN NULL;
END;
$$;



SET default_tablespace = '';

SET default_table_access_method = heap;

-- Name: categories; Type: TABLE

CREATE TABLE public.categories (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);

-- Name: categories_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: categories_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;

-- Name: ingredients; Type: TABLE

CREATE TABLE public.ingredients (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    unit character varying(20) NOT NULL,
    stock_qty numeric(10,2) DEFAULT 0.00,
    min_stock numeric(10,2) DEFAULT 0.00,
    cost_per_unit numeric(12,4) DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ingredients_cost_per_unit_check CHECK ((cost_per_unit >= (0)::numeric))
);

-- Name: ingredients_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.ingredients_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: ingredients_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.ingredients_id_seq OWNED BY public.ingredients.id;

-- Name: menu_ingredients; Type: TABLE

CREATE TABLE public.menu_ingredients (
    id integer NOT NULL,
    menu_id integer NOT NULL,
    ingredient_id integer NOT NULL,
    qty_needed numeric(10,2) NOT NULL,
    CONSTRAINT menu_ingredients_qty_needed_check CHECK ((qty_needed > (0)::numeric))
);

-- Name: menu_ingredients_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.menu_ingredients_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: menu_ingredients_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.menu_ingredients_id_seq OWNED BY public.menu_ingredients.id;

-- Name: menus; Type: TABLE

CREATE TABLE public.menus (
    id integer NOT NULL,
    category_id integer,
    name character varying(100) NOT NULL,
    price numeric(10,2) NOT NULL,
    image character varying(255),
    status character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT menus_price_check CHECK ((price > (0)::numeric)),
    CONSTRAINT menus_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);

-- Name: menus_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.menus_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: menus_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.menus_id_seq OWNED BY public.menus.id;

-- Name: stock_movements; Type: TABLE

CREATE TABLE public.stock_movements (
    id integer NOT NULL,
    ingredient_id integer,
    user_id integer,
    transaction_id integer,
    type character varying(20) NOT NULL,
    qty numeric(10,2) NOT NULL,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT stock_movements_qty_check CHECK (((((type)::text = 'adjustment'::text) AND (qty <> (0)::numeric)) OR (((type)::text = ANY ((ARRAY['in'::character varying, 'out'::character varying])::text[])) AND (qty > (0)::numeric)))),
    CONSTRAINT stock_movements_type_check CHECK (((type)::text = ANY ((ARRAY['in'::character varying, 'out'::character varying, 'adjustment'::character varying])::text[])))
);

-- Name: stock_movements_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.stock_movements_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: stock_movements_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.stock_movements_id_seq OWNED BY public.stock_movements.id;

-- Name: transaction_details; Type: TABLE

CREATE TABLE public.transaction_details (
    id integer NOT NULL,
    transaction_id integer,
    menu_id integer,
    qty integer NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    CONSTRAINT transaction_details_qty_check CHECK ((qty > 0))
);

-- Name: transaction_details_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.transaction_details_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: transaction_details_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.transaction_details_id_seq OWNED BY public.transaction_details.id;

-- Name: transactions; Type: TABLE

CREATE TABLE public.transactions (
    id integer NOT NULL,
    user_id integer,
    transaction_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    total numeric(12,2) DEFAULT 0 NOT NULL,
    payment_method character varying(20),
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    order_status character varying(20) DEFAULT 'waiting'::character varying NOT NULL,
    table_no character varying(10),
    CONSTRAINT transactions_order_status_check CHECK (((order_status)::text = ANY ((ARRAY['waiting'::character varying, 'preparing'::character varying, 'ready'::character varying])::text[]))),
    CONSTRAINT transactions_paid_needs_method CHECK ((((status)::text <> 'completed'::text) OR (payment_method IS NOT NULL))),
    CONSTRAINT transactions_payment_method_check CHECK (((payment_method)::text = ANY ((ARRAY['cash'::character varying, 'qris'::character varying])::text[]))),
    CONSTRAINT transactions_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);

-- Name: transactions_id_seq; Type: SEQUENCE

CREATE SEQUENCE public.transactions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;

-- Name: v_ingredient_stock; Type: VIEW

CREATE OR REPLACE VIEW public.v_ingredient_stock AS
 SELECT id,
    name,
    unit,
    COALESCE(stock_qty, (0)::numeric) AS stock_qty,
    COALESCE(min_stock, (0)::numeric) AS min_stock,
        CASE
            WHEN (COALESCE(stock_qty, (0)::numeric) <= (0)::numeric) THEN 'habis'::text
            WHEN (COALESCE(stock_qty, (0)::numeric) <= COALESCE(min_stock, (0)::numeric)) THEN 'menipis'::text
            ELSE 'aman'::text
        END AS stock_status,
    updated_at
   FROM public.ingredients i
  WHERE is_active;

-- Name: v_menu_availability; Type: VIEW

CREATE OR REPLACE VIEW public.v_menu_availability AS
 SELECT m.id AS menu_id,
    m.name,
    m.category_id,
    m.price,
    m.status,
    count(mi.id) AS jumlah_bahan,
    (min(floor((GREATEST(COALESCE(i.stock_qty, (0)::numeric), (0)::numeric) / mi.qty_needed))))::integer AS porsi_tersedia,
    (((m.status)::text = 'active'::text) AND ((count(mi.id) = 0) OR (min(floor((GREATEST(COALESCE(i.stock_qty, (0)::numeric), (0)::numeric) / mi.qty_needed))) >= (1)::numeric))) AS is_available
   FROM ((public.menus m
     LEFT JOIN public.menu_ingredients mi ON ((mi.menu_id = m.id)))
     LEFT JOIN public.ingredients i ON ((i.id = mi.ingredient_id)))
  GROUP BY m.id, m.name, m.category_id, m.price, m.status;

-- Name: v_menu_hpp; Type: VIEW

CREATE OR REPLACE VIEW public.v_menu_hpp AS
 SELECT m.id AS menu_id,
    m.name,
    m.price,
    count(mi.id) AS jumlah_bahan,
    round(COALESCE(sum((mi.qty_needed * i.cost_per_unit)), (0)::numeric)) AS hpp,
    (m.price - round(COALESCE(sum((mi.qty_needed * i.cost_per_unit)), (0)::numeric))) AS margin,
        CASE
            WHEN (m.price > (0)::numeric) THEN round((((m.price - COALESCE(sum((mi.qty_needed * i.cost_per_unit)), (0)::numeric)) * (100)::numeric) / m.price), 1)
            ELSE NULL::numeric
        END AS margin_persen
   FROM ((public.menus m
     LEFT JOIN public.menu_ingredients mi ON ((mi.menu_id = m.id)))
     LEFT JOIN public.ingredients i ON ((i.id = mi.ingredient_id)))
  GROUP BY m.id, m.name, m.price;

-- Name: categories id; Type: DEFAULT

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);

-- Name: ingredients id; Type: DEFAULT

ALTER TABLE ONLY public.ingredients ALTER COLUMN id SET DEFAULT nextval('public.ingredients_id_seq'::regclass);

-- Name: menu_ingredients id; Type: DEFAULT

ALTER TABLE ONLY public.menu_ingredients ALTER COLUMN id SET DEFAULT nextval('public.menu_ingredients_id_seq'::regclass);

-- Name: menus id; Type: DEFAULT

ALTER TABLE ONLY public.menus ALTER COLUMN id SET DEFAULT nextval('public.menus_id_seq'::regclass);

-- Name: stock_movements id; Type: DEFAULT

ALTER TABLE ONLY public.stock_movements ALTER COLUMN id SET DEFAULT nextval('public.stock_movements_id_seq'::regclass);

-- Name: transaction_details id; Type: DEFAULT

ALTER TABLE ONLY public.transaction_details ALTER COLUMN id SET DEFAULT nextval('public.transaction_details_id_seq'::regclass);

-- Name: transactions id; Type: DEFAULT

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);

-- Name: categories categories_name_key; Type: CONSTRAINT

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_name_key UNIQUE (name);

-- Name: categories categories_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);

-- Name: ingredients ingredients_name_key; Type: CONSTRAINT

ALTER TABLE ONLY public.ingredients
    ADD CONSTRAINT ingredients_name_key UNIQUE (name);

-- Name: ingredients ingredients_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.ingredients
    ADD CONSTRAINT ingredients_pkey PRIMARY KEY (id);

-- Name: menu_ingredients menu_ingredients_menu_id_ingredient_id_key; Type: CONSTRAINT

ALTER TABLE ONLY public.menu_ingredients
    ADD CONSTRAINT menu_ingredients_menu_id_ingredient_id_key UNIQUE (menu_id, ingredient_id);

-- Name: menu_ingredients menu_ingredients_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.menu_ingredients
    ADD CONSTRAINT menu_ingredients_pkey PRIMARY KEY (id);

-- Name: menus menus_name_key; Type: CONSTRAINT

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_name_key UNIQUE (name);

-- Name: menus menus_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_pkey PRIMARY KEY (id);

-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);

-- Name: transaction_details transaction_details_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.transaction_details
    ADD CONSTRAINT transaction_details_pkey PRIMARY KEY (id);

-- Name: transactions transactions_pkey; Type: CONSTRAINT

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);

-- Name: idx_stock_mov_ing; Type: INDEX

CREATE INDEX idx_stock_mov_ing ON public.stock_movements USING btree (ingredient_id, created_at);

-- Name: idx_stock_mov_trx; Type: INDEX

CREATE INDEX idx_stock_mov_trx ON public.stock_movements USING btree (transaction_id);

-- Name: idx_td_trx; Type: INDEX

CREATE INDEX idx_td_trx ON public.transaction_details USING btree (transaction_id);

-- Name: idx_trx_date; Type: INDEX

CREATE INDEX idx_trx_date ON public.transactions USING btree (transaction_date);

-- Name: idx_trx_status; Type: INDEX

CREATE INDEX idx_trx_status ON public.transactions USING btree (status, order_status);

-- Name: transactions trg_consume_stock_on_ready; Type: TRIGGER

CREATE TRIGGER trg_consume_stock_on_ready AFTER UPDATE OF order_status ON public.transactions FOR EACH ROW WHEN ((((new.order_status)::text = 'ready'::text) AND ((old.order_status)::text IS DISTINCT FROM 'ready'::text))) EXECUTE FUNCTION public.fn_consume_stock_on_ready();

-- Name: stock_movements trg_stock_movement_apply; Type: TRIGGER

CREATE TRIGGER trg_stock_movement_apply AFTER INSERT ON public.stock_movements FOR EACH ROW EXECUTE FUNCTION public.fn_apply_stock_movement();

-- Name: transaction_details trg_td_sync_total; Type: TRIGGER

CREATE TRIGGER trg_td_sync_total AFTER INSERT OR DELETE OR UPDATE ON public.transaction_details FOR EACH ROW EXECUTE FUNCTION public.fn_sync_transaction_total();

-- Name: transactions trg_trx_guard_cancel; Type: TRIGGER

CREATE TRIGGER trg_trx_guard_cancel BEFORE UPDATE OF status ON public.transactions FOR EACH ROW EXECUTE FUNCTION public.fn_guard_cancel();

-- Name: menu_ingredients menu_ingredients_ingredient_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.menu_ingredients
    ADD CONSTRAINT menu_ingredients_ingredient_id_fkey FOREIGN KEY (ingredient_id) REFERENCES public.ingredients(id) ON DELETE CASCADE;

-- Name: menu_ingredients menu_ingredients_menu_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.menu_ingredients
    ADD CONSTRAINT menu_ingredients_menu_id_fkey FOREIGN KEY (menu_id) REFERENCES public.menus(id) ON DELETE CASCADE;

-- Name: menus menus_category_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;

-- Name: stock_movements stock_movements_ingredient_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_ingredient_id_fkey FOREIGN KEY (ingredient_id) REFERENCES public.ingredients(id) ON DELETE CASCADE;

-- Name: stock_movements stock_movements_transaction_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;

-- Name: stock_movements stock_movements_user_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: transaction_details transaction_details_menu_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.transaction_details
    ADD CONSTRAINT transaction_details_menu_id_fkey FOREIGN KEY (menu_id) REFERENCES public.menus(id) ON DELETE RESTRICT;

-- Name: transaction_details transaction_details_transaction_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.transaction_details
    ADD CONSTRAINT transaction_details_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE CASCADE;

-- Name: transactions transactions_user_id_fkey; Type: FK CONSTRAINT

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;
