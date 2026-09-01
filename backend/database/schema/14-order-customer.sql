-- 订单关联消费者；后台成员 member_id 与消费者 customer_id 不能混用。
ALTER TABLE `orders`
  ADD COLUMN `customer_id` BIGINT UNSIGNED NULL AFTER `member_id`,
  ADD KEY `idx_order_customer` (`customer_id`),
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);
