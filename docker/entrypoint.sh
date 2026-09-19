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

# Seed only when the database itself is empty. A local flag file isn't
# enough on hosts (e.g. Fly Machines) that rebuild the container's disk on
# every deploy — that would re-run db:seed against a persistent external DB
# on every deploy and clobber any admin edits, since the seeders use
# updateOrCreate keyed by slug.
PRODUCT_COUNT=$(php artisan tinker --execute="echo DB::table('products')->count();" 2>/dev/null | tail -1)
if [ "${PRODUCT_COUNT:-0}" -eq 0 ] 2>/dev/null; then
    echo "Seeding database (empty)..."
    php artisan db:seed --force --no-interaction
fi

# Pre-compile Blade views, routes, and config while still running as root.
# php-fpm workers run as www-data and can't write storage/framework/views at
# request time on this host, which turns a harmless tempnam() fallback notice
# into a fatal 500 on every page. Caching now means no runtime compile is
# ever needed.
echo "Caching views, routes, and config..."
php artisan view:cache
php artisan route:cache
php artisan config:cache
chown -R www-data:www-data "$WORKDIR/storage" "$WORKDIR/bootstrap/cache" 2>/dev/null || true

# Start the Laravel scheduler in the background (drives bakong:check-pending
# among other scheduled tasks — routes/console.php registers what runs).
php artisan schedule:work >> "$WORKDIR/storage/logs/schedule.log" 2>&1 &

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
echo "App is ready at http://localhost:8080"
exec nginx -g 'daemon off;'
