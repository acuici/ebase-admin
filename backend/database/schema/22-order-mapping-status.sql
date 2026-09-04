ALTER TABLE `order_channel_extensions`
  ADD COLUMN `mapping_status` VARCHAR(24) NOT NULL DEFAULT 'pending' AFTER `raw_payload`,
  ADD KEY `idx_extension_mapping_status` (`mapping_status`, `updated_at`);
