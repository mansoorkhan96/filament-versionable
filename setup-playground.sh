#!/usr/bin/env bash

set -e  # Exit immediately if a command exits with a non-zero status

if [ -d "./playground" ]; then
    echo "Playground already setup."
    exit 0
fi

echo "Installing filamentphp/demo project..."

git clone -b 4.x git@github.com:filamentphp/demo.git playground

echo "Configuring application..."

cd playground || exit 1

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

if command -v herd &> /dev/null; then
    herd link filament-versionable
fi

echo "Installing filament versionable plugin..."
composer config repositories.filament-versionable '{"type": "path", "url": "../", "options": {"symlink": true}}' --file composer.json
composer require mansoor/filament-versionable:@dev

cd ..

echo "Setup completed successfully."
