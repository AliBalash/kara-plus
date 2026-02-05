#!/usr/bin/env bash
set -euo pipefail

<<<<<<< HEAD
cd /opt/apps/kara-plus

echo "[1/6] Fetch + reset to origin/master"
git config --global --add safe.directory /opt/apps/kara-plus
git fetch origin
git reset --hard origin/master

echo "[2/6] Build & up (docker compose)"
docker compose --env-file .env.docker up -d --build

APP_CID="$(docker compose ps -q app)"

echo "[3/6] Composer install (no-dev)"
docker exec -i "$APP_CID" bash -lc "composer install --no-interaction --prefer-dist --no-dev"

echo "[4/7] Storage link"
docker exec -i "$APP_CID" bash -lc "php artisan storage:link --relative --force"

echo "[5/7] Migrate"
docker exec -i "$APP_CID" bash -lc "php artisan migrate --force"

echo "[6/7] Cache optimize"
docker exec -i "$APP_CID" bash -lc "php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo "[7/7] Restart queue workers"
docker exec -i "$APP_CID" bash -lc "php artisan queue:restart || true"

echo "Deploy done."
=======
ROOT="/opt/apps/kara-plus"

echo "[1/6] Fetch + reset to origin/deployment"
cd "$ROOT"
git fetch origin deployment
git reset --hard origin/deployment

echo "[2/6] Build & up (docker compose)"
docker compose --env-file .env.docker up -d --build
>>>>>>> 3fa5eb0 (Add docker compose deploy setup)
