# EBASE 页面与功能清单

更新日期：2026-09-02  
当前阶段：高保真前端原型，尚未接入正式 ThinkPHP API。

## 1. 当前实现状态

- 前端：Vue 3 + TypeScript + Vite + Vue Router。
- 图标：Lucide Vue Next。
- 数据：当前页面使用内置模拟数据；部分 CRUD、流程表单、收藏和最近访问使用 `localStorage` 持久化。
- 认证：已有登录交互原型，但尚未接入 JWT、刷新令牌、验证码或后端会话。
- 后端、数据库、Redis、队列、对象存储、支付和物流服务尚未在本仓库实现。

因此，“页面存在”不等于“生产业务能力已经完成”。接后端时不得保留 `localStorage` 作为真实业务数据源。

## 2. 全局框架

- 后台框架：固定侧栏、顶部工具栏、全局搜索、店铺切换、通知和个人头像。
- 响应式导航：移动端抽屉侧栏。
- 认证框架：登录/找回密码使用独立布局，不显示后台导航。
- 通用视觉：Noto Sans SC + Manrope、Lucide 图标、统一颜色/圆角/边框/表单字体。

## 3. 已有页面

### 认证与成员

- `/login`：成员邮箱登录、密码显隐、保持登录、SSO 占位入口。
- `/forgot-password`：找回密码、发送成功状态。
- `/member/profile`：个人资料、账号安全、登录设备、通知偏好、退出登录。
- `/settings/members`：成员指标、状态标签、搜索、筛选、目录表格和分页外观。
- `/settings/members/invite`：邀请成员、部门/角色/数据范围、安全策略。
- `/settings/members/:id`：成员资料编辑、权限设置、重置密码和停用账号入口。

### 控制台与一级模块

- `/`：运营控制台，含经营指标、销售趋势、履约状态、待办、库存预警和最新订单。
- `/orders`：订单管理。
- `/logistics`：物流履约。
- `/products`：产品管理。
- `/inventory`：库存中心。
- `/users`：消费者/会员管理，注意不是内部成员。
- `/content`：内容中心。
- `/coupons`：优惠券管理。
- `/marketing`：营销活动。
- `/reports`：数据报表。

上述九个一级模块复用 `ModuleView.vue`，具备指标、页签、搜索、筛选、勾选、批量操作、表格、分页外观和侧边洞察。

### 四步业务流程

- `/orders/:id`：订单概览、商品与支付、履约记录、售后与备注。
- `/logistics/:id`：异常概览、收件信息、物流轨迹、处理记录。
- `/products/new`：基础信息、销售规格、媒体素材、渠道发布。
- `/inventory/restock`：补货清单、供应商、到货计划、审批记录。
- `/users/:id`：用户概览、消费行为、标签人群、触达记录。
- `/content/new`：内容编辑、关联商品、发布渠道、排期与审核。
- `/coupons/new`：基础规则、适用范围、领取限制、投放计划。
- `/marketing/new`：活动信息、优惠机制、渠道与人群、预算审批。
- `/reports/analysis`：核心趋势、渠道贡献、商品结构、用户结构。
- `/settings/roles/:id`：角色信息、功能权限、数据权限、成员与日志。

流程页包含步骤导航、编辑表单、摘要、时间线、附件入口和本地草稿保存。

### 功能地图与二级 CRUD

- `/features`：搜索、业务分组、收藏、最近访问和功能状态。
- `/features/refunds`：售后退款中心。
- `/features/warehouses`：仓库管理。
- `/features/categories`：类目与品牌。
- `/features/suppliers`：供应商管理。
- `/features/segments`：用户分群。
- `/features/assets`：素材库。
- `/features/coupon-delivery`：批量发券。
- `/features/approvals`：营销审批中心。
- `/features/report-builder`：自定义报表。
- `/features/audit-logs`：操作日志。

这些二级页复用 `SecondaryView.vue` 和 `secondaryConfigs.ts`，具备搜索、阶段卡片、查看、新增、编辑、删除确认、必填验证、状态选择和本地持久化。

### 系统设置

`/settings` 包含八组配置：企业信息、成员与角色、店铺与渠道、仓库配置、支付配置、消息通知、安全设置、操作日志。

## 4. 当前仅为原型的交互

以下能力只有 UI/本地行为，接后端时必须替换：

- 登录、退出、找回密码和 SSO。
- 所有列表查询、分页、筛选、批量操作和导出。
- 所有 CRUD、审批、保存草稿和附件上传。
- 成员邀请、权限变更、密码重置、双重验证和设备退出。
- 支付、物流轨迹、库存锁、订单状态、通知和操作日志。
- 图表、经营指标和预警数据。

## 5. 建议后续优先级

1. 建立 ThinkPHP 8 基础工程、统一响应/异常、JWT、权限中间件和 OpenAPI。
2. 接通成员登录、刷新令牌、个人会话、角色权限与操作日志。
3. 完成商品、SKU、库存、订单的核心数据模型与接口。
4. 建立 Redis 库存锁和队列，完成订单超时取消与库存回补。
5. 接入微信支付服务层和可靠回调流程。
6. 按业务节奏接入物流、优惠券、发票、对象存储与消息通知。

## 6. 关键源码

- 路由：`src/router/index.ts`
- 应用布局：`src/App.vue`、`src/components/layout/AppShell.vue`
- 通用一级模块：`src/views/ModuleView.vue`
- 通用四步流程：`src/views/WorkflowView.vue`
- 二级 CRUD：`src/views/SecondaryView.vue`
- 二级功能配置：`src/data/secondaryConfigs.ts`
- 系统设置：`src/views/SettingsView.vue`
- 全局样式：`src/styles/main.css`
- 成员与认证样式：`src/styles/members.css`
