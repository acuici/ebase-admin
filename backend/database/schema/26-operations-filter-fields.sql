-- Operations filter facts. Apply only after base schema.
ALTER TABLE `product_skus`
  ADD COLUMN `warehouse_code` VARCHAR(64) NULL AFTER `product_id`,
  ADD COLUMN `supplier_id` BIGINT UNSIGNED NULL AFTER `warehouse_code`,
  ADD KEY `idx_sku_warehouse_supplier` (`warehouse_code`, `supplier_id`);
ALTER TABLE `customers`
  ADD COLUMN `member_tier` VARCHAR(32) NULL AFTER `status`,
  ADD KEY `idx_customer_tier_status` (`member_tier`, `status`);
ALTER TABLE `coupons`
  ADD COLUMN `applicable_channel` VARCHAR(32) NULL AFTER `status`,
  ADD COLUMN `audience` VARCHAR(32) NULL AFTER `applicable_channel`,
  ADD KEY `idx_coupon_channel_audience` (`applicable_channel`, `audience`);
ALTER TABLE `marketing_campaigns`
  ADD COLUMN `publish_channel` VARCHAR(32) NULL AFTER `campaign_type`,
  ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `ends_at`,
  ADD KEY `idx_campaign_channel_owner_dates` (`publish_channel`, `created_by`, `starts_at`, `ends_at`);
CREATE TABLE `suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_supplier_code` (`supplier_code`), KEY `idx_supplier_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `warehouses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `supplier_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_warehouse_code` (`warehouse_code`), KEY `idx_warehouse_supplier_status` (`supplier_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `member_data_scopes` (
  `member_id` BIGINT UNSIGNED NOT NULL,
  `scope_type` VARCHAR(32) NOT NULL,
  `scope_value` VARCHAR(128) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`member_id`, `scope_type`, `scope_value`), KEY `idx_data_scope_value` (`scope_type`, `scope_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
