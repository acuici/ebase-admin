-- 独立站：站点与渠道商品配置，严格与 products 主数据分离。
CREATE TABLE IF NOT EXISTS `storefront_sites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `brand_name` VARCHAR(120) NULL,
  `service_email` VARCHAR(190) NULL,
  `default_locale` VARCHAR(16) NOT NULL DEFAULT 'zh-CN',
  `currency` CHAR(3) NOT NULL DEFAULT 'CNY',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Shanghai',
  `status` VARCHAR(24) NOT NULL DEFAULT 'draft' COMMENT 'draft,active,maintenance,disabled',
  `default_seo_title` VARCHAR(255) NULL,
  `default_seo_description` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_site_code` (`site_code`), KEY `idx_site_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='独立站站点';

CREATE TABLE IF NOT EXISTS `storefront_domains` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `domain` VARCHAR(253) NOT NULL,
  `domain_type` VARCHAR(24) NOT NULL DEFAULT 'primary' COMMENT 'primary,redirect,custom',
  `dns_status` VARCHAR(24) NOT NULL DEFAULT 'pending' COMMENT 'pending,verified,failed',
  `https_status` VARCHAR(24) NOT NULL DEFAULT 'pending' COMMENT 'pending,active,failed',
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending',
  `verification_token` VARCHAR(128) NULL,
  `certificate_expires_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_domain` (`domain`), KEY `idx_domain_site` (`site_id`),
  CONSTRAINT `fk_domain_site` FOREIGN KEY (`site_id`) REFERENCES `storefront_sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='独立站域名';

CREATE TABLE IF NOT EXISTS `storefront_product_listings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'draft' COMMENT 'draft,published,scheduled,archived',
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(12,2) NULL COMMENT 'NULL = follow SKU price',
  `inventory_policy` VARCHAR(32) NOT NULL DEFAULT 'shared' COMMENT 'shared,allocated',
  `seo_title` VARCHAR(255) NULL,
  `seo_description` VARCHAR(500) NULL,
  `published_at` DATETIME NULL,
  `scheduled_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_site_slug` (`site_id`,`slug`), UNIQUE KEY `uk_site_product` (`site_id`,`product_id`),
  KEY `idx_listing_status` (`status`),
  CONSTRAINT `fk_listing_site` FOREIGN KEY (`site_id`) REFERENCES `storefront_sites` (`id`),
  CONSTRAINT `fk_listing_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='独立站渠道商品配置';

CREATE TABLE IF NOT EXISTS `storefront_content` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `content_type` VARCHAR(32) NOT NULL COMMENT 'theme,navigation,page,policy,campaign,seo_redirect',
  `content_key` VARCHAR(120) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'draft',
  `payload` JSON NOT NULL,
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_content_key` (`site_id`,`content_type`,`content_key`),
  KEY `idx_content_site_type` (`site_id`,`content_type`,`status`),
  CONSTRAINT `fk_content_site` FOREIGN KEY (`site_id`) REFERENCES `storefront_sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='独立站内容、主题、导航与 SEO 配置';

ALTER TABLE `orders`
  ADD COLUMN `channel_type` VARCHAR(32) NULL AFTER `member_id`,
  ADD COLUMN `channel_store_id` BIGINT UNSIGNED NULL AFTER `channel_type`,
  ADD COLUMN `external_order_no` VARCHAR(128) NULL AFTER `channel_store_id`,
  ADD KEY `idx_order_channel` (`channel_type`,`channel_store_id`),
  ADD UNIQUE KEY `uk_external_order` (`channel_type`,`channel_store_id`,`external_order_no`);
