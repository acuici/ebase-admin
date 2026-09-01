-- Query indexes for high-volume demo and production data.
ALTER TABLE `orders`
  ADD KEY `idx_orders_created_status` (`created_at`,`status`),
  ADD KEY `idx_orders_channel_status` (`channel_type`,`status`,`created_at`);
ALTER TABLE `order_items`
  ADD KEY `idx_order_items_sku_order` (`sku_id`,`order_id`);
ALTER TABLE `customer_addresses`
  ADD KEY `idx_customer_addresses_customer_default` (`customer_id`,`is_default`);
ALTER TABLE `customer_touchpoints`
  ADD KEY `idx_touchpoints_type_time` (`touchpoint_type`,`occurred_at`);
ALTER TABLE `product_skus`
  ADD KEY `idx_product_skus_stock_status` (`status`,`stock_quantity`,`reserved_quantity`);
