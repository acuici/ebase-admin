# Hermes / PHP Agent 任务单：多渠道商品与 SKU 映射

状态：待执行  
依赖契约：`docs/MULTI-CHANNEL-PRODUCT-CONTRACT.md`

## 任务边界

负责 MySQL、ThinkPHP 8、REST API、领域服务、权限、OpenAPI 和后端测试。不要修改 Vue 页面，不接入未经确认的真实平台凭据，不改写现有业务数据。

## 开始前

1. 完整阅读根目录 `AGENTS.md`、`docs/DEVELOPMENT-STANDARDS.md`、`docs/PROJECT-INVENTORY.md` 和共享契约。
2. 检查本地实际 MySQL，而非只阅读 Schema 文件。
3. 检查工作区状态，保留其他 Agent 的改动。
4. 先提交迁移、接口和兼容性设计供审查；设计未确认前不要实施。

## 实施内容

1. 新增按顺序执行且可审查的 Schema：
   - `channel_stores`
   - `channel_products`
   - `channel_product_skus`
   - `channel_order_item_exceptions`
   - 如可靠库存同步确有需要，新增通用同步任务/事件表；优先复用现有 `job_outbox`。
2. 新增模型、Validate、Service、Controller 和 `/api/v1` 路由。
3. 为所有管理接口配置认证与契约规定的权限码。
4. 扩展渠道订单导入请求，解析商品行并按平台 SKU 映射到内部 SKU。
5. 成功映射时创建 `order_items`；无映射或冲突时创建可追踪异常。
6. 首版执行“全部商品映射成功才进入库存处理”，避免部分扣减。
7. 重复导入不得重复创建订单、明细、异常、库存流水或任务。
8. 库存变更必须复用领域服务、数据库事务和库存流水；外部同步通过可靠任务与平台适配器执行。
9. 不得依赖 `merchant_sku_code === product_skus.sku_code`。
10. 更新 `backend/docs/openapi.yaml` 和相关项目文档。

## 必须先回答的设计问题

- 迁移文件编号、执行顺序和回滚/兼容策略是什么？
- 平台授权凭据只保存引用还是新增加密表？首轮建议仅保存引用，避免扩大安全范围。
- 订单异常状态放在 `orders.status`，还是单独增加映射处理状态？不得破坏现有订单状态机。
- 如何生成缺少平台订单行 ID 时的稳定幂等键？
- 如何保证同一平台商品中的 SKU 不会映射到其他内部商品？
- 库存同步如何复用 `job_outbox`，其唯一业务键放在哪里？

## 后端验收用例

- 同渠道店铺外部 ID 重复时返回冲突。
- 同一平台 SKU 重复映射时返回冲突。
- 平台商品与内部 SKU 分属不同商品时拒绝。
- 内部编码与平台商家 SKU 编码不同仍可正常映射。
- 无映射订单创建异常且不扣库存。
- 多行订单中一行无映射时不发生部分扣减。
- 重复导入结果稳定，不重复写任何业务事实。
- 已支付且全部映射订单通过既有状态机正确处理库存。
- 库存不足完整回滚。
- 未授权角色访问接口返回 403。
- API 响应不包含 Token、密钥或完整敏感平台数据。

## 完成定义

- 迁移兼容当前已有数据并在本地 MySQL 验证。
- 后端测试和静态检查通过。
- OpenAPI 与真实响应一致。
- `docs/PROJECT-INVENTORY.md` 与共享契约同步更新。
- 给出变更文件、测试证据、已知限制和供 Vue Agent 使用的接口摘要。

