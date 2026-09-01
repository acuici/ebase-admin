-- EBASE development seed data
-- Safe to re-run: demo records use deterministic unique codes and upserts.
-- Never run this file against production.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO roles (name, description, permission_codes, is_active, created_at, updated_at)
VALUES (
  '运营专员',
  '开发演示角色，拥有商品、订单与独立站基础权限',
  'catalog.product.read,catalog.product.update,order.order.read,order.order.update,storefront.site.read,storefront.site.update',
  1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE description = VALUES(description), permission_codes = VALUES(permission_codes), updated_at = NOW();

INSERT INTO members (email, name, password_hash, status, is_super, created_at, updated_at)
VALUES (
  'operator@ebase.local',
  '开发运营专员',
  '$2y$10$qamLjlC1TQaxitpOBFdHFeoRrCK8ccwvGndfc9.JKSQ2An8Co.w7S',
  1, 0, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE name = VALUES(name), status = 1, updated_at = NOW();

INSERT IGNORE INTO member_roles (member_id, role_id, created_at)
SELECT m.id, r.id, NOW()
FROM members m CROSS JOIN roles r
WHERE m.email = 'operator@ebase.local' AND r.name = '运营专员';

INSERT INTO storefront_sites (
  site_code, name, brand_name, service_email, default_locale, currency,
  timezone, status, default_seo_title, default_seo_description, created_at, updated_at
)
VALUES
  ('cn-main', '中国大陆站', 'EBASE', 'service@ebase.cn', 'zh-CN', 'CNY', 'Asia/Shanghai', 'active',
   'EBASE｜品质生活精选', '探索数码、家居与生活方式好物，享受可靠配送和会员专属服务。', NOW(), NOW()),
  ('global-main', '全球体验站', 'EBASE Global', 'hello@ebase.global', 'en-US', 'USD', 'UTC', 'draft',
   'EBASE Global | Better everyday commerce', 'Discover considered technology, home, and lifestyle essentials.', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status), updated_at = NOW();

INSERT INTO storefront_domains (
  site_id, domain, domain_type, dns_status, https_status, status, verification_token, created_at, updated_at
)
SELECT id, 'shop.ebase.test', 'primary', 'verified', 'active', 'active', NULL, NOW(), NOW()
FROM storefront_sites WHERE site_code = 'cn-main'
ON DUPLICATE KEY UPDATE dns_status = 'verified', https_status = 'active', status = 'active', updated_at = NOW();

INSERT INTO products (product_no, name, brand, description, status, created_at, updated_at)
VALUES
  ('DEMO-AIR-X-PRO', 'Air X Pro 主动降噪耳机', 'EBASE Audio', '40 dB 自适应降噪、30 小时续航与多设备快速切换。', 'active', NOW(), NOW()),
  ('DEMO-ARC-LAMP', 'Arc 智能氛围台灯', 'EBASE Home', '无级调光、色温记忆与桌面专注模式。', 'active', NOW(), NOW()),
  ('DEMO-TRAVEL-SET', '城市轻旅收纳套装', 'EBASE Life', '适配短途差旅和日常通勤的模块化收纳组合。', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), status = 'active', updated_at = NOW();

INSERT INTO product_skus (
  product_id, sku_code, name, specs, price, market_price, stock_quantity,
  reserved_quantity, status, created_at, updated_at
)
SELECT id, 'DEMO-AIR-X-PRO-BLACK', 'Air X Pro｜曜石黑', JSON_OBJECT('颜色', '曜石黑'), 1599.00, 1899.00, 80, 2, 'active', NOW(), NOW()
FROM products WHERE product_no = 'DEMO-AIR-X-PRO'
ON DUPLICATE KEY UPDATE price = VALUES(price), stock_quantity = VALUES(stock_quantity), reserved_quantity = VALUES(reserved_quantity), updated_at = NOW();

INSERT INTO product_skus (
  product_id, sku_code, name, specs, price, market_price, stock_quantity,
  reserved_quantity, status, created_at, updated_at
)
SELECT id, 'DEMO-AIR-X-PRO-SILVER', 'Air X Pro｜星云银', JSON_OBJECT('颜色', '星云银'), 1599.00, 1899.00, 64, 0, 'active', NOW(), NOW()
FROM products WHERE product_no = 'DEMO-AIR-X-PRO'
ON DUPLICATE KEY UPDATE price = VALUES(price), stock_quantity = VALUES(stock_quantity), reserved_quantity = VALUES(reserved_quantity), updated_at = NOW();

INSERT INTO product_skus (
  product_id, sku_code, name, specs, price, market_price, stock_quantity,
  reserved_quantity, status, created_at, updated_at
)
SELECT id, 'DEMO-ARC-LAMP-WHITE', 'Arc 智能氛围台灯｜月白', JSON_OBJECT('颜色', '月白'), 499.00, 599.00, 126, 0, 'active', NOW(), NOW()
FROM products WHERE product_no = 'DEMO-ARC-LAMP'
ON DUPLICATE KEY UPDATE price = VALUES(price), stock_quantity = VALUES(stock_quantity), updated_at = NOW();

INSERT INTO product_skus (
  product_id, sku_code, name, specs, price, market_price, stock_quantity,
  reserved_quantity, status, created_at, updated_at
)
SELECT id, 'DEMO-TRAVEL-SET-GRAY', '城市轻旅收纳套装｜岩灰', JSON_OBJECT('颜色', '岩灰'), 269.00, 329.00, 210, 0, 'active', NOW(), NOW()
FROM products WHERE product_no = 'DEMO-TRAVEL-SET'
ON DUPLICATE KEY UPDATE price = VALUES(price), stock_quantity = VALUES(stock_quantity), updated_at = NOW();

INSERT INTO storefront_product_listings (
  site_id, product_id, status, title, slug, description, price, inventory_policy,
  seo_title, seo_description, published_at, created_at, updated_at
)
SELECT s.id, p.id, 'published', 'Air X Pro 主动降噪耳机｜轻盈舒适，沉浸聆听',
       'air-x-pro-noise-cancelling-headphones', p.description, 1599.00, 'shared',
       'Air X Pro 主动降噪耳机 | EBASE', '40 dB 自适应降噪，30 小时续航与多设备快速切换。', NOW(), NOW(), NOW()
FROM storefront_sites s CROSS JOIN products p
WHERE s.site_code = 'cn-main' AND p.product_no = 'DEMO-AIR-X-PRO'
ON DUPLICATE KEY UPDATE status = 'published', price = VALUES(price), updated_at = NOW();

INSERT INTO storefront_content (site_id, content_type, content_key, title, slug, status, payload, published_at, created_at, updated_at)
SELECT id, 'theme', 'default-theme', 'Clarity 1.2', NULL, 'published',
       JSON_OBJECT('version', '1.2', 'sections', JSON_ARRAY('hero', 'featured_products', 'newsletter')), NOW(), NOW(), NOW()
FROM storefront_sites WHERE site_code = 'cn-main'
ON DUPLICATE KEY UPDATE payload = VALUES(payload), status = 'published', updated_at = NOW();

INSERT INTO storefront_content (site_id, content_type, content_key, title, slug, status, payload, published_at, created_at, updated_at)
SELECT id, 'navigation', 'main-navigation', '主导航', NULL, 'published',
       JSON_OBJECT('items', JSON_ARRAY(JSON_OBJECT('label','首页','url','/'), JSON_OBJECT('label','新品','url','/collections/new'), JSON_OBJECT('label','关于我们','url','/pages/about'))), NOW(), NOW(), NOW()
FROM storefront_sites WHERE site_code = 'cn-main'
ON DUPLICATE KEY UPDATE payload = VALUES(payload), status = 'published', updated_at = NOW();

INSERT INTO orders (
  order_no, member_id, channel_type, channel_store_id, external_order_no,
  status, total_amount, currency, paid_at, created_at, updated_at
)
SELECT 'DEMO-ORDER-PAID-001', m.id, 'storefront', s.id, 'WEB-DEMO-001',
       'paid', 1599.00, 'CNY', NOW(), NOW(), NOW()
FROM members m CROSS JOIN storefront_sites s
WHERE m.email = 'admin@ebase.local' AND s.site_code = 'cn-main'
ON DUPLICATE KEY UPDATE status = 'paid', updated_at = NOW();

INSERT INTO orders (
  order_no, member_id, channel_type, channel_store_id, external_order_no,
  status, total_amount, currency, created_at, updated_at
)
SELECT 'DEMO-ORDER-SHIPPED-001', m.id, 'jd', NULL, 'JD-DEMO-20260902001',
       'shipped', 499.00, 'CNY', NOW(), NOW()
FROM members m WHERE m.email = 'admin@ebase.local'
ON DUPLICATE KEY UPDATE status = 'shipped', updated_at = NOW();

INSERT INTO order_items (order_id, sku_id, sku_code, product_name, quantity, unit_price, subtotal)
SELECT o.id, sku.id, sku.sku_code, sku.name, 1, sku.price, sku.price
FROM orders o JOIN product_skus sku ON sku.sku_code = 'DEMO-AIR-X-PRO-BLACK'
WHERE o.order_no = 'DEMO-ORDER-PAID-001'
AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.sku_id = sku.id);

INSERT INTO order_items (order_id, sku_id, sku_code, product_name, quantity, unit_price, subtotal)
SELECT o.id, sku.id, sku.sku_code, sku.name, 1, sku.price, sku.price
FROM orders o JOIN product_skus sku ON sku.sku_code = 'DEMO-ARC-LAMP-WHITE'
WHERE o.order_no = 'DEMO-ORDER-SHIPPED-001'
AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.sku_id = sku.id);

