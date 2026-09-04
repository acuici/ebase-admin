# 多渠道商品与 SKU 映射契约

状态：设计基线，尚未实施  
适用范围：ThinkPHP 8 API、Vue 3 运营后台、淘宝/天猫、京东、拼多多、抖音及后续渠道  
最后更新：2026-09-03

## 1. 目标与原则

EBASE 以 `products` 和 `product_skus` 作为内部商品事实库。平台商品、平台 SKU 和商家填写的 SKU 编码均为渠道映射，不得成为内部商品的主身份。

必须遵守以下原则：

- 内部 `product_skus.id` 是库存、订单和履约使用的 SKU 主身份。
- `product_skus.sku_code` 是内部业务编码；可以与平台商家 SKU 编码相同，但系统不得依赖二者相同。
- 平台自身分配的商品 ID、SKU ID 必须按“渠道 + 店铺”保存和查询。
- 同一平台允许绑定多个店铺；平台授权信息不得放入普通系统设置或业务响应。
- 商品主数据与渠道刊登配置分离；平台标题、类目、价格、图片、状态和错误不得覆盖 `products` 或 `product_skus`。
- 所有渠道订单商品必须先解析到内部 SKU，才允许进入正常库存扣减流程。
- 无映射、映射冲突或平台商品数据不完整时，订单应进入可追踪的异常流程，不得猜测 SKU 或静默跳过。
- 金额继续使用 `DECIMAL(12,2)`；数量使用整数；时间按现有 UTC 存储约定处理。

## 2. 领域关系

```text
products (内部 SPU)
  └─ product_skus (内部 SKU / 库存主身份)

channel_stores (渠道店铺及授权主体)
  └─ channel_products (某店铺的平台商品)
       └─ channel_product_skus (平台 SKU → 内部 SKU)

orders (统一订单)
  ├─ order_channel_extensions (渠道订单原始信息)
  ├─ order_items (已成功映射的内部 SKU 快照)
  └─ channel_order_item_exceptions (未映射/冲突商品)
```

独立站的 `storefront_sites` 和 `storefront_product_listings` 保持现状。首轮实施不得强行合并独立站模型；渠道接口可在服务层提供一致视图。

## 3. 数据表契约

### 3.1 `channel_stores`

表示一个已接入的外部平台店铺。

建议字段：

- `id BIGINT UNSIGNED`：内部主键。
- `store_code VARCHAR(64)`：EBASE 内部店铺编码，全局唯一。
- `channel_type VARCHAR(32)`：`tmall`、`taobao`、`jd`、`pdd`、`douyin`，后续可扩展。
- `external_store_id VARCHAR(128)`：平台店铺 ID，不使用整数类型。
- `name VARCHAR(160)`：后台展示名称。
- `status VARCHAR(24)`：`pending_auth`、`active`、`expired`、`disabled`。
- `authorization_status VARCHAR(24)`：`unbound`、`valid`、`expiring`、`expired`、`revoked`。
- `authorized_at DATETIME NULL`、`authorization_expires_at DATETIME NULL`。
- `last_synced_at DATETIME NULL`、`last_sync_error VARCHAR(500) NULL`。
- `created_at DATETIME`、`updated_at DATETIME`。

约束：

- 唯一键：`store_code`。
- 唯一键：`(channel_type, external_store_id)`。
- 索引：`(channel_type, status)`、`authorization_status`。
- Access Token、Refresh Token 等敏感凭据不得明文存入本表。凭据必须通过受控加密存储或适配器密钥引用管理，且不得出现在 API 响应和日志中。

### 3.2 `channel_products`

表示内部商品在某个渠道店铺中的一条平台商品记录。

建议字段：

