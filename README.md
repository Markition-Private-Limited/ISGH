# ISGH App

## Overview

`isgh-app` is a Laravel 12 application for mosque membership registration, OTP email verification, Stripe checkout, and Wild Apricot membership syncing.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ / npm
- SQLite, MySQL, or PostgreSQL for database
- A writable `storage/` and `bootstrap/cache/`

## Initial Setup

1. Install PHP dependencies:

```bash
composer install
```

2. Install frontend dependencies:

```bash
npm install
```

3. Copy the example environment file:

```bash
cp .env.example .env
```

4. Generate the application key:

```bash
php artisan key:generate
```

5. Create SQLite database file (when using SQLite):

```bash
touch database/database.sqlite
```

6. Run database migrations:

```bash
php artisan migrate
```

## Environment Variables

Update `.env` with your environment settings. The app expects the following values at minimum:

```env
APP_NAME=ISGH
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
# DB_DATABASE=/full/path/to/database/database.sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

WILD_APRICOT_API_KEY=...
WILD_APRICOT_ACCOUNT_ID=...
WILD_APRICOT_SECRET_KEY=...

ADMIN_TOKEN=...
```

> For local development, the repository includes a working `.env` with SQLite values. Update the Stripe, Wild Apricot, and mail values before testing real integrations.

## Development

Start the app in development mode with Laravel, queue worker, logs, and Vite:

```bash
composer run dev
```

Alternatively, run the backend and frontend separately:

```bash
php artisan serve
npm run dev
```

## Build for Production

```bash
npm run build
```

## Common Commands

```bash
# Run the setup script (install, .env, key, migrate, build)
composer run setup

# Run migrations
php artisan migrate

# Run tests
php artisan test

# Run a specific test
php artisan test --filter=MembershipWildApricotTest

# PHP code style lint / fix
vendor/bin/pint
```



## Production Checklist

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Use a real database driver (`mysql` or `pgsql`)
- Configure a real mail driver
- Register a Stripe webhook endpoint
