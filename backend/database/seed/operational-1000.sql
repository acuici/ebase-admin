-- EBASE local operational demo data. Never run against production.
-- Creates 1,000 products, 1,000 SKUs, 1,000 fulfillment records and 1,000 packages.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO products (product_no, name, brand, category, description, status, created_at, updated_at)
WITH RECURSIVE seq AS (SELECT 1 n UNION ALL SELECT n+1 FROM seq WHERE n<1000)
SELECT CONCAT('DEMO-PROD-',LPAD(n,5,'0')), CONCAT('演示商品',LPAD(n,5,'0')), ELT(1+MOD(n,4),'LUMEA Home','LUMEA Life','LUMEA Audio','LUMEA Tech'), ELT(1+MOD(n,4),'家居生活','箱包出行','数码影音','智能穿戴'), CONCAT('用于本地压测的商品资料 ',n), 'active', NOW(), NOW() FROM seq
ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at);

INSERT INTO product_skus (product_id, sku_code, name, specs, price, market_price, stock_quantity, reserved_quantity, status, created_at, updated_at)
SELECT p.id, CONCAT('DEMO-SKU-',LPAD(CAST(SUBSTRING(p.product_no,11) AS UNSIGNED),5,'0')), CONCAT(p.name,' · 标准款'), JSON_OBJECT('版本','标准款','演示编号',p.product_no), 99 + MOD(p.id,1900), 129 + MOD(p.id,2200), 20 + MOD(p.id,480), MOD(p.id,12), 'active', NOW(), NOW()
FROM products p WHERE p.product_no LIKE 'DEMO-PROD-%'
ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at);

INSERT INTO fulfillments (fulfillment_no, order_id, warehouse_code, status, recipient_snapshot, shipping_method, shipped_at, delivered_at, created_at, updated_at)
SELECT CONCAT('DEMO-FUL-',LPAD(CAST(SUBSTRING(o.order_no,13) AS UNSIGNED),6,'0')), o.id, ELT(1+MOD(o.id,3),'WH-E01','WH-S01','WH-N02'), ELT(1+MOD(o.id,4),'processing','shipped','delivered','pending'), JSON_OBJECT('name',c.name,'phone',c.phone,'address','演示收货地址'), 'standard', IF(o.status IN ('shipped','completed'),DATE_SUB(NOW(),INTERVAL MOD(o.id,20) DAY),NULL), IF(o.status='completed',DATE_SUB(NOW(),INTERVAL MOD(o.id,10) DAY),NULL), NOW(), NOW()
FROM orders o JOIN customers c ON c.id=o.customer_id
WHERE o.order_no LIKE 'DEMO-ORDER-%'
ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at);

INSERT INTO shipment_packages (fulfillment_id, package_no, carrier_code, tracking_no, status, shipped_at, created_at, updated_at)
SELECT f.id, CONCAT('DEMO-PACK-',LPAD(CAST(SUBSTRING(f.fulfillment_no,10) AS UNSIGNED),6,'0')), ELT(1+MOD(f.id,4),'SF','JD','YTO','ZTO'), CONCAT('DEMO',LPAD(f.id,12,'0')), f.status, f.shipped_at, NOW(), NOW()
FROM fulfillments f WHERE f.fulfillment_no LIKE 'DEMO-FUL-%'
ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at);

INSERT INTO shipment_tracking_events (package_id,event_code,event_status,description,location,occurred_at,created_at)
SELECT p.id,'DEMO_DELIVERED','运输中','演示物流轨迹：包裹正常运输中','上海转运中心',DATE_SUB(NOW(),INTERVAL MOD(p.id,72) HOUR),NOW()
FROM shipment_packages p WHERE p.package_no LIKE 'DEMO-PACK-%'
AND NOT EXISTS (SELECT 1 FROM shipment_tracking_events e WHERE e.package_id=p.id);

COMMIT;
