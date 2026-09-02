#!/bin/sh
set -eu

if [ "$#" -ne 2 ] || [ "$2" != "--yes" ]; then
    echo "Uso: scripts/restore.sh /caminho/backup.dump --yes" >&2
    echo "A restauração substitui os objetos existentes no banco configurado." >&2
    exit 1
fi

backup_file=$1
if [ ! -f "$backup_file" ]; then
    echo "Backup não encontrado: $backup_file" >&2
    exit 1
fi

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$project_dir"

docker compose exec -T db sh -c 'pg_restore --clean --if-exists --no-owner --no-privileges -U "$POSTGRES_USER" -d "$POSTGRES_DB"' < "$backup_file"
docker compose exec -T app php artisan migrate --force

echo "Restauração concluída."
