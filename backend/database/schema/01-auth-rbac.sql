-- ============================================================
-- EBASE 数据库核心 Schema
-- 遵循 docs/DEVELOPMENT-STANDARDS.md
-- 命名：snake_case；时间戳 created_at/updated_at；金额 DECIMAL
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 认证与权限
-- ------------------------------------------------------------

-- 成员（后台内部员工）
DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`          VARCHAR(190)    NOT NULL COMMENT '登录邮箱',
  `name`           VARCHAR(80)     NOT NULL COMMENT '姓名',
  `password_hash`  VARCHAR(255)    NOT NULL COMMENT '密码哈希',
  `avatar`         VARCHAR(255)    NULL COMMENT '头像 URL',
  `status`         TINYINT         NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `is_super`       TINYINT         NOT NULL DEFAULT 0 COMMENT '1超级管理员',
  `last_login_at`  DATETIME        NULL,
  `created_at`     DATETIME        NULL,
  `updated_at`     DATETIME        NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台成员';

-- 角色
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(80)     NOT NULL COMMENT '角色名',
  `description`      VARCHAR(255)    NULL,
  `permission_codes` TEXT            NULL COMMENT '权限码，逗号分隔',
  `is_active`        TINYINT         NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `created_at`       DATETIME        NULL,
  `updated_at`       DATETIME        NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色';

-- 成员-角色关联
DROP TABLE IF EXISTS `member_roles`;
CREATE TABLE `member_roles` (
  `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `role_id`   BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME       NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_role` (`member_id`, `role_id`),
  KEY `idx_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成员角色关联';

-- 成员会话
DROP TABLE IF EXISTS `member_sessions`;
CREATE TABLE `member_sessions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id`  BIGINT UNSIGNED NOT NULL,
  `session_id` VARCHAR(128)    NOT NULL COMMENT '会话ID',
  `device`     VARCHAR(255)    NULL COMMENT '设备描述',
  `ip`         VARCHAR(45)     NULL,
  `revoked_at` DATETIME        NULL COMMENT '撤销时间',
  `created_at` DATETIME        NULL,
  `last_seen`  DATETIME        NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session` (`session_id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成员会话';

SET FOREIGN_KEY_CHECKS = 1;
