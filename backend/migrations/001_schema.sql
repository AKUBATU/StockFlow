BEGIN;
CREATE TABLE products (
 id SERIAL PRIMARY KEY,
 sku TEXT NOT NULL UNIQUE CHECK (length(trim(sku)) > 0),
 name TEXT NOT NULL CHECK (length(trim(name)) > 0),
 price INTEGER NOT NULL CHECK (price >= 0),
 stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
 is_active BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE sales_orders (
 id SERIAL PRIMARY KEY,
 customer TEXT NOT NULL CHECK (length(trim(customer)) > 0),
 status TEXT NOT NULL DEFAULT 'CREATED' CHECK (status IN ('CREATED','CANCELLED')),
 total INTEGER NOT NULL CHECK (total >= 0),
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
 cancelled_at TIMESTAMPTZ
);
CREATE TABLE stock_movements (
 id SERIAL PRIMARY KEY,
 product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
 type TEXT NOT NULL CHECK (type IN ('in','out')),
 qty INTEGER NOT NULL CHECK (qty > 0),
 note TEXT NOT NULL DEFAULT '',
 order_id INTEGER REFERENCES sales_orders(id) ON DELETE RESTRICT,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE sales_order_items (
 id SERIAL PRIMARY KEY,
 order_id INTEGER NOT NULL REFERENCES sales_orders(id) ON DELETE RESTRICT,
 sku TEXT NOT NULL REFERENCES products(sku) ON UPDATE CASCADE ON DELETE RESTRICT,
 qty INTEGER NOT NULL CHECK (qty > 0),
 unit_price INTEGER NOT NULL CHECK (unit_price >= 0),
 UNIQUE(order_id, sku)
);
CREATE INDEX stock_movements_product_time ON stock_movements(product_id, created_at DESC, id DESC);
CREATE INDEX sales_orders_created ON sales_orders(created_at);
CREATE TABLE IF NOT EXISTS stockflow_schema_migrations (
 version TEXT PRIMARY KEY,
 applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
INSERT INTO stockflow_schema_migrations(version) VALUES('001_schema.sql') ON CONFLICT DO NOTHING;
COMMIT;
