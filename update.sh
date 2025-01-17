#!/bin/bash

echo "⬇️ Getting updates..."
git pull || { echo "❌ Failed to pull updates from the repository."; exit 1; }

echo # Move to a new line
echo "🔃 Installing npm dependencies..."
npm ci || { echo "❌ Failed to install npm dependencies."; exit 1; }

echo # Move to a new line
echo "🏗️ Building npm dependencies..."
npm run build || { echo "❌ Failed to build npm dependencies."; exit 1; }

echo # Move to a new line
echo "🔃 Installing composer dependencies..."
composer install --no-interaction --prefer-dist || { echo "❌ Failed to install composer dependencies."; exit 1; }

echo # Move to a new line
echo "🗃️ Running database migrations..."
# Ask the user if they want to refresh the database or just force the migrations
read -p "Do you want to refresh the database? This will wipe all data and reseed it. (y/N): " -n 1 -r
echo    # Move to a new line

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo # Move to a new line
    echo "🔄 Refreshing the database and seeding..."
    php artisan migrate:fresh --seed || { echo "❌ Failed to refresh and seed the database."; exit 1; }
else
    echo # Move to a new line
    echo "🔄 Running migrations with force..."
    php artisan migrate --force || { echo "❌ Failed to run migrations."; exit 1; }
fi

echo # Move to a new line
echo "✅ Clearing cache..."
php artisan optimize:clear || { echo "❌ Failed to clear the cache."; exit 1; }

echo # Move to a new line
echo "🚀 Deployment process completed successfully!"