INSERT INTO order_channel_extensions (
  order_id, channel_type, channel_store_id, external_order_no, buyer_external_id,
  raw_payload, imported_at, created_at, updated_at
)
SELECT o.id, 'jd', NULL, 'JD-DEMO-20260902001', 'jd-buyer-demo-001',
       JSON_OBJECT('shop_order_id','JD-DEMO-20260902001','source','development-seed'), NOW(), NOW(), NOW()
FROM orders o WHERE o.order_no = 'DEMO-ORDER-SHIPPED-001'
ON DUPLICATE KEY UPDATE raw_payload = VALUES(raw_payload), updated_at = NOW();

INSERT INTO payments (
  payment_no, order_id, channel, status, amount, currency, channel_transaction_id,
  channel_payload, paid_at, created_at, updated_at
)
SELECT 'DEMO-PAY-001', o.id, 'alipay', 'paid', o.total_amount, o.currency, 'ALI-DEMO-TXN-001',
       JSON_OBJECT('trade_no','ALI-DEMO-TXN-001','sandbox',true), NOW(), NOW(), NOW()
FROM orders o WHERE o.order_no = 'DEMO-ORDER-PAID-001'
ON DUPLICATE KEY UPDATE status = 'paid', channel_payload = VALUES(channel_payload), updated_at = NOW();

