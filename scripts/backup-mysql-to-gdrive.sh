#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/apps/kara-plus"
ENV_FILE="$APP_DIR/.env"
BACKUP_DIR="$APP_DIR/backups/mysql"
ENV_OVERRIDE_FILE="/etc/kara-plus/backup.env"

umask 077

if [[ -f "$ENV_FILE" ]]; then
  # Load DB settings from app env
  set -a
  . "$ENV_FILE"
  set +a
fi

if [[ -f "$ENV_OVERRIDE_FILE" ]]; then
  # Load backup/rclone settings
  set -a
  . "$ENV_OVERRIDE_FILE"
  set +a
fi

: "${MYSQL_ROOT_PASSWORD:?Missing MYSQL_ROOT_PASSWORD in .env}"
: "${MYSQL_DATABASE:?Missing MYSQL_DATABASE in .env}"

RCLONE_REMOTE="${RCLONE_REMOTE:-}"
RCLONE_FOLDER="${RCLONE_FOLDER:-kara-plus/mysql}"
RETENTION_DAYS="${RETENTION_DAYS:-20}"
LOCAL_RETENTION_DAYS="${LOCAL_RETENTION_DAYS:-7}"

if [[ -z "$RCLONE_REMOTE" ]]; then
  echo "RCLONE_REMOTE is not set. Set it in /etc/kara-plus/backup.env" >&2
  exit 2
fi

if ! command -v rclone >/dev/null 2>&1; then
  echo "rclone not found in PATH" >&2
  exit 3
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "docker not found in PATH" >&2
  exit 3
fi

mkdir -p "$BACKUP_DIR"

cd "$APP_DIR"

ts="$(date -u +"%Y%m%dT%H%M%SZ")"
filename="${MYSQL_DATABASE}-${ts}.sql.gz"
filepath="$BACKUP_DIR/$filename"
tmpfile="${filepath}.tmp"

if [[ -e "$filepath" ]]; then
  echo "Backup file already exists: $filepath" >&2
  exit 4
fi

# Stream mysqldump from the mysql container and compress on the host
if ! docker compose exec -T mysql \
  mysqldump --single-transaction --quick --routines --triggers --databases "$MYSQL_DATABASE" \
  -uroot -p"$MYSQL_ROOT_PASSWORD" | gzip -9 > "$tmpfile"; then
  rm -f "$tmpfile"
  echo "mysqldump failed" >&2
  exit 5
fi

mv "$tmpfile" "$filepath"

# Upload to Google Drive
rclone copy "$filepath" "${RCLONE_REMOTE}:${RCLONE_FOLDER}/" \
  --checksum \
  --transfers 2 \
  --checkers 4 \
  --stats 30s

# Enforce retention on remote
rclone delete "${RCLONE_REMOTE}:${RCLONE_FOLDER}/" --min-age "${RETENTION_DAYS}d" --rmdirs

# Local retention to avoid disk growth
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime "+${LOCAL_RETENTION_DAYS}" -delete
