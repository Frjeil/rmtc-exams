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

if [ -d /var/www/html/vendor/darkaonline/l5-swagger ]; then
    php artisan l5-swagger:generate
fi

exec "$@"
