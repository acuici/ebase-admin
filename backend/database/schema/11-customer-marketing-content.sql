-- 消费者、营销、内容审核与物流订阅领域。
CREATE TABLE IF NOT EXISTS `customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_no` VARCHAR(40) NOT NULL,
  `email` VARCHAR(190) NULL,
  `phone` VARCHAR(32) NULL,
  `name` VARCHAR(120) NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'active',
  `source_channel` VARCHAR(32) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_customer_no` (`customer_no`), UNIQUE KEY `uk_customer_email` (`email`), KEY `idx_customer_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `recipient_name` VARCHAR(80) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `province` VARCHAR(80) NOT NULL,
  `city` VARCHAR(80) NOT NULL,
  `district` VARCHAR(80) NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `is_default` TINYINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_address_customer` (`customer_id`), CONSTRAINT `fk_address_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_tags` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`name` VARCHAR(80) NOT NULL,`color` VARCHAR(16) NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `uk_customer_tag_name` (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `customer_tag_relations` (`customer_id` BIGINT UNSIGNED NOT NULL,`tag_id` BIGINT UNSIGNED NOT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`customer_id`,`tag_id`),CONSTRAINT `fk_tag_relation_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),CONSTRAINT `fk_tag_relation_tag` FOREIGN KEY (`tag_id`) REFERENCES `customer_tags` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `customer_segments` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`name` VARCHAR(120) NOT NULL,`description` VARCHAR(255) NULL,`rules` JSON NOT NULL,`status` VARCHAR(24) NOT NULL DEFAULT 'active',`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `uk_segment_name` (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`code` VARCHAR(64) NOT NULL,`name` VARCHAR(120) NOT NULL,`discount_type` VARCHAR(24) NOT NULL,`discount_value` DECIMAL(12,2) NOT NULL,`min_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,`total_quantity` INT UNSIGNED NOT NULL,`claimed_quantity` INT UNSIGNED NOT NULL DEFAULT 0,`status` VARCHAR(24) NOT NULL DEFAULT 'draft',`starts_at` DATETIME NULL,`ends_at` DATETIME NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `uk_coupon_code` (`code`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `coupon_claims` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`coupon_id` BIGINT UNSIGNED NOT NULL,`customer_id` BIGINT UNSIGNED NOT NULL,`status` VARCHAR(24) NOT NULL DEFAULT 'available',`used_order_id` BIGINT UNSIGNED NULL,`claimed_at` DATETIME NOT NULL,`used_at` DATETIME NULL,PRIMARY KEY (`id`),UNIQUE KEY `uk_coupon_customer` (`coupon_id`,`customer_id`),KEY `idx_claim_customer` (`customer_id`),CONSTRAINT `fk_claim_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`),CONSTRAINT `fk_claim_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `marketing_campaigns` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`name` VARCHAR(160) NOT NULL,`campaign_type` VARCHAR(32) NOT NULL,`status` VARCHAR(24) NOT NULL DEFAULT 'draft',`audience_rules` JSON NULL,`budget` DECIMAL(12,2) NULL,`starts_at` DATETIME NULL,`ends_at` DATETIME NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `approval_requests` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`request_type` VARCHAR(48) NOT NULL,`resource_id` BIGINT UNSIGNED NOT NULL,`status` VARCHAR(24) NOT NULL DEFAULT 'pending',`submitted_by` BIGINT UNSIGNED NOT NULL,`reviewed_by` BIGINT UNSIGNED NULL,`reviewed_at` DATETIME NULL,`comment` VARCHAR(500) NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `idx_approval_resource` (`request_type`,`resource_id`,`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_review_requests` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`content_id` BIGINT UNSIGNED NOT NULL,`status` VARCHAR(24) NOT NULL DEFAULT 'pending',`submitted_by` BIGINT UNSIGNED NOT NULL,`reviewed_by` BIGINT UNSIGNED NULL,`reviewed_at` DATETIME NULL,`comment` VARCHAR(500) NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `idx_content_review` (`content_id`,`status`),CONSTRAINT `fk_review_content` FOREIGN KEY (`content_id`) REFERENCES `storefront_content` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logistics_subscriptions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`package_id` BIGINT UNSIGNED NOT NULL,`provider` VARCHAR(48) NOT NULL,`callback_url` VARCHAR(500) NOT NULL,`status` VARCHAR(24) NOT NULL DEFAULT 'active',`last_polled_at` DATETIME NULL,`last_event_at` DATETIME NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `uk_logistics_subscription` (`package_id`,`provider`),CONSTRAINT `fk_subscription_package` FOREIGN KEY (`package_id`) REFERENCES `shipment_packages` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
