# EBASE 开发规范

本文定义生产实现的工程边界。关键词 **MUST / MUST NOT / SHOULD** 分别表示必须、禁止和建议。根目录 `AGENTS.md` 为最短强制入口，本文件提供可执行细则。

## 1. 总体架构

```text
Vue Admin / Future Storefront
            │ HTTPS JSON
            ▼
      ThinkPHP 8 /api/v1
  Auth · Permission · Validation
            │
   ┌────────┼─────────┐
   ▼        ▼         ▼
 MySQL    Redis   Service Adapters
事实数据  缓存/锁/队列  支付/物流/存储/通知
```

后端 **MUST** 是模块化单体起步。未出现明确容量或团队边界前，不得拆微服务。支付、物流、存储和通知通过接口/适配器隔离，便于替换供应商。

管理后台与消费者独立站必须是两个前端应用：当前 Vue/Vite 项目负责运营后台；消费者商城建议使用 Nuxt 等支持 SSR/SEO 的 Vue 技术栈。两个前端共享 ThinkPHP 8 API 和同一业务事实库，禁止各自复制商品、库存、订单和用户主数据。

## 2. 固定技术选型

### 前端

- Vue 3、TypeScript、Vite、Vue Router。
- 所有新源码使用 TypeScript，禁止新增无类型的业务 JavaScript。
- API 状态与本地 UI 状态分离；正式业务数据不得写入 `localStorage`。
- 引入 Pinia、查询缓存库或 UI 框架前必须记录 ADR，并说明现有方案无法满足的原因。

### 后端

- ThinkPHP 8，PHP 版本必须满足 ThinkPHP 8 官方要求并在项目配置锁定。
- 分层建议：`controller` 只做协议转换，`validate` 做入参验证，`service` 编排业务，`domain/model` 表达领域与持久化，`repository` 仅在查询复杂度确有需要时引入。
- 控制器 **MUST NOT** 包含库存扣减、支付状态迁移、优惠计算等核心规则。

### 基础设施

- MySQL：订单、商品、SKU、库存流水、用户、成员、角色、优惠券、支付单等核心事实。
- Redis：购物车缓存、热点商品、库存锁、验证码、Token/会话状态、幂等键、异步队列。
- 图片/附件：开发环境可本地；生产使用阿里云 OSS、腾讯云 COS 或 MinIO。业务代码只依赖 `StorageServiceInterface`。
- 队列：基于 Redis。订单超时取消、库存回补、支付后续处理、短信/邮件通知必须异步化并支持重试与死信处理。

## 3. REST API 契约

### 路径与方法

- 基础路径：`/api/v1`。
- 集合使用复数名词：`/orders`、`/products`、`/members`。
- `GET` 查询，`POST` 创建，`PATCH` 局部更新，`PUT` 完整替换，`DELETE` 删除。
- 业务动作仅在无法自然表达为资源时使用：`POST /orders/{id}/cancel`、`POST /members/{id}/disable`。

### 统一响应

成功：

```json
{
  "code": "OK",
  "message": "success",
  "data": {},
  "request_id": "01J..."
}
```

分页：

```json
{
  "code": "OK",
  "message": "success",
  "data": {
    "items": [],
    "pagination": { "page": 1, "page_size": 20, "total": 86, "pages": 5 }
  },
  "request_id": "01J..."
}
```

失败：

```json
{
  "code": "VALIDATION_ERROR",
  "message": "请求参数不合法",
  "errors": { "email": ["邮箱格式不正确"] },
  "request_id": "01J..."
}
```

- HTTP 状态码 **MUST** 与语义一致；业务错误不得全部返回 200。
- `code` 是稳定机器码，前端不得依赖中文 `message` 判断逻辑。
- 每个请求 **MUST** 带 `request_id`，日志和响应使用同一 ID。
- 列表默认 `page=1&page_size=20`，最大页大小 100；排序字段必须白名单化。
- 时间统一以 ISO 8601 返回，存储使用 UTC，展示层按 `Asia/Shanghai` 转换。

### 错误码最小集合

