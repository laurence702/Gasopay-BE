#!/bin/bash

# Exit on error
set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
while ! nc -z $DB_HOST $DB_PORT; do
  sleep 1
done
echo "Database is ready!"

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Run database seeding
echo "Seeding the database..."
php artisan db:seed --force

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g 'daemon off;'
