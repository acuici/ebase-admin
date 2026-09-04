-- 仅用于 ebase-test-mysql，不复制开发库数据。
SET NAMES utf8mb4;
INSERT INTO roles (id, name, permission_codes, is_active, created_at, updated_at) VALUES
(1, '测试超级管理员', '*', 1, NOW(), NOW()),
(2, '测试普通角色', 'channel.store.read,channel.product.read,channel.mapping.read,channel.mapping.manage,channel.order.import,channel.order_exception.read,channel.order_exception.resolve', 1, NOW(), NOW()),
(3, '测试未授权角色', '', 1, NOW(), NOW());
INSERT INTO members (id, email, name, password_hash, status, is_super, created_at, updated_at) VALUES
(1, 'test-admin@example.invalid', '测试管理员', '$2y$10$qamLjlC1TQaxitpOBFdHFeoRrCK8ccwvGndfc9.JKSQ2An8Co.w7S', 1, 1, NOW(), NOW()),
(2, 'test-operator@example.invalid', '测试运营专员', '$2y$10$qamLjlC1TQaxitpOBFdHFeoRrCK8ccwvGndfc9.JKSQ2An8Co.w7S', 1, 0, NOW(), NOW());
INSERT INTO member_roles (member_id, role_id, created_at) VALUES (1,1,NOW()), (2,2,NOW());
INSERT INTO products (id, product_no, name, brand, category, description, status, created_at, updated_at) VALUES (1, 'TEST-PRODUCT-001', '测试商品', '测试品牌', '测试类目', '测试商品描述', 'active', NOW(), NOW());
INSERT INTO product_skus (id, product_id, sku_code, name, specs, price, market_price, stock_quantity, reserved_quantity, status, created_at, updated_at) VALUES (1,1,'INTERNAL-RED-01','测试红色 SKU','{"color":"red"}',100.00,120.00,10,0,'active',NOW(),NOW()),(2,1,'INTERNAL-BLUE-01','测试蓝色 SKU','{"color":"blue"}',100.00,120.00,1,0,'active',NOW(),NOW());
INSERT INTO channel_stores (id, store_code, channel_type, external_store_id, name, status, authorization_status, created_at, updated_at) VALUES (1,'TEST-JD-STORE','jd','JD-STORE-001','测试京东店铺','active','valid',NOW(),NOW());
INSERT INTO channel_products (id, channel_store_id, product_id, external_product_id, title, listing_status, sync_status, created_at, updated_at) VALUES (1,1,1,'JD-PRODUCT-001','测试平台商品','published','synced',NOW(),NOW());
INSERT INTO channel_product_skus (id, channel_product_id, product_sku_id, external_sku_id, merchant_sku_code, listing_status, sync_status, created_at, updated_at) VALUES (1,1,1,'JD-SKU-RED-001','MERCHANT-RED','published','synced',NOW(),NOW()),(2,1,2,'JD-SKU-BLUE-001','MERCHANT-BLUE','published','synced',NOW(),NOW());
