-- 用户画像、补货、商品质量、物流异常、通知事实表。
CREATE TABLE IF NOT EXISTS `customer_touchpoints` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `channel` VARCHAR(32) NOT NULL,
  `touchpoint_type` VARCHAR(32) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `content` VARCHAR(500) NULL,
  `occurred_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_touchpoint_customer_time` (`customer_id`,`occurred_at`),
  CONSTRAINT `fk_touchpoint_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restock_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_no` VARCHAR(40) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `warehouse_code` VARCHAR(64) NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'draft' COMMENT 'draft,pending_approval,approved,ordered,received,cancelled',
  `suggested_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `estimated_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `expected_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_restock_plan_no` (`plan_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restock_plan_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `sku_id` BIGINT UNSIGNED NOT NULL,
  `current_stock` INT UNSIGNED NOT NULL,
  `reserved_stock` INT UNSIGNED NOT NULL,
  `daily_sales` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `lead_days` INT UNSIGNED NOT NULL DEFAULT 7,
  `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `suggested_quantity` INT UNSIGNED NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_restock_plan_sku` (`plan_id`,`sku_id`),
  CONSTRAINT `fk_restock_item_plan` FOREIGN KEY (`plan_id`) REFERENCES `restock_plans` (`id`),
  CONSTRAINT `fk_restock_item_sku` FOREIGN KEY (`sku_id`) REFERENCES `product_skus` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_quality_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `score` TINYINT UNSIGNED NOT NULL,
  `missing_fields` JSON NULL,
  `has_sku` TINYINT NOT NULL,
  `has_assets` TINYINT NOT NULL,
  `has_storefront_seo` TINYINT NOT NULL,
  `checked_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_quality_product` (`product_id`),
  CONSTRAINT `fk_quality_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logistics_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` BIGINT UNSIGNED NOT NULL,
  `exception_type` VARCHAR(48) NOT NULL COMMENT 'pickup_timeout,address_error,delivery_failed,damaged,stalled',
  `severity` VARCHAR(16) NOT NULL DEFAULT 'medium',
  `status` VARCHAR(24) NOT NULL DEFAULT 'open' COMMENT 'open,processing,resolved,ignored',
  `description` VARCHAR(500) NOT NULL,
  `assigned_to` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_exception_status` (`status`,`severity`), KEY `idx_exception_package` (`package_id`),
  CONSTRAINT `fk_exception_package` FOREIGN KEY (`package_id`) REFERENCES `shipment_packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `notification_type` VARCHAR(48) NOT NULL COMMENT 'restock,logistics_exception,approval,security,system',
  `title` VARCHAR(160) NOT NULL,
  `content` VARCHAR(500) NOT NULL,
  `target_path` VARCHAR(255) NULL,
  `payload` JSON NULL,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_notification_member_read` (`member_id`,`read_at`,`created_at`),
  CONSTRAINT `fk_notification_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
