-- Categories required by the secondary CRUD and category filter contract.
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_code` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_category_code` (`category_code`), KEY `idx_category_parent_status` (`parent_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
