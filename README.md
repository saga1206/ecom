# eCom: Product & Warehouse Management (Laravel)

A Laravel web application for managing products and the warehouses that store them. Products carry dual pricing (USD and JPY), stock counts, images, and a warehouse assignment. Access to management screens is restricted to admin users, and the whole stack runs in Docker with a single command.

---

## Table of Contents

1. [Features](#features)
2. [Tech Stack](#tech-stack)
3. [Project Structure](#project-structure)
4. [Data Model](#data-model)
5. [Routes](#routes)
6. [Roles & Access Control](#roles--access-control)
7. [Getting Started (Docker)](#getting-started-docker)
8. [Getting Started (Without Docker)](#getting-started-without-docker)
9. [Environment Variables](#environment-variables)
10. [Useful Commands](#useful-commands)
11. [Testing](#testing)
12. [Production Notes](#production-notes)
13. [Troubleshooting](#troubleshooting)
14. [Roadmap](#roadmap)

---

## Features

**Authentication & accounts**
- Register, log in, log out, password reset (Laravel Breeze)
- Profile page where every logged-in user can update their name and email, change their password, or delete their account
- Role-based access: `user` and `admin`

**Product management (admin only)**
- Create, view, edit, and delete products
- Fields: name, description, price in USD, price in JPY, stock, warehouse, image
- Image upload with validation (image files, max 2 MB); old images are removed on replace or delete
- Paginated product list showing warehouse and stock

**Warehouse management (admin only)**
- Create, view, edit, and delete warehouses
- Fields: name, location, inspection status (`pending` by default)
- Each warehouse shows the products it holds

**Quality of life**
- Server-side validation with error messages on every form
- Flash messages after create, update, and delete
- Seeders for an admin account and sample warehouses and products
- One-command Docker setup (app + MySQL) with persistent database and uploads

---

## Tech Stack

| Layer      | Technology                                  |
|------------|---------------------------------------------|
| Backend    | PHP 8.3, Laravel 12                         |
| Frontend   | Blade, Tailwind CSS, Alpine.js, Vite        |
| Auth       | Laravel Breeze                              |
| Database   | MySQL 8 (Docker) / SQLite or MySQL (local)  |
| Web server | Apache (Docker image)                       |
| Packaging  | Docker, Docker Compose                      |

---

## Project Structure

```
eCom/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                   # Breeze auth controllers
│   │   │   ├── ProductController.php   # Product CRUD
│   │   │   ├── WarehouseController.php # Warehouse CRUD
│   │   │   └── ProfileController.php   # Profile edit/update/delete
│   │   └── Middleware/
│   │       └── EnsureUserIsAdmin.php   # `admin` route middleware
│   └── Models/
│       ├── Product.php
│       ├── User.php
│       └── Warehouse.php
├── bootstrap/app.php                   # Registers the `admin` middleware alias
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── AdminSeeder.php
├── docker/
│   └── entrypoint.sh                   # Migrates, links storage, starts Apache
├── resources/views/
│   ├── products/                       # index, create, edit, show
│   ├── warehouses/                     # index, create, edit, show
│   ├── auth/  layouts/  components/  profile/
│   └── dashboard.blade.php
├── routes/web.php
├── Dockerfile
├── docker-compose.yml
└── .dockerignore
```

---

## Data Model

```
users                         warehouses                  products
-----                         ----------                  --------
id                            id                          id
name                          name                        name
email (unique)                location                    description (nullable)
password                      inspection_status           price_usd  decimal(10,2)
role  (user | admin)          created_at / updated_at     price_jpy  decimal(12,2)
email_verified_at                                         stock      unsigned int
remember_token                                            warehouse_id  -> warehouses.id
created_at / updated_at                                   image (nullable path)
                                                          created_at / updated_at
```

**Relationships**
- A `Warehouse` has many `Product`s
- A `Product` belongs to one `Warehouse`
- Deleting a warehouse deletes its products (`cascadeOnDelete`)

---

## Routes

| Method    | URI                       | Access        | Purpose                  |
|-----------|---------------------------|---------------|--------------------------|
| GET       | `/`                       | Public        | Welcome page             |
| GET       | `/dashboard`              | Auth          | Dashboard                |
| GET/PATCH/DELETE | `/profile`         | Auth          | Manage own profile       |
| GET       | `/products`               | Admin         | List products            |
| GET/POST  | `/products/create`, `/products` | Admin   | Create product           |
| GET       | `/products/{id}`          | Admin         | View product             |
| GET/PUT   | `/products/{id}/edit`, `/products/{id}` | Admin | Edit product      |
| DELETE    | `/products/{id}`          | Admin         | Delete product           |
| *         | `/warehouses/...`         | Admin         | Same resource routes     |
| *         | `/login`, `/register`, `/forgot-password`, ... | Guest | Breeze auth |

List every route with `php artisan route:list`.

---

## Roles & Access Control

- Every user has a `role` column (`user` by default).
- `User::isAdmin()` returns `true` when `role === 'admin'`.
- The `admin` middleware (`EnsureUserIsAdmin`) aborts with **403** for non-admins.
- Profile routes only need `auth`, so every user can manage their own account.
- Product and warehouse routes need `auth` + `admin`.

The seeder creates the first admin (see [Default Admin Account](#default-admin-account)).

---

## Getting Started (Docker)

### Prerequisites
- Docker 24+ and Docker Compose v2

### 1. Clone and configure

```bash
git clone <your-repo-url> ecom
cd ecom
cp .env.example .env
```

Open `.env` and set at least:

```env
APP_NAME=eCom
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_DATABASE=ecom
DB_USERNAME=ecom
DB_PASSWORD=change-me
DB_ROOT_PASSWORD=change-me-too

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me-now
```

### 2. Generate an app key

```bash
docker compose build
docker compose run --rm app php artisan key:generate --show
```

Copy the printed `base64:...` value into `APP_KEY=` in `.env`.

### 3. Start everything

```bash
docker compose up -d
```

On start the container waits for MySQL, runs migrations, links the storage folder, and starts Apache.

### 4. Seed the admin account (first run only)

```bash
docker compose exec app php artisan db:seed
```

### 5. Open the app

- App: http://localhost:8000
- Log in with the admin account below, then open **Products** and **Warehouses**.

### Default Admin Account

| Field    | Value                                 |
|----------|---------------------------------------|
| Email    | `ADMIN_EMAIL` from `.env`             |
| Password | `ADMIN_PASSWORD` from `.env`          |

Change the password after the first login and never keep the defaults in production.

### Stopping and resetting

```bash
docker compose down            # stop containers, keep data
docker compose down -v         # stop and DELETE database + uploaded images
```

### What Docker gives you

| Service | Image            | Port (host) | Notes                                   |
|---------|------------------|-------------|-----------------------------------------|
| `app`   | built from `Dockerfile` (PHP 8.3 + Apache) | 8000 | Runs migrations on start |
| `db`    | `mysql:8.0`      | 3307        | Data kept in the `db_data` volume       |

Uploaded product images live in the `storage_data` volume, so they survive rebuilds.

---

## Getting Started (Without Docker)

### Prerequisites
- PHP 8.2+ with `pdo_mysql` (or `pdo_sqlite`), `mbstring`, `gd`, `zip`, `bcmath`
- Composer 2
- Node.js 20+ and npm

```bash
git clone <your-repo-url> ecom
cd ecom

composer install
npm install

cp .env.example .env
php artisan key:generate

# Configure DB_* in .env (SQLite works out of the box with the default Laravel 12 .env)
php artisan migrate --seed
php artisan storage:link

# Run the app and asset watcher in two terminals
php artisan serve
npm run dev
```

Open http://127.0.0.1:8000.

---

## Environment Variables

| Variable          | Description                              | Example                |
|-------------------|------------------------------------------|------------------------|
| `APP_NAME`        | Application name                         | `eCom`                 |
| `APP_ENV`         | `local` or `production`                  | `local`                |
| `APP_KEY`         | Encryption key (required)                | `base64:...`           |
| `APP_DEBUG`       | Show detailed errors (off in production) | `true`                 |
| `APP_URL`         | Base URL                                 | `http://localhost:8000`|
| `DB_DATABASE`     | Database name                            | `ecom`                 |
| `DB_USERNAME`     | Database user                            | `ecom`                 |
| `DB_PASSWORD`     | Database password                        | `change-me`            |
| `DB_ROOT_PASSWORD`| MySQL root password (Docker only)        | `change-me-too`        |
| `ADMIN_EMAIL`     | Email for the seeded admin               | `admin@example.com`    |
| `ADMIN_PASSWORD`  | Password for the seeded admin            | `change-me-now`        |

In Docker, `DB_CONNECTION`, `DB_HOST` and `DB_PORT` are set by `docker-compose.yml`, so you don't need to edit them.

---

## Useful Commands

Run these inside the container with `docker compose exec app <command>`, or directly without Docker.

```bash
php artisan migrate                # run new migrations
php artisan migrate:fresh --seed   # wipe the database and reseed (destroys data)
php artisan route:list             # list all routes
php artisan tinker                 # interactive shell
php artisan optimize:clear         # clear config/route/view caches
php artisan test                   # run the test suite
```

Logs: `docker compose logs -f app`

---

## Testing

```bash
docker compose exec app php artisan test
```

Suggested coverage:
- Guests are redirected to login when visiting `/products`
- Non-admin users get a 403 on `/products` and `/warehouses`
- Admins can create, update, and delete products and warehouses
- Validation rejects negative prices, missing names, and unknown `warehouse_id`
- Image uploads are stored and removed with the product

---

## Production Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use strong, unique values for `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, and `ADMIN_PASSWORD`.
- Put a reverse proxy (Nginx, Caddy, Traefik) with HTTPS in front of the `app` container.
- Don't publish the MySQL port in production; remove the `ports` entry for `db` in `docker-compose.yml`.
- Cache configuration for speed: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- Back up the `db_data` and `storage_data` volumes regularly.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `APP_KEY is not set` on start | Generate a key (step 2) and put it in `.env` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL is still starting; wait a few seconds or check `docker compose logs db` |
| Images upload but don't display | Run `php artisan storage:link` (done automatically in Docker) |
| 403 on `/products` | The logged-in user's `role` isn't `admin`; re-run the seeder or update the user |
| Styles missing in Docker | The image builds assets during `docker compose build`; rebuild with `docker compose build --no-cache` |
| Port 8000 or 3307 already in use | Change the host port in `docker-compose.yml` |

---

## Roadmap

- [ ] Product search, filtering, and sorting
- [ ] Low-stock alerts on the dashboard
- [ ] Warehouse inspection workflow (status transitions and history)
- [ ] CSV import/export for products
- [ ] Public storefront with cart and orders
- [ ] Automated currency conversion between USD and JPY
- [ ] CI pipeline (tests + Docker build)

---

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
