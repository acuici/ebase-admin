-- Member personal settings are separate from identity/auth fields.
CREATE TABLE IF NOT EXISTS `member_profiles` (
  `member_id` BIGINT UNSIGNED NOT NULL,
  `phone` VARCHAR(32) NULL,
  `job_title` VARCHAR(120) NULL,
  `department` VARCHAR(120) NULL,
  `locale` VARCHAR(16) NOT NULL DEFAULT 'zh-CN',
  `notification_preferences` JSON NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`member_id`),
  CONSTRAINT `fk_profile_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成员个人资料与偏好';
