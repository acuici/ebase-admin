-- 系统设置统一事实表。
-- setting_group: company,members,channels,warehouse,payment,notifications,security
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_group` VARCHAR(32) NOT NULL,
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` JSON NOT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_group`,`setting_key`),
  KEY `idx_setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置';

CREATE TABLE IF NOT EXISTS `operation_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operator_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(64) NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `resource_type` VARCHAR(64) NULL,
  `resource_id` VARCHAR(64) NULL,
  `result` VARCHAR(24) NOT NULL DEFAULT 'success',
  `risk_level` VARCHAR(16) NOT NULL DEFAULT 'low',
  `ip` VARCHAR(45) NULL,
  `detail` JSON NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_operation_time` (`created_at`),
  KEY `idx_operation_module` (`module`,`created_at`),
  KEY `idx_operation_operator` (`operator_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台操作日志';
