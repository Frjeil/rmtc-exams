#!/bin/sh
set -e

cd /var/www/html

chown -R www-data:www-data storage bootstrap/cache

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate
fi

php artisan migrate --force

SEEDED=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null)
if [ "$SEEDED" = "0" ]; then
    php artisan db:seed --force
fi

if [ -d /var/www/html/vendor/darkaonline/l5-swagger ]; then
    php artisan l5-swagger:generate
fi

exec "$@"
