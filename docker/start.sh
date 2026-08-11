#!/bin/sh
set -eu

php artisan migrate --force
php artisan optimize

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
