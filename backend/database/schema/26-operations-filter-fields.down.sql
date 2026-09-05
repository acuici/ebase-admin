-- Rollback for 26-operations-filter-fields.sql.
DROP TABLE IF EXISTS `member_data_scopes`;
DROP TABLE IF EXISTS `warehouses`;
DROP TABLE IF EXISTS `suppliers`;
ALTER TABLE `marketing_campaigns` DROP INDEX `idx_campaign_channel_owner_dates`, DROP COLUMN `created_by`, DROP COLUMN `publish_channel`;
ALTER TABLE `coupons` DROP INDEX `idx_coupon_channel_audience`, DROP COLUMN `audience`, DROP COLUMN `applicable_channel`;
ALTER TABLE `customers` DROP INDEX `idx_customer_tier_status`, DROP COLUMN `member_tier`;
ALTER TABLE `product_skus` DROP INDEX `idx_sku_warehouse_supplier`, DROP COLUMN `supplier_id`, DROP COLUMN `warehouse_code`;
