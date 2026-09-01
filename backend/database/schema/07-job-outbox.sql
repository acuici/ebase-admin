-- 可靠任务表：MySQL 是任务事实来源；Redis 仅作为后续队列传输层。
CREATE TABLE IF NOT EXISTS `job_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_key` VARCHAR(160) NOT NULL,
  `job_type` VARCHAR(80) NOT NULL,
  `payload` JSON NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending' COMMENT 'pending,processing,completed,failed,dead',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` INT UNSIGNED NOT NULL DEFAULT 5,
  `available_at` DATETIME NOT NULL,
  `locked_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `last_error` VARCHAR(1000) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_job_key` (`job_key`),
  KEY `idx_job_poll` (`status`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='可靠异步任务';
