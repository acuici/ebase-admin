-- EBASE 商品、库存、订单核心表
CREATE TABLE IF NOT EXISTS `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_no` VARCHAR(32) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `brand` VARCHAR(80) NULL,
  `description` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft,active,archived',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_product_no` (`product_no`), KEY `idx_product_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_skus` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `sku_code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `specs` JSON NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `market_price` DECIMAL(12,2) NULL,
  `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_sku_code` (`sku_code`), KEY `idx_sku_product` (`product_id`),
  CONSTRAINT `fk_sku_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_ledgers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku_id` BIGINT UNSIGNED NOT NULL,
  `change_quantity` INT NOT NULL,
  `before_quantity` INT UNSIGNED NOT NULL,
  `after_quantity` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(40) NOT NULL COMMENT 'restock,reserve,release,deduct',
  `reference_type` VARCHAR(40) NULL,
  `reference_id` VARCHAR(64) NULL,
  `operator_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_ledger_sku` (`sku_id`), KEY `idx_ledger_ref` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(32) NOT NULL,
  `member_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending_payment' COMMENT 'pending_payment,paid,processing,shipped,completed,cancelled',
  `total_amount` DECIMAL(12,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'CNY',
  `expires_at` DATETIME NULL,
  `paid_at` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_order_no` (`order_no`), KEY `idx_order_member` (`member_id`), KEY `idx_order_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `sku_id` BIGINT UNSIGNED NOT NULL,
  `sku_code` VARCHAR(64) NOT NULL,
  `product_name` VARCHAR(160) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_item_order` (`order_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_status_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `from_status` VARCHAR(24) NULL,
  `to_status` VARCHAR(24) NOT NULL,
  `operator_id` BIGINT UNSIGNED NULL,
  `source` VARCHAR(40) NOT NULL,
  `remark` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_status_log_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
