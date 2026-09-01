-- 支付领域：渠道无关的事实表，回调原文留存并保证幂等。
CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_no` VARCHAR(40) NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `channel` VARCHAR(32) NOT NULL COMMENT 'wechat_pay,alipay,douyin_pay,jd_pay',
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending' COMMENT 'pending,paid,closed,refunded,failed',
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'CNY',
  `channel_transaction_id` VARCHAR(128) NULL,
  `channel_payload` JSON NULL COMMENT '受控渠道原始数据，禁止存完整密钥',
  `paid_at` DATETIME NULL,
  `closed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_no` (`payment_no`),
  UNIQUE KEY `uk_channel_transaction` (`channel`,`channel_transaction_id`),
  KEY `idx_payment_order` (`order_id`),
  KEY `idx_payment_status` (`status`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付单';

CREATE TABLE IF NOT EXISTS `payment_callback_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel` VARCHAR(32) NOT NULL,
  `event_id` VARCHAR(128) NOT NULL,
  `payment_no` VARCHAR(40) NULL,
  `signature_valid` TINYINT NOT NULL,
  `headers_json` JSON NULL,
  `payload_json` JSON NULL,
  `processed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_callback_event` (`channel`,`event_id`),
  KEY `idx_callback_payment` (`payment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付回调审计';

CREATE TABLE IF NOT EXISTS `idempotency_keys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` VARCHAR(64) NOT NULL,
  `idempotency_key` VARCHAR(128) NOT NULL,
  `response_code` VARCHAR(32) NULL,
  `response_body` JSON NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_idempotency` (`scope`,`idempotency_key`),
  KEY `idx_idempotency_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='业务幂等键';