- `id BIGINT UNSIGNED`。
- `channel_store_id BIGINT UNSIGNED`：关联 `channel_stores.id`。
- `product_id BIGINT UNSIGNED`：关联 `products.id`。
- `external_product_id VARCHAR(128)`：平台商品 ID。
- `merchant_product_code VARCHAR(128) NULL`：商家在平台填写的商品编码。
- `title VARCHAR(255) NULL`、`category_id VARCHAR(128) NULL`、`category_name VARCHAR(255) NULL`。
- `listing_status VARCHAR(24)`：`draft`、`pending_review`、`published`、`offline`、`rejected`、`archived`。
- `sync_status VARCHAR(24)`：`pending`、`syncing`、`synced`、`failed`、`conflict`。
- `platform_payload JSON NULL`：受控平台字段快照，不保存凭据。
- `last_synced_at DATETIME NULL`、`last_sync_error_code VARCHAR(64) NULL`、`last_sync_error VARCHAR(500) NULL`。
- `created_at DATETIME`、`updated_at DATETIME`。

约束：

- 唯一键：`(channel_store_id, external_product_id)`。
- 默认一个内部商品在同一店铺对应一个平台商品；唯一键：`(channel_store_id, product_id)`。若未来确认同店铺一对多刊登需求，必须先更新本契约再放宽。
- 外键：`channel_store_id → channel_stores.id`、`product_id → products.id`。
- 索引：`(product_id, listing_status)`、`(channel_store_id, sync_status)`。

### 3.3 `channel_product_skus`

表示平台 SKU 到内部 SKU 的明确映射，是本功能的核心事实表。

建议字段：

- `id BIGINT UNSIGNED`。
- `channel_product_id BIGINT UNSIGNED`：关联 `channel_products.id`。
- `product_sku_id BIGINT UNSIGNED`：关联 `product_skus.id`。
- `external_sku_id VARCHAR(128)`：平台自身 SKU ID。
- `merchant_sku_code VARCHAR(128) NULL`：商家在平台填写的 SKU 编码。
- `spec_snapshot JSON NULL`：平台规格快照。
- `channel_price DECIMAL(12,2) NULL`、`currency CHAR(3) NOT NULL DEFAULT 'CNY'`。
- `inventory_policy VARCHAR(24)`：`shared`、`allocated`、`disabled`。
- `allocated_quantity INT UNSIGNED NULL`：仅 `allocated` 策略使用。
- `listing_status VARCHAR(24)`、`sync_status VARCHAR(24)`。
- `last_inventory_synced_at DATETIME NULL`、`last_price_synced_at DATETIME NULL`。
- `last_sync_error_code VARCHAR(64) NULL`、`last_sync_error VARCHAR(500) NULL`。
- `created_at DATETIME`、`updated_at DATETIME`。

约束：

- 唯一键：`(channel_product_id, external_sku_id)`。
- 唯一键：`(channel_product_id, product_sku_id)`，避免同一平台商品内重复映射同一个内部 SKU。
- 不对 `merchant_sku_code` 单独设置全局唯一；部分平台允许重复或为空。
- 外键：`channel_product_id → channel_products.id`、`product_sku_id → product_skus.id`。
- 索引：`product_sku_id`、`(sync_status, updated_at)`。
- 写入时必须校验 `channel_products.product_id = product_skus.product_id`，防止跨商品错误映射。

### 3.4 `channel_order_item_exceptions`

记录渠道订单商品无法安全映射到内部 SKU 的异常。

建议字段：

- `id BIGINT UNSIGNED`。
- `order_id BIGINT UNSIGNED`。
- `channel_store_id BIGINT UNSIGNED NULL`。
- `external_order_item_id VARCHAR(128) NULL`。
- `external_product_id VARCHAR(128) NULL`。
- `external_sku_id VARCHAR(128) NULL`。
- `merchant_sku_code VARCHAR(128) NULL`。
- `reason_code VARCHAR(64)`：`STORE_NOT_FOUND`、`PRODUCT_NOT_MAPPED`、`SKU_NOT_MAPPED`、`MAPPING_CONFLICT`、`INVALID_QUANTITY`、`INVALID_AMOUNT`。
- `status VARCHAR(24)`：`pending`、`resolved`、`ignored`。
- `raw_item JSON`：受控原始商品行。
- `resolved_product_sku_id BIGINT UNSIGNED NULL`、`resolved_by BIGINT UNSIGNED NULL`、`resolved_at DATETIME NULL`。
- `created_at DATETIME`、`updated_at DATETIME`。

