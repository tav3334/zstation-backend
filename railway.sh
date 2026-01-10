#!/bin/bash

echo "🚀 Starting Railway deployment..."

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "⚠️  Generating APP_KEY..."
    php artisan key:generate --force
fi

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Clear and cache config
echo "🔧 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment complete!"
