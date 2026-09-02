#!/bin/sh
set -eu
umask 077

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$project_dir"

if ! command -v openssl >/dev/null 2>&1; then
    echo "Instale o openssl para gerar APP_KEY e DB_PASSWORD." >&2
    exit 1
fi

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Criado .env a partir de .env.example."
fi
chmod 600 .env

env_value() {
    awk -v name="$1" '
        index($0, name "=") == 1 {
            print substr($0, length(name) + 2)
            exit
        }
    ' .env
}

env_set() {
    name=$1
    value=$2
    tmp=$(mktemp)
    awk -v name="$name" -v value="$value" '
        BEGIN { found = 0 }
        index($0, name "=") == 1 {
            print name "=" value
            found = 1
            next
        }
        { print }
        END {
            if (!found) {
                print name "=" value
            }
        }
    ' .env > "$tmp"
    mv "$tmp" .env
}

app_key=$(env_value APP_KEY)
if [ -z "$app_key" ]; then
    env_set APP_KEY "base64:$(openssl rand -base64 32 | tr -d '\n')"
    echo "APP_KEY gerada."
fi

db_password=$(env_value DB_PASSWORD)
if [ -z "$db_password" ] || [ "$db_password" = "troque-esta-senha" ]; then
    generated=$(openssl rand -hex 24)
    env_set DB_PASSWORD "$generated"
    echo "DB_PASSWORD gerada e salva no .env."
fi

echo "Ambiente pronto. O arquivo .env só interpola o Compose; o container lê as mesmas variáveis do processo."
echo "Ajuste APP_URL e APP_PORT se não for http://localhost:8080."
