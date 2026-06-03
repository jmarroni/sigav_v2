# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this project is

SIGAV v2 — an inventory / POS / billing system for a multi-branch retail business (productos, stock por sucursal, pedidos, transferencias entre sucursales, facturación AFIP, notas de crédito, pedidos óptica, cajas, Mercado Libre / Mercado Artesanal integration). UI strings and most code are in Spanish.

The codebase is hybrid: a Laravel 7 application **and** a large legacy PHP application live side by side. Both are deployed together and both are still in active use. Understand which side of the system a change belongs to before editing.

## Stack

- PHP `^7.2.5`, Laravel `^7.0`, Laravel Passport `~9.0`
- MySQL (prod), SQLite in-memory (tests)
- Frontend: Laravel Mix 5 (webpack) + SCSS + plain JS — UI is mostly server-rendered (Blade or legacy PHP)
- PDFs: `spipu/html2pdf` (Laravel side), `tecnickcom/tcpdf` (legacy `public/`)
- HTTP: GuzzleHTTP + `php-curl-class/php-curl-class`
- Images: `intervention/image`
- AFIP (Argentine tax authority) electronic invoicing — certs under `public/AFIP/{cert,key}`

## Hybrid architecture — read this first

There are **two parallel applications** sharing the same database:

### 1. Laravel app (modern side)
- Entry: `public/index.php` → `bootstrap/app.php`
- Routes: `routes/web.php` (web) and `routes/api.php` (JSON, behind Passport `auth:api`)
- Controllers: `app/Http/Controllers/*Controller.php` and `app/Http/Controllers/Api/*`
- Eloquent models: `app/Models/*.php` (Producto, Categoria, Sucursales, Pedido, Stock, Transferencia, FacturasProveedores, PedidosOptica, …)
- Blade views: `resources/views/{productos,clientes,pedidos,transferencias,reportes,etiquetas,categorias,proveedores,roles,usuarios,login,layout}`
- Auth: Laravel session guard for Blade controllers (`$this->middleware('auth')`); Passport `auth:api` for `/api/auth/*` JSON endpoints
- Passwords: **bcrypt** (after the 2025-11 security migration)

### 2. Legacy PHP app (under `public/`)
- ~88 standalone `.php` scripts in `public/` (e.g. `login.php`, `ventas.php`, `facturar.php`, `pedidos.php`, `nota_credito.php`, `usuarios_api.php`, `stock_por_sucursal.php`)
- Auth: cookie-based (`$_COOKIE["kiosco"]`, `"sucursal"`, `"rol"`); password hash is `sha1($clave . SEMILLA)` from `public/conection.php`
- DB connection: **hardcoded** `mysqli_connect` credentials in `public/conection.php` (known tech-debt — do not hardcode new ones; do not delete the file without a migration plan)
- Many of these scripts use raw MySQLi with string interpolation — treat anything touched as suspect for SQL injection / XSS and prefer prepared statements when editing
- Subfolders (`AFIP/`, `presupuesto/`, `notas_credito/`, `branchs/`, `clientes/`, `cobros/`, …) hold generated artifacts (PDFs, images, txt logs)

The root route bridges the two worlds:
```php
Route::get('/', function () { return redirect("/login.php"); });
```
After legacy `login.php` sets cookies, the user is sent to Laravel routes like `/carga` or `/ventas.php` depending on `rol_id`.

### The two User models
- `App\Models\User` — Passport-enabled, table `users` (Laravel/JWT side)
- `App\Models\Usuario` — table `usuarios`, password column `clave`, `$hidden = ['clave']` (legacy/admin side)

When working on auth, confirm which model and which table the flow uses before editing.

## Common commands

```bash
# First-time setup
composer install
npm install
cp .env.example .env
php artisan key:generate
php -r "echo bin2hex(random_bytes(32));"   # generate API_SECRET_KEY, paste into .env
php artisan migrate
php artisan passport:install

# Dev server (Laravel routes only; legacy .php pages need a real web server with PHP)
php artisan serve

# Frontend assets
npm run dev          # one-off build
npm run watch        # rebuild on change
npm run prod         # production build

# Database
php artisan migrate
php artisan migrate:rollback
php artisan db:seed

# Routes / cache
php artisan route:list
php artisan config:clear
php artisan cache:clear

# Tests (see caveat below)
vendor/bin/phpunit
vendor/bin/phpunit --filter SomeTest
vendor/bin/phpunit tests/Feature/SomeTest.php
```

### Tests caveat
`phpunit.xml` declares `./tests/Unit` and `./tests/Feature` suites, but **no `tests/` directory exists** in the repo. There is no test infrastructure to run against today. If you write tests, you must create that directory and the base `TestCase` first. Do not claim "tests pass" without verifying the suite actually executed something.

## Security context (recent and important)

`SECURITY_FIXES_REPORT.md` documents 8 critical fixes merged in PR #25 (commit `74abf2d`). When working on this codebase:

- API authentication uses `config('app.api_secret_key')` from env `API_SECRET_KEY` — do not hardcode it back
- Passwords on the Laravel side are **bcrypt**; on the legacy side they are still **SHA1+SEMILLA** — existing legacy users have not been migrated, so a logged-in user may still be authenticated only via legacy cookies
- Destructive operations are exposed as `DELETE`/`POST` routes (not `GET`) — preserve the verbs in `routes/web.php`
- CORS is centralized in `config/cors.php` and reads `FRONTEND_URL` from env — do not re-add `header('Access-Control-Allow-Origin: *')` inside controllers
- `Producto` and `Usuario` models have `$guarded` to prevent mass assignment of `id` / `clave` / `password`
- The legacy `public/*.php` files have **not** been hardened to the same degree; SHA1 hashing, hardcoded DB credentials, and direct query interpolation still exist there

When you touch legacy `public/*.php`: prefer parameterized queries (`mysqli_prepare`), escape output, and surface the issue rather than silently propagating the existing pattern.

## Conventions worth knowing

- Naming is mostly Spanish (controllers, tables, columns, routes): `usuario`, `clave`, `rol_id`, `sucursal_id`, `proveedor`, `categoria`, `pedido`, `transferencia`, `cierreCajaReporte`
- Many legacy tables disable timestamps: `public $timestamps = false;`
- Models live in `app/Models/` (not the Laravel 5.x default `app/`), and `App\User` is not present — use `App\Models\User`
- `composer.json` autoloads `database/seeds` and `database/factories` via classmap
- `minimum-stability: dev` is set — be aware when adding dependencies

## Things to avoid

- Do not run `php artisan serve` and assume legacy `.php` pages work — they need a real PHP web server with the document root at `public/`
- Do not rename `public/login.php`, `public/conection.php`, or other legacy entry points without auditing every `header('Location:')` and form `action=` reference across `public/`
- Do not change route verbs from `DELETE`/`POST` back to `GET` for destructive actions
- Do not edit `composer.lock` or `package-lock.json` by hand
- Do not commit `public/AFIP/key/*` or `.env`