INSERT INTO fulfillments (
  fulfillment_no, order_id, warehouse_code, status, recipient_snapshot,
  shipping_method, shipped_at, created_at, updated_at
)
SELECT 'DEMO-FULFILLMENT-001', o.id, 'EBASE-SH-01', 'shipped',
       JSON_OBJECT('name','王晓明','phone','13800000000','address','上海市浦东新区演示路 88 号'),
       '标准快递', NOW(), NOW(), NOW()
FROM orders o WHERE o.order_no = 'DEMO-ORDER-SHIPPED-001'
ON DUPLICATE KEY UPDATE status = 'shipped', updated_at = NOW();

INSERT INTO shipment_packages (
  fulfillment_id, package_no, carrier_code, tracking_no, status,
  carrier_payload, shipped_at, created_at, updated_at
)
SELECT f.id, 'DEMO-PACKAGE-001', 'SF', 'SFDEMO20260902001', 'shipped',
       JSON_OBJECT('carrier','SF','source','development-seed'), NOW(), NOW(), NOW()
FROM fulfillments f WHERE f.fulfillment_no = 'DEMO-FULFILLMENT-001'
ON DUPLICATE KEY UPDATE status = 'shipped', updated_at = NOW();

INSERT INTO shipment_tracking_events (
  package_id, event_code, external_event_id, event_status, description,
  location, occurred_at, raw_payload, created_at
)
SELECT p.id, 'PACKAGE_ACCEPTED', 'SF-DEMO-EVENT-001', 'in_transit', '快件已由上海浦东集散中心发出',
       '上海市', NOW(), JSON_OBJECT('source','development-seed'), NOW()
FROM shipment_packages p WHERE p.package_no = 'DEMO-PACKAGE-001'
ON DUPLICATE KEY UPDATE description = VALUES(description), occurred_at = VALUES(occurred_at);

COMMIT;
