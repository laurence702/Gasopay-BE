#!/bin/sh

# Exit on error
set -e

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
nginx -g 'daemon off;'