约束：

- 唯一键建议使用 `(order_id, external_order_item_id)`；平台不提供行 ID 时，由服务生成稳定的 `external_order_item_key` 并建立唯一键。
- 解决异常时必须在事务中创建或补全 `order_items`，并通过统一库存服务执行后续动作。

## 4. 渠道订单导入契约

`POST /api/v1/channel-orders/import` 的现有能力需要升级，但须保持已有调用兼容。

请求新增 `items`：

```json
{
  "channel_type": "jd",
  "channel_store_id": 12,
  "external_order_no": "JD-20260903-001",
  "status": "paid",
  "total_amount": "399.00",
  "currency": "CNY",
  "items": [
    {
      "external_order_item_id": "line-1",
      "external_product_id": "product-7788",
      "external_sku_id": "sku-9922",
      "merchant_sku_code": "SHOE-BLK-42",
      "product_name": "运动鞋",
      "spec_text": "黑色 / 42",
      "quantity": 2,
      "unit_price": "199.50"
    }
  ],
  "raw_payload": {}
}
```

处理规则：

1. 以 `(channel_type, channel_store_id, external_order_no)` 保证订单导入幂等。
2. `channel_store_id` 必须指向与 `channel_type` 匹配且有效的店铺；历史兼容调用可暂时允许空值，但含商品行的新调用不得为空。
3. 首选 `(channel_store_id, external_product_id, external_sku_id)` 查询映射。
4. `merchant_sku_code` 只能作为人工辅助信息，首版不得自动等同于内部 `sku_code`。
5. 成功映射的商品写入 `order_items`，保存内部 SKU、平台商品名称、规格、成交单价和数量快照。
6. 任一商品无法映射时写入异常表；订单标记为 `mapping_required` 或建立独立处理状态，不得扣减该商品库存。
7. 一个订单的库存处理必须定义清晰的一致性策略。首版采用“全部映射成功才处理库存”，避免部分扣减。
8. 重复回调或重复导入不得重复创建订单明细、库存流水或异常记录。
9. 支付状态与库存状态迁移必须调用现有领域服务，不得在控制器直接更新库存。

## 5. 库存同步契约

- 中央可售库存以 `product_skus.stock_quantity - reserved_quantity` 为事实来源。
- `shared`：向渠道推送中央可售库存，仍需考虑安全库存与平台在途订单延迟。
- `allocated`：渠道可售数不得超过 `allocated_quantity` 与中央可售库存中的较小值。
- `disabled`：不自动推送库存。
- 平台库存同步必须通过适配器和可靠任务执行，不得把外部 API 调用放进数据库事务。
- 任务必须包含唯一业务键，例如 `store:{storeId}:sku:{mappingId}:stock:{version}`，重复消费安全。
- 同步失败记录错误码、简要错误、重试次数和最近时间；敏感响应不得落库。
- 首轮只建立通用接口、任务事实与测试替身，不要求在没有平台资质时伪造真实平台 API。

## 6. REST API 基线

所有接口使用 `/api/v1`、统一响应结构、独立 Validate 类、认证与权限中间件。

### 店铺

- `GET /api/v1/channel-stores`
- `POST /api/v1/channel-stores`
- `GET /api/v1/channel-stores/{id}`
- `PATCH /api/v1/channel-stores/{id}`
- `POST /api/v1/channel-stores/{id}/disable`
- `POST /api/v1/channel-stores/{id}/sync`

### 平台商品与 SKU 映射

- `GET /api/v1/channel-products`
- `POST /api/v1/channel-products`
- `GET /api/v1/channel-products/{id}`
- `PATCH /api/v1/channel-products/{id}`
- `DELETE /api/v1/channel-products/{id}`：仅允许无订单历史时删除，否则归档。
- `POST /api/v1/channel-products/{id}/skus`
- `PATCH /api/v1/channel-product-skus/{id}`
- `DELETE /api/v1/channel-product-skus/{id}`：有历史引用时禁止硬删除。
- `POST /api/v1/channel-products/{id}/sync`

