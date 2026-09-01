-- 渠道订单原始数据与履约扩展；orders 保持跨渠道的订单事实主模型。
CREATE TABLE IF NOT EXISTS `order_channel_extensions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `channel_type` VARCHAR(32) NOT NULL,
  `channel_store_id` BIGINT UNSIGNED NULL,
  `external_order_no` VARCHAR(128) NOT NULL,
  `buyer_external_id` VARCHAR(128) NULL,
  `raw_payload` JSON NOT NULL COMMENT '渠道原始订单字段，受控保存，不存密钥',
  `imported_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_channel_order` (`channel_type`,`channel_store_id`,`external_order_no`),
  KEY `idx_extension_order` (`order_id`),
  CONSTRAINT `fk_extension_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='渠道订单原始扩展字段';

CREATE TABLE IF NOT EXISTS `fulfillments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fulfillment_no` VARCHAR(40) NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `warehouse_code` VARCHAR(64) NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending' COMMENT 'pending,processing,shipped,delivered,cancelled',
  `recipient_snapshot` JSON NOT NULL COMMENT '下单时收件信息快照',
  `shipping_method` VARCHAR(80) NULL,
  `shipped_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_fulfillment_no` (`fulfillment_no`), KEY `idx_fulfillment_order` (`order_id`),
  CONSTRAINT `fk_fulfillment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='履约单';

CREATE TABLE IF NOT EXISTS `shipment_packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fulfillment_id` BIGINT UNSIGNED NOT NULL,
  `package_no` VARCHAR(40) NOT NULL,
  `carrier_code` VARCHAR(64) NOT NULL,
  `tracking_no` VARCHAR(128) NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending',
  `label_url` VARCHAR(500) NULL,
  `carrier_payload` JSON NULL COMMENT '物流服务商原始响应',
  `shipped_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_tracking_carrier` (`carrier_code`,`tracking_no`), UNIQUE KEY `uk_package_no` (`package_no`), KEY `idx_package_fulfillment` (`fulfillment_id`),
  CONSTRAINT `fk_package_fulfillment` FOREIGN KEY (`fulfillment_id`) REFERENCES `fulfillments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='履约包裹';

CREATE TABLE IF NOT EXISTS `shipment_tracking_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` BIGINT UNSIGNED NOT NULL,
  `event_code` VARCHAR(64) NULL,
  `event_status` VARCHAR(64) NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `location` VARCHAR(255) NULL,
  `occurred_at` DATETIME NOT NULL,
  `raw_payload` JSON NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), KEY `idx_tracking_package_time` (`package_id`,`occurred_at`),
  CONSTRAINT `fk_tracking_package` FOREIGN KEY (`package_id`) REFERENCES `shipment_packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物流轨迹事件';
