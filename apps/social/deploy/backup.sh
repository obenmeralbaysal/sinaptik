#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

env_value() {
  local key="$1"
  local fallback="$2"
  local line
  line="$(grep -E "^${key}=" .env | tail -n 1 || true)"
  if [[ -z "$line" ]]; then
    printf '%s' "$fallback"
    return
  fi
  printf '%s' "${line#*=}"
}

PGUSER="$(env_value POSTGRES_USER sinsocial)"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DIR="$(env_value BACKUP_DIR "${BACKUP_DIR:-}")"
if [[ -z "$DIR" ]]; then
  DIR="$ROOT/storage/backups"
elif [[ "$DIR" != /* ]]; then
  DIR="$ROOT/$DIR"
fi
mkdir -p "$DIR"
FILE="$DIR/sinsocial-${STAMP}.sql.gz"

docker compose exec -T db pg_dump -U "$PGUSER" --dbname sinsocial | gzip > "$FILE"
echo "Yedek yazıldı: $FILE"

BUCKET="$(env_value BACKUP_S3_BUCKET "")"
if [[ -n "$BUCKET" ]]; then
  ENDPOINT="$(env_value AWS_ENDPOINT "")"
  ARGS=(s3 cp "$FILE" "s3://${BUCKET}/sinsocial/$(basename "$FILE")")
  if [[ -n "$ENDPOINT" ]]; then
    ARGS+=(--endpoint-url "$ENDPOINT")
  fi
  aws "${ARGS[@]}"
  echo "Yedek R2/S3 hedefine kopyalandı."
fi

find "$DIR" -name 'sinsocial-*.sql.gz' -mtime +30 -delete
