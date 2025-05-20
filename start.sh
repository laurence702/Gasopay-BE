#!/bin/sh

# Exit on error
set -e

# Run database migrations and seed the database
echo "Running database migrations and seeding..."
php artisan migrate --force --seed

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g 'daemon off;'
