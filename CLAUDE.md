# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sistema de gestión de inventario, ventas y proveedores para óptica. Multi-sucursal inventory, sales, and supplier management system built with Laravel 7.

## Common Commands

```bash
# Development
npm run dev          # Build assets for development
npm run watch        # Watch mode with auto-rebuild
npm run hot          # Hot module replacement

# Production
npm run prod         # Build optimized assets

# Laravel
php artisan serve    # Start development server
php artisan migrate  # Run database migrations
php artisan tinker   # Interactive REPL

# Testing
./vendor/bin/phpunit              # Run all tests
./vendor/bin/phpunit --filter=TestName  # Run specific test
```

## Architecture

### Tech Stack
- **Backend**: Laravel 7 (PHP 7.2.5+), MySQL
- **Frontend**: Blade templates, Laravel Mix (Webpack), Sass, Axios
- **Auth**: Laravel Passport (OAuth 2.0) for API, legacy PHP session auth for web
- **PDF**: spipu/html2pdf, TCPDF
- **Images**: Intervention Image
- **Fiscal**: AFIP SDK (Argentine tax system integration)

### Key Directories
- `app/Http/Controllers/` - Main application logic (ProductoController, ProveedorController, ReporteController handle bulk of features)
- `app/Models/` - 33 Eloquent models (Producto, Stock, Proveedor, Cliente, Venta, Factura, Transferencia, etc.)
- `public/` - Web root containing legacy PHP files (login.php, caja.php, ventas.php, etc.)
- `resources/views/` - Blade templates organized by feature

### Dual Architecture
This project uses both Laravel MVC and legacy PHP scripts:
- **Laravel routes** (`routes/web.php`): CRUD operations for products, categories, suppliers, users, roles, transfers, reports
- **Legacy PHP** (`public/*.php`): Point of sale (caja.php, ventas.php), login, invoicing

### Key Models & Relationships
- `Producto` ↔ `Stock` (multi-location inventory via sucursal_id)
- `Producto` ↔ `Categoria`, `Proveedor`
- `Venta` ↔ `Cliente`, `Factura`
- `Transferencia` ↔ `Sucursales` (branch-to-branch transfers)
- `Usuario` ↔ `Rol`, `Sucursal`

### Route Patterns
Routes follow RESTful conventions with Spanish naming:
- `Route::resource('carga', 'ProductoController')` - Standard CRUD
- `producto.actualizar.stock/{id}/{stock}/{stock_minimo}/{sucursal}` - Stock updates
- `transferencia.cambiarstatus` - Status changes
- `reporte.*` - Various reports (facturas, stocks, pedidos)

## Setup Notes

Required directories (create if missing):
```bash
mkdir -p facturas presupuesto clientes assets/perfil upload_articles
```

Storage link for uploads:
```bash
php artisan storage:link
# or visit: /updateStorage
```

## Code Style

StyleCI configured with Laravel preset. Run before commits to maintain consistency.
