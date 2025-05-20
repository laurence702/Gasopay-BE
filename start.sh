#!/bin/bash

# Exit on error
set -e

# Configuration
DB_TIMEOUT=30  # seconds to wait for database
RETRY_INTERVAL=1  # seconds between retries

# Function to log messages
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Wait for database to be ready
log "Waiting for database to be ready..."
TIMEOUT_COUNT=0
while ! nc -z $DB_HOST $DB_PORT; do
    if [ $TIMEOUT_COUNT -ge $DB_TIMEOUT ]; then
        log "ERROR: Database connection timed out after ${DB_TIMEOUT} seconds"
        exit 1
    fi
    TIMEOUT_COUNT=$((TIMEOUT_COUNT + RETRY_INTERVAL))
    sleep $RETRY_INTERVAL
done
log "Database is ready!"

# Run database migrations
log "Running database migrations..."
if ! php artisan migrate --force; then
    log "ERROR: Database migration failed"
    exit 1
fi
log "Database migrations completed successfully"

# Run database seeding
log "Seeding the database..."
if ! php artisan db:seed --force; then
    log "ERROR: Database seeding failed"
    exit 1
fi
log "Database seeding completed successfully"

# Start PHP-FPM in the background
log "Starting PHP-FPM..."
php-fpm -D
log "PHP-FPM started successfully"

# Start Nginx in the foreground
log "Starting Nginx..."
nginx -g 'daemon off;'
