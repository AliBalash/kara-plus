#!/usr/bin/env bash
set -euo pipefail

ROOT="/opt/apps/kara-plus"
SSH_KEY="$HOME/.ssh/kara_plus_deploy"

echo "[0/7] Ensure GitHub SSH host key"
mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"
if ! ssh-keygen -F github.com >/dev/null 2>&1; then
  ssh-keyscan -H github.com >> "$HOME/.ssh/known_hosts"
  chmod 600 "$HOME/.ssh/known_hosts"
fi
if [ -f "$SSH_KEY" ]; then
  GIT_SSH_COMMAND="ssh -i $SSH_KEY -o IdentitiesOnly=yes"
else
  GIT_SSH_COMMAND=""
fi

echo "[1/7] Fetch + reset to origin/deployment"
cd "$ROOT"
git config --global --add safe.directory "$ROOT"
if [ -n "$GIT_SSH_COMMAND" ]; then
  GIT_SSH_COMMAND="$GIT_SSH_COMMAND" git fetch origin deployment
else
  git fetch origin deployment
fi
git reset --hard origin/deployment

echo "[2/7] Build & up (docker compose)"
docker compose --env-file .env.docker -f docker-compose.yml up -d --build

APP_CID="$(docker compose -f docker-compose.yml ps -q app)"

echo "[3/7] Composer install (no-dev)"
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
