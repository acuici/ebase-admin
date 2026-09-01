-- 素材库：附件事实数据独立于商品、内容和独立站渠道配置。
CREATE TABLE IF NOT EXISTS `assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `storage_driver` VARCHAR(24) NOT NULL DEFAULT 'local',
  `storage_path` VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(120) NOT NULL,
  `extension` VARCHAR(16) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL,
  `sha256` CHAR(64) NOT NULL,
  `visibility` VARCHAR(24) NOT NULL DEFAULT 'private' COMMENT 'private,public',
  `uploaded_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asset_sha_path` (`sha256`,`storage_path`),
  KEY `idx_asset_uploaded_by` (`uploaded_by`),
  KEY `idx_asset_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='素材库';

CREATE TABLE IF NOT EXISTS `asset_relations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `relation_type` VARCHAR(64) NOT NULL COMMENT 'product,product_listing,content',
  `relation_id` BIGINT UNSIGNED NOT NULL,
  `purpose` VARCHAR(64) NOT NULL DEFAULT 'default' COMMENT 'cover,gallery,detail,attachment',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asset_relation` (`asset_id`,`relation_type`,`relation_id`,`purpose`),
  KEY `idx_relation_target` (`relation_type`,`relation_id`,`sort_order`),
  CONSTRAINT `fk_asset_relation_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='素材关联';
