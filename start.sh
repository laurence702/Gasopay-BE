#!/bin/sh

# Exit on error
set -e

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
nginx -g 'daemon off;'

composer install --no-interaction --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force --seed 