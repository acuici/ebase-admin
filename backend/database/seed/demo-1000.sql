-- EBASE local demo data. Never run against production.
-- Idempotent by DEMO- business identifiers. Requires MySQL 8+.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO customers (customer_no, email, phone, name, status, source_channel, created_at, updated_at)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL SELECT n + 1 FROM seq WHERE n < 1000
)
SELECT
    CONCAT('DEMO-CUST-', LPAD(n, 4, '0')),
    CONCAT('customer', LPAD(n, 4, '0'), '@demo.lumea.local'),
    CONCAT('139', LPAD(n, 8, '0')),
    CONCAT('演示客户', LPAD(n, 4, '0')),
    'active',
    ELT(1 + MOD(n, 4), 'storefront', 'tmall', 'jd', 'douyin'),
    DATE_SUB(NOW(), INTERVAL MOD(n, 365) DAY),
    NOW()
FROM seq
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO customer_addresses (customer_id, recipient_name, phone, province, city, district, address_line, is_default, created_at, updated_at)
SELECT c.id, c.name, c.phone, '上海市', '上海市', ELT(1 + MOD(c.id, 3), '浦东新区', '静安区', '徐汇区'), CONCAT('演示路 ', MOD(c.id, 999) + 1, ' 号'), 1, NOW(), NOW()
FROM customers c
WHERE c.customer_no LIKE 'DEMO-CUST-%'
  AND NOT EXISTS (SELECT 1 FROM customer_addresses a WHERE a.customer_id = c.id AND a.is_default = 1);

INSERT INTO customer_tags (name, color, created_at)
VALUES ('演示高复购', '#635BFF', NOW()), ('演示数码偏好', '#2E9B72', NOW()), ('演示待召回', '#C97919', NOW())
ON DUPLICATE KEY UPDATE color = VALUES(color);

INSERT IGNORE INTO customer_tag_relations (customer_id, tag_id, created_at)
SELECT c.id, t.id, NOW()
FROM customers c
JOIN customer_tags t ON t.name = ELT(1 + MOD(c.id, 3), '演示高复购', '演示数码偏好', '演示待召回')
WHERE c.customer_no LIKE 'DEMO-CUST-%';

INSERT INTO customer_touchpoints (customer_id, channel, touchpoint_type, title, content, occurred_at, created_at)
SELECT c.id, 'system', 'profile_created', '完成消费者画像', '由演示数据生成器创建', c.created_at, NOW()
FROM customers c
WHERE c.customer_no LIKE 'DEMO-CUST-%'
  AND NOT EXISTS (SELECT 1 FROM customer_touchpoints p WHERE p.customer_id = c.id AND p.touchpoint_type = 'profile_created');

INSERT INTO orders (order_no, member_id, customer_id, channel_type, external_order_no, status, total_amount, currency, created_at, updated_at)
SELECT
    CONCAT('DEMO-ORDER-', LPAD(c.id, 6, '0')),
    NULL,
    c.id,
    ELT(1 + MOD(c.id, 4), 'storefront', 'tmall', 'jd', 'douyin'),
    CONCAT('DEMO-EXT-', LPAD(c.id, 6, '0')),
    ELT(1 + MOD(c.id, 5), 'pending_payment', 'paid', 'processing', 'shipped', 'completed'),
    CAST(99 + MOD(c.id * 137, 4900) AS DECIMAL(12,2)),
    'CNY',
    DATE_SUB(NOW(), INTERVAL MOD(c.id, 90) DAY),
    NOW()
FROM customers c
WHERE c.customer_no LIKE 'DEMO-CUST-%'
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO order_items (order_id, sku_id, sku_code, product_name, quantity, unit_price, subtotal)
SELECT o.id, s.id, s.sku_code, s.name, 1 + MOD(o.id, 3), s.price, s.price * (1 + MOD(o.id, 3))
FROM orders o
JOIN product_skus s ON s.id = 1 + MOD(o.id - 1, (SELECT COUNT(*) FROM product_skus))
WHERE o.order_no LIKE 'DEMO-ORDER-%'
  AND NOT EXISTS (SELECT 1 FROM order_items i WHERE i.order_id = o.id);

INSERT INTO notifications (member_id, notification_type, title, content, target_path, payload, created_at)
SELECT m.id, 'system', '演示数据已准备', '已生成消费者、订单和画像演示数据。', '/users', JSON_OBJECT('demo', TRUE), NOW()
FROM members m
WHERE m.status = 1
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.member_id = m.id AND n.notification_type = 'system' AND n.title = '演示数据已准备');

COMMIT;
