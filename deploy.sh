#!/usr/bin/env bash
set -euo pipefail

cd /opt/apps/kara-plus

git fetch --all
git reset --hard origin/master

docker compose --env-file .env.docker pull || true
docker compose --env-file .env.docker up -d --build

APP_CID=$(docker compose ps -q app)

docker exec -i "$APP_CID" bash -lc "composer install --no-interaction --prefer-dist --no-dev"
docker exec -i "$APP_CID" bash -lc "php artisan migrate --force"
docker exec -i "$APP_CID" bash -lc "php artisan config:cache && php artisan route:cache && php artisan view:cache"
docker exec -i "$APP_CID" bash -lc "php artisan queue:restart || true"