- `VALIDATION_ERROR`、`UNAUTHENTICATED`、`TOKEN_EXPIRED`、`FORBIDDEN`
- `RESOURCE_NOT_FOUND`、`RESOURCE_CONFLICT`、`RATE_LIMITED`
- `INVENTORY_INSUFFICIENT`、`ORDER_STATE_INVALID`、`PAYMENT_FAILED`
- `INTERNAL_ERROR`、`UPSTREAM_UNAVAILABLE`

## 4. 认证、会话与权限

- `POST /api/v1/auth/login` 返回短期 Access Token 与可轮换 Refresh Token。
- `POST /api/v1/auth/refresh` 每次成功刷新后使旧 Refresh Token 失效。
- `POST /api/v1/auth/logout` 撤销当前会话；`DELETE /api/v1/member/sessions/{id}` 撤销指定设备。
- Refresh Token **MUST** 以哈希或不可逆标识存储，不得明文落库。
- 验证码、密码重置令牌和登录失败计数存 Redis，设置 TTL 和尝试次数限制。
- 权限采用 RBAC + 数据范围：`member → roles → permission_codes`，再叠加店铺、渠道、仓库或部门范围。
- 权限码使用 `domain.resource.action`，例如 `order.order.export`、`catalog.product.update`、`admin.member.invite`。
- 每个受保护路由必须经过认证与权限中间件；数据范围必须进入查询条件，不能查询后再由前端过滤。

## 5. 核心数据规则

### MySQL

- 主键策略需全局统一；对外 ID 不暴露可枚举自增序列时，使用 UUID/ULID 或独立业务号。
- 表名、字段名使用 `snake_case`；外键字段为 `{entity}_id`。
- 金额使用 `DECIMAL` 或最小货币单位整数，并保存币种。
- 订单号、支付单号、退款单号、库存流水号必须唯一索引。
- 状态值用代码常量/枚举，不在业务逻辑中散落中文字符串。
- 商品基础资料与渠道商品配置必须分离。独立站标题、描述、Slug、价格、媒体、SEO、发布状态和库存策略属于渠道配置，不得覆盖商品主数据或国内平台字段。
- 订单必须记录 `channel_type`、`channel_store_id` 和适用时的 `external_order_no`；独立站订单与平台订单共用订单主模型。
- 所有库存变化必须写库存流水；不得只更新 `stock_quantity` 而无审计记录。

### Redis Key

统一格式：`ebase:{env}:{domain}:{purpose}:{id}`。

示例：

- `ebase:prod:auth:captcha:{challenge_id}`
- `ebase:prod:auth:session:{session_id}`
- `ebase:prod:cart:user:{user_id}`
- `ebase:prod:inventory:lock:{sku_id}`
- `ebase:prod:product:hot:{product_id}`
- `ebase:prod:idempotency:payment_callback:{transaction_id}`

禁止永久保存临时数据；所有临时 Key 必须设置 TTL。锁的释放必须校验持有者 token，禁止直接 `DEL` 他人锁。

## 6. 订单、库存与异步任务

- 创建订单、锁定库存、支付、取消、发货、完成、售后必须有明确状态机。
- 状态迁移必须校验当前状态并记录操作者、时间、来源和备注。
- 下单先锁库存，支付成功后确认扣减；超时或失败通过可靠任务释放/回补。
- 队列 Job **MUST** 包含唯一业务键并保证重复消费安全。
- 重试采用退避策略并设置最大次数；最终失败进入失败队列/任务表并告警。
- 支付回调处理与通知、积分、消息发送解耦；回调线程只完成必要验签、订单更新和可靠事件落库。

首批队列任务：

- `order.cancel_timeout`
- `inventory.release_reservation`
- `payment.callback_process`
- `notification.sms_send`
- `notification.email_send`

## 7. 可替换服务层

定义并依赖接口，不得在控制器中直接调用微信、快递或 OSS SDK。

