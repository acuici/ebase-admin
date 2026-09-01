-- 物流事件去重：承运商回调重放不得产生重复轨迹。
ALTER TABLE `shipment_tracking_events`
  ADD COLUMN `external_event_id` VARCHAR(128) NULL AFTER `event_code`,
  ADD UNIQUE KEY `uk_tracking_external_event` (`package_id`,`external_event_id`);
