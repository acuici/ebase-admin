-- Rollback for 29-warehouse-operational-facts.sql.
ALTER TABLE `warehouses`
  DROP INDEX `idx_warehouse_owner_city_status`,
  DROP COLUMN `outbound_quantity`,
  DROP COLUMN `inbound_quantity`,
  DROP COLUMN `capacity_rate`,
  DROP COLUMN `city`,
  DROP COLUMN `owner_code`;
