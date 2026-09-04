-- 为固定角色 ID 追加渠道映射权限。避免依赖数据库中可能已经乱码的角色名称。
UPDATE `roles`
SET `permission_codes` = CONCAT_WS(',', NULLIF(`permission_codes`, ''), 'channel.order.import', 'channel.order.inventory_confirm', 'channel.store.read', 'channel.store.create', 'channel.store.update', 'channel.store.disable', 'channel.store.sync', 'channel.product.read', 'channel.product.create', 'channel.product.update', 'channel.product.archive', 'channel.product.sync', 'channel.mapping.read', 'channel.mapping.manage', 'channel.order_exception.read', 'channel.order_exception.resolve')
WHERE `id` = 1
  AND `permission_codes` <> '*';
