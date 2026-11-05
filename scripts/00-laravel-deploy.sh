#!/usr/bin/env bash
set -e

echo "Running database migrations..."
php /var/www/html/artisan migrate --force

echo "Caching Laravel config, routes, and views..."
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache
