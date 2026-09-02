#!/usr/bin/env bash
set -euo pipefail
BASE_URL="${BASE_URL:-http://127.0.0.1:8797}"
API="$BASE_URL/api/v1"
LOGIN=$(curl -fsS -X POST "$API/auth/login" -H 'Content-Type: application/json' -d '{"email":"admin@ebase.local","password":"ChangeMe123!"}')
TOKEN=$(printf '%s' "$LOGIN" | python3 -c 'import json,sys; print(json.load(sys.stdin)["data"]["access_token"])')
HEADER="Authorization: Bearer $TOKEN"
curl -fsS "$API/health" >/dev/null
curl -fsS "$API/operations/products?page=1&page_size=2" -H "$HEADER" >/dev/null
curl -fsS "$API/operations/dashboard" -H "$HEADER" >/dev/null
curl -fsS "$API/storefront-public/cn-main/manifest" >/dev/null
printf 'API smoke tests passed\n'
