#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/apps/kara-plus"
ENV_FILE="$APP_DIR/.env"
BACKUP_DIR="$APP_DIR/backups/mysql"
RETENTION_DAYS="${RETENTION_DAYS:-10}"
LOCK_FILE="/tmp/kara-plus-mysql-backup.lock"

umask 077

if [[ -f "$ENV_FILE" ]]; then
  set -a
  . "$ENV_FILE"
  set +a
fi

: "${MYSQL_ROOT_PASSWORD:?Missing MYSQL_ROOT_PASSWORD in .env}"
: "${MYSQL_DATABASE:?Missing MYSQL_DATABASE in .env}"

if ! command -v docker >/dev/null 2>&1; then
  echo "docker not found in PATH" >&2
  exit 3
fi

mkdir -p "$BACKUP_DIR"

if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  if ! flock -n 9; then
    echo "backup is already running; skipping"
    exit 0
  fi
fi

cd "$APP_DIR"

ts="$(date -u +"%Y%m%dT%H%M%SZ")"
filename="${MYSQL_DATABASE}-${ts}.sql.gz"
filepath="$BACKUP_DIR/$filename"
tmpfile="${filepath}.tmp"

if [[ -e "$filepath" ]]; then
  echo "backup file already exists: $filepath" >&2
  exit 4
fi

if ! docker compose exec -T mysql \
  mysqldump --single-transaction --quick --routines --triggers --databases "$MYSQL_DATABASE" \
  -uroot -p"$MYSQL_ROOT_PASSWORD" | gzip -9 > "$tmpfile"; then
  rm -f "$tmpfile"
  echo "mysqldump failed" >&2
  exit 5
fi

if ! gzip -t "$tmpfile"; then
  rm -f "$tmpfile"
  echo "gzip integrity check failed" >&2
  exit 6
fi

mv "$tmpfile" "$filepath"

# Keep only the last 10 days locally
find "$BACKUP_DIR" -type f -name "${MYSQL_DATABASE}-*.sql.gz" -mtime "+${RETENTION_DAYS}" -delete

echo "backup completed: $filepath"
