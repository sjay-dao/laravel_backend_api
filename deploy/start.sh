#!/bin/sh
set -eu
port="${PORT:-10000}"
case "$port" in ''|*[!0-9]*) echo "Invalid PORT" >&2; exit 1 ;; esac
sed -i "s/^Listen 80$/Listen $port/" /etc/apache2/ports.conf
sed -i "s/\*:10000/*:$port/" /etc/apache2/sites-available/000-default.conf
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php8 artisan config:cache --no-interaction
php8 artisan route:cache --no-interaction
php8 artisan view:cache --no-interaction
chown -R www-data:www-data storage bootstrap/cache
# No migrations, seed, reset, worker or scheduler runs on service restart.
exec apache2-foreground
