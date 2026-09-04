#!/bin/bash
set -e

WORKDIR=/var/www/html

# Create .env from example if missing
if [ ! -f "$WORKDIR/.env" ]; then
    echo "Creating .env from .env.example..."
    cp "$WORKDIR/.env.example" "$WORKDIR/.env"
fi

# Generate APP_KEY if not set (checks the real environment first, since in
# production APP_KEY is injected by the host rather than living in .env)
if [ -z "${APP_KEY:-}" ]; then
    FILE_APP_KEY=$(grep -E "^APP_KEY=" "$WORKDIR/.env" | cut -d= -f2 | tr -d '"')
    if [ -z "$FILE_APP_KEY" ]; then
        echo "Generating application key..."
        php artisan key:generate --no-interaction
    fi
fi

# Install PHP dependencies if vendor is missing
if [ ! -d "$WORKDIR/vendor" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Build frontend assets if missing
if [ ! -d "$WORKDIR/public/build" ]; then
    echo "Installing and building frontend assets..."
    npm install
    npm run build
fi

# Storage symlink
php artisan storage:link --force 2>/dev/null || true

# Fix storage permissions
chown -R www-data:www-data "$WORKDIR/storage" "$WORKDIR/bootstrap/cache" 2>/dev/null || true
chmod -R 775 "$WORKDIR/storage" "$WORKDIR/bootstrap/cache" 2>/dev/null || true

# Wait for the database. Bounded and loud: an unreachable/misconfigured DB
# must never hang silently, since that leaves nginx/php-fpm never starting
# and no port ever opening (opaque "no open ports detected" from the host).
echo "Waiting for database at ${DB_HOST:-<unset>}:${DB_PORT:-5432}..."
DB_WAIT_ATTEMPTS=30
i=0
until DB_CHECK_OUTPUT=$(php -r "
try {
    new PDO(
        'pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-neondb};sslmode=${DB_SSLMODE:-require}',
        '${DB_USERNAME}',
        '${DB_PASSWORD}'
    );
    echo 'ok';
} catch (Exception \$e) {
    fwrite(STDERR, \$e->getMessage());
    exit(1);
}
" 2>&1); do
    i=$((i + 1))
    if [ "$i" -ge "$DB_WAIT_ATTEMPTS" ]; then
        echo "Database still unreachable after ${DB_WAIT_ATTEMPTS} attempts. Last error: ${DB_CHECK_OUTPUT}"
        echo "Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SSLMODE."
        exit 1
    fi
    echo "Database not ready yet (attempt ${i}/${DB_WAIT_ATTEMPTS}): ${DB_CHECK_OUTPUT}"
    sleep 2
done
echo "Database is ready."

# Run migrations
echo "Running migrations..."
php artisan migrate --force --no-interaction

# Seed on first run only
SEEDED_FLAG="$WORKDIR/storage/.docker_seeded"
if [ ! -f "$SEEDED_FLAG" ]; then
    echo "Seeding database..."
    php artisan db:seed --force --no-interaction
    touch "$SEEDED_FLAG"
fi

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
echo "App is ready at http://localhost:8080"
exec nginx -g 'daemon off;'
