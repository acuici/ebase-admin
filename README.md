# ebase-admin

EBASE 电商运营后台与 ThinkPHP 8 API 后端。

当前已包含：

- Vue 3 + TypeScript + Vite + Vue Router 管理后台
- JWT 成员认证、Refresh Token 轮换、设备会话和安全审计
- RBAC 角色权限矩阵与后端权限中间件
- 商品、SKU、库存流水、订单状态机和履约管理
- 独立站站点、域名、渠道商品、主题、导航、页面和 SEO 内容
- 消费者、地址、标签、用户画像和触达记录
- 优惠券、营销活动、审批和内容审核
- 支付宝、微信支付真实 SDK 适配边界；抖音支付、京东支付适配器配置边界
- 物流订阅、轨迹事件、异常检测和通知中心
- 安全素材上传、素材关联和 S3/OSS/COS/MinIO 存储适配器
- 可靠任务队列：订单超时取消、库存回补、物流轨迹查询
- 系统设置、动态浏览器标题和操作日志

> 后端业务数据使用 MySQL，Redis 用于 Token、缓存、锁、幂等和队列。生产支付、物流、短信、邮件、对象存储凭据只能放在 `.env` 或外部密钥系统，不能提交到 Git。

## 目录

```text
src/                 Vue 管理后台
backend/             ThinkPHP 8 API 后端
docs/                项目清单与开发规范
```

## 本地环境

要求：

- Node.js 22+
- PHP 8.2+
- Composer 2+
- MySQL 8+
- Redis 7+

当前本机 Docker 开发服务：

- MySQL：`127.0.0.1:3306`
- Redis：`127.0.0.1:6379`
- 数据库：`ebase`
- 数据库用户：`ebase`

## 启动前端

```bash
npm install
npm run dev
```

Vite 端口被占用时会自动切换，例如：

```text
http://127.0.0.1:5174
```

后端地址可通过 `VITE_API_BASE_URL` 修改，默认：

```text
http://127.0.0.1:8797/api/v1
```

## 启动后端

```bash
cd backend
composer install
cp .example.env .env
# 按本机环境修改 .env
php think run -H 127.0.0.1 -p 8797
```

后端默认地址：

```text
http://127.0.0.1:8797
```

健康检查：

```bash
curl http://127.0.0.1:8797/api/v1/health
```

## 初始化数据库

按顺序执行 Schema：

```bash
cd backend
for f in database/schema/*.sql; do
  docker exec -i mall-platform-mysql-1 mysql \
    --default-character-set=utf8mb4 \
    -uebase -pebase_dev_pass ebase < "$f"
done
```

加载可重复执行的开发演示数据：

```bash
docker exec -i mall-platform-mysql-1 mysql \
  --default-character-set=utf8mb4 \
  -uebase -pebase_dev_pass ebase < database/seed/dev.sql
```

本地管理员：

```text
邮箱：admin@ebase.local
密码：ChangeMe123!
```

仅限本地开发，任何共享或生产环境必须修改密码。

## 主要 API

认证：

```text
POST /api/v1/auth/login
POST /api/v1/auth/refresh
POST /api/v1/auth/logout
POST /api/v1/auth/password/reset
```

成员与权限：

```text
GET    /api/v1/member/profile
PATCH  /api/v1/member/profile
GET    /api/v1/member/sessions
DELETE /api/v1/member/sessions/:id
DELETE /api/v1/member/sessions/others
GET    /api/v1/member/auth-logs
GET|POST|PUT|DELETE /api/v1/admin/members...
GET|POST|PUT|DELETE /api/v1/admin/roles...
```

商品、订单和库存：

```text
GET|POST|PUT|DELETE /api/v1/products...
GET|POST|PUT /api/v1/products/:productId/skus...
GET|POST /api/v1/orders...
POST /api/v1/orders/:id/cancel
POST /api/v1/orders/:id/transition
POST /api/v1/products/:productId/skus/:id/stock-adjustments
```

消费者、营销和内容：

```text
GET|POST|PUT /api/v1/customers...
GET|POST /api/v1/marketing/coupons...
GET /api/v1/marketing/campaigns
GET /api/v1/marketing/approvals
GET /api/v1/content/reviews
POST /api/v1/content/:contentId/reviews
POST /api/v1/content/reviews/:id
```

独立站：

```text
GET|POST|PUT /api/v1/storefront/sites...
GET|PUT /api/v1/storefront/sites/:siteId/products...
GET|PUT|POST|DELETE /api/v1/storefront/sites/:siteId/content...
```

补货、质量、物流和通知：

```text
GET|POST /api/v1/restock...
GET /api/v1/products/:productId/quality-report
GET|POST|PUT /api/v1/logistics/exceptions...
GET|POST /api/v1/notifications...
```

系统设置：

```text
GET /api/v1/system-settings/:group
PUT /api/v1/system-settings/:group
GET /api/v1/operation-logs
```

## 任务队列

可靠任务事实存储在 MySQL `job_outbox`，处理命令：

```bash
cd backend
php think jobs:process
```

当前任务类型：

- `order.cancel_timeout`
- `logistics.track`

生产环境应使用 Supervisor、launchd 或容器编排器持续运行 worker，并监控 `failed` / `dead` 任务。

## 验证命令

```bash
# 前端
npm run build

# 后端语法
find backend/app backend/route backend/config -name '*.php' -print0 \
  | xargs -0 -n1 php -l

# Composer 依赖安全审计
cd backend && composer audit

# 查看路由
php think route:list
```

## 开发规范

开始工作前必须阅读：

1. [`AGENTS.md`](./AGENTS.md)
2. [`docs/PROJECT-INVENTORY.md`](./docs/PROJECT-INVENTORY.md)
3. [`docs/DEVELOPMENT-STANDARDS.md`](./docs/DEVELOPMENT-STANDARDS.md)
4. [`backend/README.md`](./backend/README.md)

前端管理后台与消费者独立站前台是两个应用，共享同一套 ThinkPHP API、MySQL 业务事实和库存订单体系。不要把消费者数据混入后台成员模型，也不要用 `localStorage` 作为正式业务数据源。
