#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
ENV_FILE="$ROOT_DIR/.env"
ENV_EXAMPLE="$ROOT_DIR/.env.example"
HOST="${APP_HOST:-localhost}"
PORT="${APP_PORT:-8000}"

read_env_value() {
    key="$1"
    file="$2"

    if [ ! -f "$file" ]; then
        return 1
    fi

    value=$(grep "^${key}=" "$file" | tail -n 1 | cut -d= -f2- | tr -d '\r' || true)

    if [ -z "$value" ]; then
        return 1
    fi

    printf '%s' "$value"
}

ensure_env_file() {
    if [ -f "$ENV_FILE" ]; then
        return
    fi

    cp "$ENV_EXAMPLE" "$ENV_FILE"
    echo ".env was not found, so it was created from .env.example"
}

ensure_sqlite_database() {
    db_driver=$(read_env_value "DB_DRIVER" "$ENV_FILE" || printf 'sqlite')

    if [ "$db_driver" != "sqlite" ]; then
        return
    fi

    sqlite_path=$(read_env_value "DB_SQLITE_PATH" "$ENV_FILE" || printf 'database/local.sqlite')

    case "$sqlite_path" in
        /*) sqlite_file="$sqlite_path" ;;
        *) sqlite_file="$ROOT_DIR/$sqlite_path" ;;
    esac

    mkdir -p "$(dirname "$sqlite_file")"

    if php -r '
        $path = $argv[1];
        try {
            $pdo = new PDO("sqlite:" . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = '\''table'\'' AND name = '\''users'\''");
            exit($stmt->fetchColumn() ? 0 : 1);
        } catch (Throwable $e) {
            exit(1);
        }
    ' "$sqlite_file"
    then
        return
    fi

    php -r '
        $path = $argv[1];
        $root = $argv[2];
        $pdo = new PDO("sqlite:" . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(file_get_contents($root . "/database/schema.sqlite.sql"));
        $pdo->exec(file_get_contents($root . "/database/seed.sqlite.sql"));
    ' "$sqlite_file" "$ROOT_DIR"

    echo "SQLite database was initialized at $sqlite_path"
}

ensure_env_file
ensure_sqlite_database

cd "$ROOT_DIR"

echo "Starting PHP server at http://$HOST:$PORT"
exec php -S "$HOST:$PORT" -t public router.php
