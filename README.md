# ebase-admin

EBASE 电商运营后台（Vue 3 + TypeScript + Vite 前端原型）。

> 当前为高保真前端原型，模拟数据与 `localStorage` 不代表正式后端能力。后端接入遵循 `AGENTS.md` 与 `docs/` 规范。

## 技术栈

- 前端：Vue 3、TypeScript、Vite、Vue Router
- 图标：lucide-vue-next
- 后端（规划）：ThinkPHP 8，REST API `/api/v1`，JWT，MySQL + Redis

## 启动

```bash
npm install
npm run dev
```

生产构建：

```bash
npm run build
```

## 开发前必读

1. [`AGENTS.md`](./AGENTS.md)：对开发者和 AI Agent 生效的强约束。
2. [`docs/PROJECT-INVENTORY.md`](./docs/PROJECT-INVENTORY.md)：现有页面、功能与原型边界。
3. [`docs/DEVELOPMENT-STANDARDS.md`](./docs/DEVELOPMENT-STANDARDS.md)：ThinkPHP 8、REST API、JWT、MySQL、Redis、队列、服务层和前端规范。
