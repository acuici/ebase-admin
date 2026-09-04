-- 渠道订单行数据库级幂等键。
-- 仅新增字段和唯一索引；历史 order_items 保持 NULL，不回填、不猜测来源。
ALTER TABLE `order_items`
  ADD COLUMN `channel_order_item_key` VARCHAR(191) NULL AFTER `order_id`,
  ADD UNIQUE KEY `uk_order_item_channel_key` (`order_id`, `channel_order_item_key`),
  ADD KEY `idx_order_item_channel_key` (`channel_order_item_key`);
