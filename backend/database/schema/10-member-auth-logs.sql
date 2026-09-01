-- 成员安全审计：登录成功/失败、登出、密码重置与会话撤销。
CREATE TABLE IF NOT EXISTS `member_auth_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(190) NULL,
  `event_type` VARCHAR(48) NOT NULL COMMENT 'login_success,login_failed,logout,password_reset,session_revoked',
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `metadata` JSON NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_auth_log_member_time` (`member_id`,`created_at`),
  KEY `idx_auth_log_email_time` (`email`,`created_at`),
  KEY `idx_auth_log_event_time` (`event_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成员登录与安全审计日志';
