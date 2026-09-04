-- 多渠道店铺映射基础表。兼容迁移，不修改既有业务事实。
CREATE TABLE IF NOT EXISTS `channel_stores` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_code` VARCHAR(64) NOT NULL,
  `channel_type` VARCHAR(32) NOT NULL,
  `external_store_id` VARCHAR(128) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending_auth',
  `authorization_status` VARCHAR(24) NOT NULL DEFAULT 'unbound',
  `authorized_at` DATETIME NULL,
  `authorization_expires_at` DATETIME NULL,
  `last_synced_at` DATETIME NULL,
  `last_sync_error_code` VARCHAR(64) NULL,
  `last_sync_error` VARCHAR(500) NULL,
  `credential_ref` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_channel_store_code` (`store_code`),
  UNIQUE KEY `uk_channel_external_store` (`channel_type`, `external_store_id`),
  KEY `idx_channel_store_status` (`channel_type`, `status`),
  KEY `idx_channel_store_authorization` (`authorization_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='外部平台店铺';
