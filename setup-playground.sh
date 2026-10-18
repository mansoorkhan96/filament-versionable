#!/usr/bin/env bash

set -e  # Exit immediately if a command exits with a non-zero status

if [ -d "./playground" ]; then
    echo "Playground already setup."
    exit 0
fi

echo "Installing filamentphp/demo project..."

git clone -b 5.x git@github.com:filamentphp/demo.git playground

echo "Configuring application..."

cd playground || exit 1

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

# herd exits 0 even when it fails, so inspect its output instead
SERVE_HINT="cd playground && php artisan serve, then open http://127.0.0.1:8000"
if command -v herd &> /dev/null; then
    HERD_OUTPUT=$(herd link filament-versionable 2>&1) || true
    if [[ "$HERD_OUTPUT" == *Error* ]]; then
        echo "Herd link failed: $HERD_OUTPUT"
    else
        SERVE_HINT="open http://filament-versionable.test"
    fi
fi

echo "Installing filament versionable plugin..."
composer config repositories.filament-versionable '{"type": "path", "url": "../", "options": {"symlink": true}}' --file composer.json
composer require mansoor/filament-versionable:@dev

php artisan vendor:publish --provider="Overtrue\LaravelVersionable\ServiceProvider"
php artisan migrate

cd ..

echo "Setup completed successfully."
echo "To run the playground: $SERVE_HINT"
