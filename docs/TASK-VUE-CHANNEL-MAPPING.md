# Vue Agent 任务单：多渠道商品与 SKU 映射管理

状态：等待 PHP API 契约确认  
依赖契约：`docs/MULTI-CHANNEL-PRODUCT-CONTRACT.md`  
后端依赖：Hermes/PHP Agent 提供并验证 OpenAPI

## 任务边界

负责 Vue 3 + TypeScript 管理页面、路由、统一 API Client、交互状态、权限可见性和前端构建。不得修改 MySQL Schema、ThinkPHP 领域规则或自行改变 API 字段。

## 开始前

1. 完整阅读根目录 `AGENTS.md`、`docs/DEVELOPMENT-STANDARDS.md`、`docs/PROJECT-INVENTORY.md` 和共享契约。
2. 等待 PHP Agent 提交已确认的 OpenAPI；OpenAPI 未确认前只允许梳理信息架构和复用组件，不接模拟生产接口。
3. 检查工作区状态，避免覆盖 PHP Agent 或用户改动。
4. 优先复用 `ModuleView.vue`、`SecondaryView.vue`、`TableState.vue`、Toast、现有表单和分页模式；新抽象必须至少有两个真实使用点。

## 页面与路由

建议在现有渠道/设置体系中增加：

- `/settings/channel-stores`：渠道店铺列表和状态管理。
- `/products/channel-listings`：平台商品列表。
- `/products/channel-listings/:id`：平台商品及 SKU 映射详情。
- `/orders/mapping-exceptions`：渠道订单商品映射异常中心。

最终路由位置必须与现有信息架构一致；不得因为方便另建视觉体系。

## API Client 与类型

在 `src/api/` 中建立集中模块和 DTO：

- `channelStores`
- `channelProducts`
- `channelProductSkus`
- `channelOrderItemExceptions`

要求：

- 所有请求通过统一 `src/api/client.ts`。
- 类型和枚举以已确认 OpenAPI 为准。
- 不在组件中直接调用 `fetch` 或 `axios`。
- 分页、筛选、错误结构遵循现有统一契约。
- 不使用 `localStorage` 保存正式店铺、映射和异常业务数据。

## 交互要求

### 渠道店铺

- 显示渠道、店铺名称、外部店铺 ID、授权状态、运行状态、最近同步和失败摘要。
- 支持关键词、渠道、状态筛选和服务端分页。
- 新增/编辑表单包含必填、帮助、错误、禁用和提交状态。
- 停用、重新同步等操作具有明确确认与成功/失败反馈。
- 不展示或允许编辑明文 Token、Secret。

### 平台商品与 SKU 映射

- 清楚区分内部商品/SKU、平台商品/SKU 和商家 SKU 编码。
- 映射详情使用逐行对应关系，显示内部 SKU 规格、平台 SKU ID、商家 SKU 编码、平台规格、渠道价格、库存策略和同步状态。
- 不用“编码相同”自动选中内部 SKU；可提供搜索辅助，但最终必须显式确认。
- 冲突、跨商品映射和已被占用的 SKU 显示后端返回的稳定错误。
- 删除已有历史引用的映射时遵循后端归档/拒绝规则，不伪装成功。

### 映射异常

- 列表显示订单、渠道、店铺、平台商品/SKU、异常原因和状态。
- 支持按原因、渠道、店铺、状态筛选，并同步 URL Query。
- 解决动作允许搜索和选择内部 SKU，提交前显示对应关系摘要。
- 忽略属于危险操作，必须二次确认并说明影响。
- 成功解决后刷新订单与异常状态，不在前端自行模拟库存结果。

## 页面状态和响应式

- 每个列表和详情必须覆盖加载、空、筛选无结果、错误和重试。
- 所有写操作提供提交态、防重复提交及统一 Toast。
- 320px、760px、1100px、1440px 下页面主体不得横向漂移；表格只在自己的容器内滚动。
- 使用 Lucide 图标，不使用 emoji、字符图标或来源不明 SVG。
- 复用现有 CSS 变量、圆角、阴影、字体和间距体系。
- 权限码控制按钮与入口可见性，但后端仍是最终授权边界。

## 前端验收用例

- 无店铺、无平台商品和无异常时有正确空状态。
- API 失败时显示原因和重试入口。
- 筛选、分页刷新后可从 URL 恢复。
- 内部 SKU 和平台商家 SKU 编码不同时仍能建立映射。
- 映射冲突展示可理解的后端错误，不吞掉 `request_id`。
- 重复点击提交只产生一次请求。
- 停用、删除/归档和忽略异常均有二次确认。
- 无权限用户看不到入口；直接访问时正确进入 403 或显示后端拒绝。
- 不在页面、日志或浏览器存储中暴露授权凭据。

## 完成定义

- 已确认 OpenAPI 与 TypeScript 类型一致。
- 页面接入真实 API，没有生产业务模拟数据或 `localStorage`。
- 路由、权限、加载/空/错误状态齐全。
- `npm run build` 通过。
- 更新 `docs/PROJECT-INVENTORY.md`。
- 给出变更文件、构建证据、尚待后端或真实平台资质验证的限制。

