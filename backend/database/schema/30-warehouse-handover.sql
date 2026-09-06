-- Warehouse ownership and member handover facts.
CREATE TABLE `warehouse_handover_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `from_member_id` BIGINT UNSIGNED NULL,
  `to_member_id` BIGINT UNSIGNED NOT NULL,
  `handover_type` VARCHAR(24) NOT NULL DEFAULT 'temporary' COMMENT 'temporary,permanent,restore',
  `status` VARCHAR(24) NOT NULL DEFAULT 'active' COMMENT 'active,completed,cancelled',
  `reason` VARCHAR(255) NULL,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_handover_warehouse_status` (`warehouse_id`, `status`),
  KEY `idx_handover_from_member_status` (`from_member_id`, `status`),
  KEY `idx_handover_to_member_status` (`to_member_id`, `status`),
  CONSTRAINT `fk_handover_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `fk_handover_from_member` FOREIGN KEY (`from_member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fk_handover_to_member` FOREIGN KEY (`to_member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fk_handover_created_by` FOREIGN KEY (`created_by`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `description`, `permission_codes`, `is_active`, `created_at`, `updated_at`)
SELECT '仓库主管', '管理仓库资料、负责人和交接', 'inventory.warehouse.read,inventory.warehouse.manage,inventory.warehouse.handover', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = '仓库主管');

INSERT INTO `roles` (`name`, `description`, `permission_codes`, `is_active`, `created_at`, `updated_at`)
SELECT '商品运营专员', '管理商品、库存和商品运营任务', 'catalog.product.read,catalog.product.update,inventory.warehouse.read', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = '商品运营专员');
