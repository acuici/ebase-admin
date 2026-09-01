-- 退款事实表：退款单号、金额、状态与渠道原始信息独立于订单和支付单。
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `refund_no` VARCHAR(40) NOT NULL,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'CNY',
  `channel` VARCHAR(32) NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending' COMMENT 'pending,processing,succeeded,failed',
  `reason` VARCHAR(255) NULL,
  `channel_refund_id` VARCHAR(128) NULL,
  `channel_payload` JSON NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uk_refund_no` (`refund_no`), UNIQUE KEY `uk_channel_refund` (`channel`,`channel_refund_id`), KEY `idx_refund_payment` (`payment_id`), KEY `idx_refund_order` (`order_id`),
  CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `fk_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='退款单';
