# retail-right-api-v3

## Project setup

    ddev start
    ddev ssh
    composer install
    cp .env.example .env
    vim .env

Requirements (.env):

    - Stripe
    - Mailgun / SMTP
    - Mongo
    - UPC

## Seed Data

    ddev ssh
    php artisan migrate:fresh
    php artisan db:seed --class=StateSeeder
    php artisan db:seed --class=RolesSeeder

## Mongo Migration

    ddev ssh
    php artisan mongo-migration:user 5c69c2e3f2a4f744ecf34ef1
    php artisan mongo-migration:stores
    php artisan mongo-migration:items 5c69c2e3f2a4f744ecf34ef1
    php artisan mongo-migration:orders 5c69c2e3f2a4f744ecf34ef1
    php artisan mongo-migration:returns 5c69c2e3f2a4f744ecf34ef1
    php artisan mongo-migration:manifest 5c69c2e3f2a4f744ecf34ef1

### Helpful Artisan Commands

    php artisan route:cache
    php artisan clear-compiled
    php artisan view:clear
    php artisan config:clear
    php artisan cache:clear
