#!/usr/bin/env bash
set -euo pipefail

cd /opt/apps/kara-plus

echo "[1/6] Pull latest master"
git fetch --all
git reset --hard origin/master

echo "[2/6] Build & up containers"
docker compose --env-file .env.docker up -d --build

APP_CID="$(docker compose ps -q app)"

echo "[3/6] Install PHP deps"
docker exec -i "$APP_CID" bash -lc "composer install --no-interaction --prefer-dist --no-dev"

echo "[4/6] Migrate DB"
docker exec -i "$APP_CID" bash -lc "php artisan migrate --force"

echo "[5/6] Cache optimize"
docker exec -i "$APP_CID" bash -lc "php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo "[6/6] Restart queue workers (safe)"
docker exec -i "$APP_CID" bash -lc "php artisan queue:restart || true"

echo "Deploy done."
