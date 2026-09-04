ALTER TABLE `order_channel_extensions`
  ADD COLUMN `inventory_processed_at` DATETIME NULL AFTER `mapping_status`,
  ADD KEY `idx_extension_inventory_processed` (`inventory_processed_at`);
