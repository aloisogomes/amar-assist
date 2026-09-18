#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

composer install --no-interaction --prefer-dist

if [ "${SKIP_DB_WAIT:-false}" != "true" ]; then
    until php -r '
        $host = getenv("DB_HOST") ?: "mysql";
        $port = getenv("DB_PORT") ?: "3306";
        $database = getenv("DB_DATABASE") ?: "amar_assist";
        $username = getenv("DB_USERNAME") ?: "amar";
        $password = getenv("DB_PASSWORD") ?: "secret";

        try {
            new PDO(
                sprintf("mysql:host=%s;port=%s;dbname=%s", $host, $port, $database),
                $username,
                $password
            );
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);
            exit(1);
        }
    '; do
        echo "Aguardando o MySQL..."
        sleep 2
    done
fi

if ! grep -qE '^APP_KEY=base64:.+' .env && [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --ansi
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --ansi
fi

exec "$@"
