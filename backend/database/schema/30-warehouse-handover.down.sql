-- Rollback for 30-warehouse-handover.sql.
DROP TABLE IF EXISTS `warehouse_handover_records`;
-- Remove only the unused default templates created by this migration.
DELETE r FROM `roles` r
WHERE r.`name` = '仓库主管'
  AND r.`permission_codes` = 'inventory.warehouse.read,inventory.warehouse.manage,inventory.warehouse.handover'
  AND NOT EXISTS (SELECT 1 FROM `member_roles` mr WHERE mr.`role_id` = r.`id`);
DELETE r FROM `roles` r
WHERE r.`name` = '商品运营专员'
  AND r.`permission_codes` = 'catalog.product.read,catalog.product.update,inventory.warehouse.read'
  AND NOT EXISTS (SELECT 1 FROM `member_roles` mr WHERE mr.`role_id` = r.`id`);
