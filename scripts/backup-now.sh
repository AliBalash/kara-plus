#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/apps/kara-plus"
BACKUP_SCRIPT="$APP_DIR/scripts/backup-mysql-local.sh"

if [[ ! -x "$BACKUP_SCRIPT" ]]; then
  chmod +x "$BACKUP_SCRIPT"
fi

echo "Starting KaraPlus database backup..."

backup_output="$("$BACKUP_SCRIPT")"
echo "$backup_output"

backup_path="$(printf '%s\n' "$backup_output" | sed -n 's/^backup completed: //p' | tail -n 1)"

if [[ -z "$backup_path" ]]; then
  echo "Backup finished but output path was not detected." >&2
  exit 7
fi

if [[ ! -f "$backup_path" ]]; then
  echo "Backup file not found: $backup_path" >&2
  exit 8
fi

backup_size="$(du -h "$backup_path" | awk '{print $1}')"

echo
echo "Backup is ready."
echo "File: $backup_path"
echo "Size: $backup_size"
