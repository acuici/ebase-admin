-- Warehouse operational facts used by the secondary warehouse form.
ALTER TABLE `warehouses`
  ADD COLUMN `owner_code` VARCHAR(64) NULL AFTER `name`,
  ADD COLUMN `city` VARCHAR(80) NULL AFTER `owner_code`,
  ADD COLUMN `capacity_rate` DECIMAL(5,2) NULL AFTER `city`,
  ADD COLUMN `inbound_quantity` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `capacity_rate`,
  ADD COLUMN `outbound_quantity` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `inbound_quantity`,
  ADD KEY `idx_warehouse_owner_city_status` (`owner_code`, `city`, `status`);
