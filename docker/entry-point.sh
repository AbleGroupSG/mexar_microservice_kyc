#!/bin/bash


set -e

echo "⏳ Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
# Prefer mariadb-admin (provided by mariadb-client on Alpine), fallback to mysqladmin
if command -v mariadb-admin >/dev/null 2>&1; then
  DB_ADMIN=mariadb-admin
elif command -v mysqladmin >/dev/null 2>&1; then
  DB_ADMIN=mysqladmin
else
  echo "❌ Neither mariadb-admin nor mysqladmin found in PATH" >&2
  exit 1
fi
i=0; max=40
while :; do
  if $DB_ADMIN ping -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" --silent >/dev/null 2>&1; then
    break
  fi
  i=$((i+1)); [ "$i" -ge "$max" ] && { echo "MySQL wait timed out"; exit 1; }
  sleep 3
done

echo "MySQL is up - continuing..."

echo "⏳ Waiting for Redis at ${REDIS_HOST:-redis}..."
i=0; max=30
while :; do
  if redis-cli -h "${REDIS_HOST:-redis}" ping 2>/dev/null | grep -q PONG; then break; fi
  i=$((i+1)); [ "$i" -ge "$max" ] && { echo "Redis wait timed out"; exit 1; }
  sleep 2
done



# if RUN_MIGRATE is set to true, run migrations

if [ "$RUN_MIGRATE" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
else
    echo "Skipping migrations."
fi

# TODO: add control variable to run optimization commands
if [ "$APP_ENV" = "production" ]; then
    echo "Running optimization commands..."
    php artisan config:cache && php artisan route:cache && php artisan optimize && true
else
    echo "Skipping optimization commands."
fi

exec "$@"
