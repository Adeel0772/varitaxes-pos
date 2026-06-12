-- POS SaaS Seed Data (demo / initial setup)
USE pos_saas;

-- Super Admin (password: Admin@123)
INSERT INTO super_admins (name, email, password) VALUES
('System Admin', 'admin@possaas.local', '$2y$10$7kK2tNU5eZONlRMFCjY3mOmM6bZzPX1E43P0Fc6gkshp1ovnXb3eC');

-- ─────────────────────────────────────────────
-- PERMISSIONS MATRIX
-- module.action format
-- ─────────────────────────────────────────────

-- SUPER ADMIN permissions
INSERT INTO roles_permissions (role, module, action) VALUES
('super_admin', 'shops', 'read'),
('super_admin', 'shops', 'create'),
('super_admin', 'shops', 'update'),
('super_admin', 'shops', 'delete'),
('super_admin', 'shops', 'approve'),
('super_admin', 'shops', 'suspend'),
('super_admin', 'dashboard', 'read'),
('super_admin', 'reports', 'read'),
('super_admin', 'activity_log', 'read'),
('super_admin', 'shops', 'impersonate');

-- SHOP OWNER permissions (full access to own shop)
INSERT INTO roles_permissions (role, module, action) VALUES
('owner', 'dashboard', 'read'),
('owner', 'products', 'read'),
('owner', 'products', 'create'),
('owner', 'products', 'update'),
('owner', 'products', 'delete'),
('owner', 'inventory', 'read'),
('owner', 'inventory', 'update'),
('owner', 'inventory', 'adjust'),
('owner', 'sales', 'read'),
('owner', 'sales', 'create'),
('owner', 'purchases', 'read'),
('owner', 'purchases', 'create'),
('owner', 'purchases', 'update'),
('owner', 'purchases', 'delete'),
('owner', 'customers', 'read'),
('owner', 'customers', 'create'),
('owner', 'customers', 'update'),
('owner', 'customers', 'delete'),
('owner', 'suppliers', 'read'),
('owner', 'suppliers', 'create'),
('owner', 'suppliers', 'update'),
('owner', 'suppliers', 'delete'),
('owner', 'reports', 'read'),
('owner', 'reports', 'export'),
('owner', 'users', 'read'),
('owner', 'users', 'create'),
('owner', 'users', 'update'),
('owner', 'users', 'delete'),
('owner', 'settings', 'read'),
('owner', 'settings', 'update'),
('owner', 'categories', 'read'),
('owner', 'categories', 'create'),
('owner', 'categories', 'update'),
('owner', 'categories', 'delete'),
('owner', 'brands', 'read'),
('owner', 'brands', 'create'),
('owner', 'brands', 'update'),
('owner', 'brands', 'delete'),
('owner', 'attributes', 'read'),
('owner', 'attributes', 'create'),
('owner', 'attributes', 'update'),
('owner', 'attributes', 'delete'),
('owner', 'barcodes', 'read'),
('owner', 'barcodes', 'print'),
('owner', 'invoices', 'read'),
('owner', 'invoices', 'print'),
('owner', 'activity_log', 'read');

-- MANAGER permissions
INSERT INTO roles_permissions (role, module, action) VALUES
('manager', 'dashboard', 'read'),
('manager', 'products', 'read'),
('manager', 'products', 'create'),
('manager', 'products', 'update'),
('manager', 'inventory', 'read'),
('manager', 'inventory', 'update'),
('manager', 'inventory', 'adjust'),
('manager', 'sales', 'read'),
('manager', 'sales', 'create'),
('manager', 'purchases', 'read'),
('manager', 'purchases', 'create'),
('manager', 'customers', 'read'),
('manager', 'customers', 'create'),
('manager', 'customers', 'update'),
('manager', 'suppliers', 'read'),
('manager', 'suppliers', 'create'),
('manager', 'reports', 'read'),
('manager', 'reports', 'export'),
('manager', 'categories', 'read'),
('manager', 'categories', 'create'),
('manager', 'categories', 'update'),
('manager', 'brands', 'read'),
('manager', 'brands', 'create'),
('manager', 'brands', 'update'),
('manager', 'attributes', 'read'),
('manager', 'barcodes', 'read'),
('manager', 'barcodes', 'print'),
('manager', 'invoices', 'read'),
('manager', 'invoices', 'print');

-- SALESMAN permissions
INSERT INTO roles_permissions (role, module, action) VALUES
('salesman', 'dashboard', 'read'),
('salesman', 'sales', 'read'),
('salesman', 'sales', 'create'),
('salesman', 'customers', 'read'),
('salesman', 'customers', 'create'),
('salesman', 'invoices', 'read'),
('salesman', 'invoices', 'print');

-- Demo shop (active) - password: Shop@123
INSERT INTO shops (name, slug, owner_name, phone, address, city, shop_type, status, invoice_format) VALUES
('Demo Sports Shop', 'demo-sports-shop', 'Ahmed Khan', '03001234567', 'Shop 12, Main Bazaar', 'Lahore', 'sports', 'active', 'a4');

SET @shop_id = LAST_INSERT_ID();

INSERT INTO users (tenant_id, name, email, phone, password, role, status) VALUES
(@shop_id, 'Ahmed Khan', 'owner@demo.local', '03001234567', '$2y$10$d4UPUwy29xktbm7dRTtJdeB9foskVPRywQyNUe5s7qMSjM4IhweBu', 'owner', 'active'),
(@shop_id, 'Sara Manager', 'manager@demo.local', '03009876543', '$2y$10$d4UPUwy29xktbm7dRTtJdeB9foskVPRywQyNUe5s7qMSjM4IhweBu', 'manager', 'active'),
(@shop_id, 'Ali Salesman', 'sales@demo.local', '03005556677', '$2y$10$d4UPUwy29xktbm7dRTtJdeB9foskVPRywQyNUe5s7qMSjM4IhweBu', 'salesman', 'active');

INSERT INTO settings (tenant_id, setting_key, setting_value) VALUES
(@shop_id, 'invoice_header', 'Demo Sports Shop - Quality Sports Equipment'),
(@shop_id, 'invoice_footer', 'Thank you for shopping with us! No returns without receipt.'),
(@shop_id, 'shop_phone', '03001234567'),
(@shop_id, 'currency_symbol', 'Rs.'),
(@shop_id, 'low_stock_days', '7'),
(@shop_id, 'receipt_copies', '1'),
(@shop_id, 'default_payment_method', 'cash'),
(@shop_id, 'show_barcode_on_invoice', '1');

INSERT INTO categories (tenant_id, name, code) VALUES
(@shop_id, 'Shirts', '22'),
(@shop_id, 'Balls', '05'),
(@shop_id, 'Bats', '10'),
(@shop_id, 'Stationery', '15');

INSERT INTO brands (tenant_id, name) VALUES
(@shop_id, 'Local Brand'),
(@shop_id, 'Imported'),
(@shop_id, 'Premium Sports');

INSERT INTO product_attributes (tenant_id, attribute_name) VALUES
(@shop_id, 'Size'),
(@shop_id, 'Color'),
(@shop_id, 'Type');
