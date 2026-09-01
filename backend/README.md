# EBASE Backend

ThinkPHP 8 REST API backend for EBASE Commerce Admin.

## Local setup

Requirements: PHP >= 8.0, Composer, MySQL 8+, Redis 7+.

```bash
composer install
cp .example.env .env
# edit .env with local credentials
php think run -H 127.0.0.1 -p 8787
```

The current local development database is `ebase` on `127.0.0.1:3306`; Redis is `127.0.0.1:6379`.

Apply schema:

```bash
for f in database/schema/*.sql; do docker exec -i mall-platform-mysql-1 mysql -uebase -pebase_dev_pass ebase < "$f"; done
```

The development seed admin is documented only for local use:

- email: `admin@ebase.local`
- password: `ChangeMe123!`

Change it before any shared or production deployment.

## API

- `GET /api/v1/health`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout` (Bearer)
- `GET /api/v1/member/profile` (Bearer)
- `GET /api/v1/member/sessions` (Bearer)
- `GET /api/v1/products` (Bearer, pagination/filter)
- `POST /api/v1/products` (Bearer)
- `GET /api/v1/products/:id` (Bearer)
- `PUT /api/v1/products/:id` (Bearer)
- `DELETE /api/v1/products/:id` (Bearer, archives product)

All responses include `code`, `message`, `data/errors`, and `request_id`. Refresh tokens are stored as SHA-256 hashes in Redis and are rotated on refresh.
