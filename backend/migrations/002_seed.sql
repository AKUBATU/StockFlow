BEGIN;
INSERT INTO products (sku,name,price,stock,is_active) VALUES
 ('ATK001','Pulpen gel hitam',5000,118,true),
 ('ATK002','Kertas HVS A4 80 gsm',58000,33,true),
 ('ATK003','Map plastik A4',3500,8,true),
 ('ATK004','Tinta printer hitam',85000,12,true),
 ('ATK005','Stapler kecil',18000,5,true),
 ('ATK006','Pensil 2B',3000,80,true),
 ('ATK007','Buku catatan A5',15000,42,true),
 ('ATK008','Penghapus putih',2500,60,true),
 ('ATK009','Penggaris 30 cm',6000,25,true),
 ('ATK010','Spidol whiteboard',12000,9,true),
 ('ATK011','Sticky notes pastel',14000,18,true),
 ('ATK012','Amplop cokelat A4',2000,0,false),
 ('ATK013','Binder clip 32 mm',16000,7,true),
 ('ATK014','Lakban bening',11000,30,false);
INSERT INTO sales_orders(customer,total,created_at) VALUES
 ('Divisi Keuangan',126000,now()-interval '2 hours'),
 ('Divisi Operasional',30000,now()-interval '1 hour');
INSERT INTO sales_order_items(order_id,sku,qty,unit_price) VALUES
 (1,'ATK001',2,5000),(1,'ATK002',2,58000),(2,'ATK007',2,15000);
INSERT INTO stock_movements(product_id,type,qty,note,created_at)
 SELECT id,'in',stock + CASE sku WHEN 'ATK001' THEN 2 WHEN 'ATK002' THEN 2 WHEN 'ATK007' THEN 2 ELSE 0 END,
 'Saldo awal',now()-interval '1 day' FROM products WHERE stock>0;
INSERT INTO stock_movements(product_id,type,qty,note,order_id,created_at)
 SELECT p.id,'out',i.qty,'Pesanan awal #'||o.id,o.id,o.created_at
 FROM sales_order_items i JOIN products p ON p.sku=i.sku JOIN sales_orders o ON o.id=i.order_id;
INSERT INTO stockflow_schema_migrations(version) VALUES('002_seed.sql') ON CONFLICT DO NOTHING;
COMMIT;