### 映射异常

- `GET /api/v1/channel-order-item-exceptions`
- `GET /api/v1/channel-order-item-exceptions/{id}`
- `POST /api/v1/channel-order-item-exceptions/{id}/resolve`
- `POST /api/v1/channel-order-item-exceptions/{id}/ignore`

列表接口必须支持服务端分页、关键词、渠道、店铺、状态和错误类型筛选，并将排序字段白名单化。

## 7. 权限码

- `channel.store.read`
- `channel.store.create`
- `channel.store.update`
- `channel.store.disable`
- `channel.store.sync`
- `channel.product.read`
- `channel.product.create`
- `channel.product.update`
- `channel.product.archive`
- `channel.product.sync`
- `channel.mapping.read`
- `channel.mapping.manage`
- `channel.order_exception.read`
- `channel.order_exception.resolve`

后端默认拒绝访问；前端权限只控制可见性，不能替代中间件授权。

## 8. 错误码

除通用错误码外，增加：

- `CHANNEL_STORE_NOT_FOUND`
- `CHANNEL_STORE_INACTIVE`
- `CHANNEL_PRODUCT_CONFLICT`
- `CHANNEL_SKU_MAPPING_NOT_FOUND`
- `CHANNEL_SKU_MAPPING_CONFLICT`
- `CHANNEL_ORDER_MAPPING_REQUIRED`
- `CHANNEL_SYNC_FAILED`

错误响应必须包含 `request_id`；批量同步可返回逐项结果，但不得用整体成功掩盖失败项。

## 9. 前端页面基线

在现有后台视觉系统中扩展，不引入第二套组件库或 CSS 框架：

- 渠道店铺列表：搜索、渠道/授权状态筛选、授权状态、最近同步、失败原因。
- 平台商品列表：内部商品、渠道、店铺、平台商品 ID、刊登/同步状态。
- 商品映射详情：内部 SPU 与平台商品信息、内部 SKU 与平台 SKU 的逐行映射。
- 映射异常中心：订单、店铺、平台商品/SKU、原因、原始信息和解决操作。
- 所有页面包含加载、空、筛选无结果、错误、成功反馈和危险操作确认。
- 筛选与分页同步到 URL Query。
- Vue 组件仅通过统一 API Client 访问后端，不得直接调用 `fetch`/`axios`。

## 10. 兼容迁移与验收

- 迁移不得删除或重写现有 `products`、`product_skus`、`orders`、`storefront_*` 数据。
- 当前历史订单的 `channel_store_id` 大量为空，迁移不得强制补造店铺关系；这些记录保留为历史未归属数据。
- 新接口上线后，含 `items` 的新渠道订单必须要求有效店铺；旧请求的淘汰时间另行决定。
- 新增外键前必须验证已有数据；新增唯一键前必须提供重复数据检查。
- 后端至少覆盖：唯一约束、跨商品映射拒绝、无映射异常、重复导入、全部映射成功、库存不足、事务回滚和越权。
- 前端至少覆盖：加载/空/错误、筛选分页、创建映射、冲突提示、解决异常和危险操作确认。
- 交付必须更新 `backend/docs/openapi.yaml`、本契约、`docs/PROJECT-INVENTORY.md`，并运行后端测试、静态检查与 `npm run build`。

## 11. 尚待产品确认

以下问题不得由任一 Agent 擅自决定：

- 同一内部商品是否允许在同一店铺创建多个平台商品。
- 渠道分配库存是否需要仓库维度。
- 映射异常解决后，已支付订单是否自动扣库存，还是进入人工复核。
- 平台售价以 SKU 为准还是允许商品级统一价格覆盖。
- 平台授权凭据最终采用数据库加密、密钥管理服务还是外部凭据服务。

