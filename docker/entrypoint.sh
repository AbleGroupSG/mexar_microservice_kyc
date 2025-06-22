#!/bin/bash
set -e

echo "⏳ Waiting for MariaDB (raw check)..."
RETRIES=20
COUNT=0

until mysqladmin ping -hmariadb -uroot -ppassword --silent; do
  COUNT=$((COUNT+1))
  echo "  🔄 Attempt $COUNT/$RETRIES..."
  sleep 3
  if [ "$COUNT" -ge "$RETRIES" ]; then
    echo "❌ Database not reachable after $((RETRIES * 3)) seconds."
    exit 1
  fi
done

echo "✅ DB Ready. Running Laravel bootstrap..."
php artisan config:clear
php artisan config:cache

if [ "$LARAVEL_RUN_MIGRATION" = "true" ]; then
  echo "🔧 Running migrations..."
  php artisan migrate --force
else
  echo "⚠️ Migration skipped. Set LARAVEL_RUN_MIGRATION=true to enable."
fi

exec php-fpm