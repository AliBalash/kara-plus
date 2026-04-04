#!/usr/bin/env bash
set -euo pipefail

ROOT="/opt/apps/kara-plus"
SSH_KEY=""
for candidate in "$HOME/.ssh/kara_plus_deploy" "/home/actions/.ssh/kara_plus_deploy" "/home/deploy/.ssh/kara_plus_deploy" "/home/runner/.ssh/kara_plus_deploy" "/root/.ssh/kara_plus_deploy"; do
  if [ -f "$candidate" ]; then
    SSH_KEY="$candidate"
    break
  fi
done

echo "[0/7] Ensure GitHub SSH host key"
mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"
if ! ssh-keygen -F github.com >/dev/null 2>&1; then
  ssh-keyscan -H github.com >> "$HOME/.ssh/known_hosts"
  chmod 600 "$HOME/.ssh/known_hosts"
fi
if [ -n "$SSH_KEY" ]; then
  GIT_SSH_COMMAND="ssh -i $SSH_KEY -o IdentitiesOnly=yes"
else
  GIT_SSH_COMMAND=""
fi

echo "[1/7] Fetch + reset to origin/master"
cd "$ROOT"
git config --global --add safe.directory "$ROOT"
if [ -n "$GIT_SSH_COMMAND" ]; then
  GIT_SSH_COMMAND="$GIT_SSH_COMMAND" git fetch origin master
else
  git fetch origin master
fi
git reset --hard origin/master

echo "[1.5/7] Resolve Docker permissions"
DOCKER_CMD="docker"
if ! $DOCKER_CMD info >/dev/null 2>&1; then
  if command -v sudo >/dev/null 2>&1; then
    if sudo -n docker info >/dev/null 2>&1; then
      DOCKER_CMD="sudo -n docker"
    else
      echo "Docker socket permission denied and passwordless sudo not available."
      echo "Add the runner user to the docker group or allow sudo for docker."
      exit 1
    fi
  else
    echo "Docker socket permission denied and sudo is not available."
    echo "Add the runner user to the docker group."
    exit 1
  fi
fi

echo "[2/7] Build & up (docker compose)"
$DOCKER_CMD compose --env-file .env.docker -f docker-compose.yml up -d --build

APP_CID="$($DOCKER_CMD compose -f docker-compose.yml ps -q app)"
if [ -z "$APP_CID" ]; then
  echo "Failed to resolve app container id."
  exit 1
fi

echo "[3/7] Composer install (no-dev)"
$DOCKER_CMD exec -i "$APP_CID" bash -lc "git config --global --add safe.directory /var/www || true"
$DOCKER_CMD exec -i "$APP_CID" bash -lc "composer install --no-interaction --prefer-dist --no-dev"

echo "[4/7] Storage link"
$DOCKER_CMD exec -i "$APP_CID" bash -lc "php artisan storage:link --relative --force"

echo "[5/7] Migrate"
$DOCKER_CMD exec -i "$APP_CID" bash -lc "php artisan migrate --force"

echo "[6/7] Normalize runtime permissions"
$DOCKER_CMD exec -u 0:0 -i "$APP_CID" bash -lc "mkdir -p /var/www/storage/framework/cache/data /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/testing /var/www/storage/framework/livewire-tmp /var/www/storage/app/private/livewire-tmp /var/www/storage/app/public/livewire-tmp /var/www/bootstrap/cache /var/www/public/assets/car-pics && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public/assets/car-pics && find /var/www/storage /var/www/bootstrap/cache /var/www/public/assets/car-pics -type d -exec chmod 775 {} + && find /var/www/storage /var/www/bootstrap/cache /var/www/public/assets/car-pics -type f -exec chmod 664 {} + && find /var/www/storage/framework/views -maxdepth 1 -type f ! -name '.gitignore' -delete && rm -f /var/www/bootstrap/cache/*.php"

echo "[6.5/7] Rebuild Laravel caches as www-data"
$DOCKER_CMD exec -u www-data:www-data -i "$APP_CID" bash -lc "php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo "[7/7] Restart queue workers"
$DOCKER_CMD exec -u www-data:www-data -i "$APP_CID" bash -lc "php artisan queue:restart || true"

echo "Deploy done and successfuly"
