#!/bin/sh
set -eu

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
backup_dir=${1:-"$project_dir/backups"}

case "$backup_dir" in
    /|"$project_dir")
        echo "Diretório de backup inseguro: $backup_dir" >&2
        exit 1
        ;;
esac

mkdir -p "$backup_dir"
backup_file="$backup_dir/financeiro-$(date -u +%Y%m%dT%H%M%SZ).dump"

cd "$project_dir"
docker compose exec -T db sh -c 'pg_dump -Fc --no-owner --no-privileges -U "$POSTGRES_USER" "$POSTGRES_DB"' > "$backup_file"

echo "$backup_file"
