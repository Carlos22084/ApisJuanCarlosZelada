#!/bin/sh
set -eu

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

php artisan migrate --force
php artisan app:seed-demo-if-empty --no-interaction
php artisan optimize

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
