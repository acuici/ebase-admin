-- 商品类目字段，避免把品牌误显示为类目。
-- 新环境由 database/schema/02-catalog-order.sql 创建；此迁移仅修复已有商品数据。
UPDATE `products`
SET `category` = CASE
  WHEN `name` LIKE '%耳机%' OR `name` LIKE '%音频%' THEN '数码影音'
  WHEN `name` LIKE '%键盘%' OR `name` LIKE '%鼠标%' THEN '电脑外设'
  WHEN `name` LIKE '%手表%' THEN '智能穿戴'
  WHEN `name` LIKE '%旅行%' OR `name` LIKE '%收纳%' THEN '箱包出行'
  ELSE '其他商品'
END
WHERE `category` IS NULL OR `category` = '';