- `PaymentServiceInterface`：创建支付、查询、关闭、退款、验签回调。
- `LogisticsServiceInterface`：创建运单、取消、查询轨迹、订阅轨迹。
- `StorageServiceInterface`：上传、签名 URL、删除、元数据查询。
- `NotificationServiceInterface`：短信、邮件、站内信。

首版支付接微信支付，但领域模型不得出现只适用于微信的字段；渠道原始数据放受控扩展字段或支付渠道明细表。物流、优惠券、发票按业务节奏加入，但接口和数据迁移必须向后兼容。

## 8. 前端工程规范

建议演进目录：

```text
src/
  api/          # 统一 client、DTO 与端点
  components/   # 可复用展示组件
  composables/  # 可复用状态/交互
  data/         # 仅原型或静态配置
  router/       # 路由与守卫
  styles/       # token 与全局样式
  types/        # 跨模块类型
  views/        # 路由页面，按领域继续拆分
```

- API Client 统一处理 base URL、Token、刷新、request ID、错误转换和取消请求。
- 页面必须处理 `idle/loading/success/empty/error`；提交按钮处理 `submitting` 并防重复提交。
- 所有保存、提交、删除、导入、上传和批量操作必须给出统一结果 Toast；成功提示简短，错误提示必须包含原因和下一步。禁止每个页面自行发明一套通知样式。
- 文件上传必须复用统一组件，覆盖点击/拖拽、类型和大小校验、进度、成功、失败、重试和移除；生产环境上传状态必须来自真实请求，不得使用模拟进度。
- 表单前端校验用于体验，后端校验才是安全边界；两端规则需保持一致。
- 路由 `meta` 承载标题、布局和权限码；受保护页面必须有导航守卫。
- 顶部搜索、帮助、通知、头像和任何带可点击外观的工具必须有真实交互、键盘关闭、焦点状态和结果/空状态；禁止保留纯装饰的功能按钮。
- 一级导航活动态必须覆盖所属详情、新建、编辑和审批路由；根路径 `/` 单独使用精确匹配，禁止用无边界前缀判断。
- 组件不得直接依赖后端中文状态；使用类型化状态码和展示映射。
- 列表筛选条件应同步到 URL Query，支持刷新和分享；原型未实现部分在接 API 时补齐。

## 9. UI 规范

- 使用 `--bg`、`--surface`、`--text`、`--muted`、`--border`、`--primary` 等现有 token。
- 页面外层沿用 `.page-canvas`；后台内容最大宽度保持一致并居中。
- 间距优先使用 4px 基线的既有尺度；同级元素间距必须一致。
- 输入控件字体继承全局 `Noto Sans SC`；英文数字标题可使用 Manrope。
- 表单标签、帮助文本、错误文本、禁用态和焦点态缺一不可。
- 图表必须使用真实坐标系和可解释数据，禁止用随意 SVG 形状模拟异常曲线。
- 禁止内容区用额外固定 `margin-left` 补偿侧栏；侧栏偏移只能由 `AppShell` 统一负责。

## 10. 测试与交付门槛

前端每次提交至少：

```bash
npm run build
```

正式接入后应增加并强制：

- 单元测试：金额、优惠、状态机、权限判断和库存计算。
- API 集成测试：认证、越权、参数错误、分页、幂等与事务回滚。
- 端到端测试：登录、商品创建、下单/取消、成员邀请与权限变更。
- 安全测试：Token 轮换、暴力登录限制、上传校验、支付回调伪造和水平越权。

任何任务在以下条件满足前不得标记完成：构建通过；新增路径可访问；加载/错误/空状态完整；权限已覆盖；危险操作有确认；文档与接口契约已同步。

## 11. 禁止事项

- 禁止把模拟数据、`localStorage` 或前端权限判断当作生产实现。
- 禁止在组件中硬编码服务商密钥、API 地址或环境差异。
- 禁止用 Redis 作为订单、支付或库存最终事实来源。
- 禁止无幂等键执行支付、退款、库存回补和队列消费。
- 禁止捕获异常后静默成功；所有异常必须进入统一处理和可观测日志。
- 禁止未经评审改变现有路由语义、视觉 token、响应结构或权限码命名。
