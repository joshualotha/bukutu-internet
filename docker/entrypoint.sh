#!/bin/sh
set -e

# Run Laravel setup commands
if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "base64:..." ]; then
    echo "Caching Laravel configuration..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true

    # Run migrations (with --force for production)
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo "Running database migrations..."
        php artisan migrate --force --ansi
    fi

    # Clear old horizon entries
    if [ "${CLEAR_HORIZON:-false}" = "true" ]; then
        php artisan horizon:clear 2>/dev/null || true
    fi
fi

# Set correct permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Execute the main command
exec "$@"
