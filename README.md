# POS SaaS — Multi-Tenant Point of Sale

Production-ready multi-tenant POS system for Pakistani retail shops. Built with Core PHP 8.1+, MySQL, Bootstrap 5.

## Requirements

- PHP 8.1+ with PDO MySQL, GD, mbstring
- MySQL 8.0+
- Apache with mod_rewrite (XAMPP recommended)
- Composer

## Installation

1. Clone/copy to your web root (e.g. `c:\xampp\htdocs\pos`)

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure database in `config/database.php`

4. Import database:
   ```bash
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
   ```

5. Ensure Apache `mod_rewrite` is enabled. Access via `http://localhost/pos/`

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@possaas.local | Admin@123 |
| Shop Owner | owner@demo.local | Shop@123 |
| Manager | manager@demo.local | Shop@123 |
| Salesman | sales@demo.local | Shop@123 |

## Features

- Multi-tenant SaaS with shop registration & approval workflow
- Role-based permissions (Super Admin, Owner, Manager, Salesman)
- Products with auto-generated codes & barcodes
- Inventory, purchases, stock adjustments
- Full POS sale screen with khata/udhaar support
- A4 & thermal invoice printing + PDF export
- Barcode label printing (CODE128)
- 11 comprehensive reports with CSV/PDF export
- Activity logging & security (CSRF, rate limiting, tenant isolation)

## Folder Structure

See project specification for full MVC layout under `modules/`, `views/`, `core/`, `config/`.

## Smoke Tests

```bash
php tests/smoke-test.php
```

## Complete Shop Flow

1. **Register** → `/register` (pending until super admin approves)
2. **Super admin** → Approve shop at `/admin/shops`
3. **Owner login** → Settings, Categories, Brands, Products
4. **Stock in** → Suppliers → Purchases (or set initial stock on product)
5. **Sell** → POS Sale → Complete → Print Invoice
6. **Khata** → Customers → credit sale → record payment later
7. **Reports** → `/reports` (11 report types, CSV/PDF export)

## Roles

| Role | Access |
|------|--------|
| Super Admin | All shops, approve/suspend, impersonate (read-only) |
| Owner | Full shop access + settings + users |
| Manager | Products, inventory, sales, purchases, reports (no settings) |
| Salesman | POS only + own today's sales |
