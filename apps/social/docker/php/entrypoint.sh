#!/bin/bash

# Wait for database to be ready
echo "Waiting for database..."
until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" > /dev/null 2>&1; do
    echo "Database not ready, sleeping..."
    sleep 2
done

echo "Database is ready!"

# Install dependencies if vendor is missing
if [ ! -d "vendor" ]; then
    echo "Vendor directory not found. Installing dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Run migrations
echo "Running migrations..."
php bin/console.php migrate

# Start php-fpm
echo "Starting PHP-FPM..."
php-fpm
