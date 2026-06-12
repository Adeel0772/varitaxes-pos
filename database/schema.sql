-- POS SaaS Database Schema
-- MySQL 8.0+ | Multi-tenant via tenant_id column strategy

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS pos_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_saas;

-- ─────────────────────────────────────────────
-- SHOPS (tenants)
-- ─────────────────────────────────────────────
CREATE TABLE shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    owner_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    shop_type ENUM('sports','stationery','clothing','general','other') DEFAULT 'general',
    logo VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','active','suspended') DEFAULT 'pending',
    invoice_format ENUM('a4','carbon') DEFAULT 'a4',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_shops_status (status),
    INDEX idx_shops_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SUPER ADMINS (no tenant_id)
-- ─────────────────────────────────────────────
CREATE TABLE super_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- ROLES & PERMISSIONS
-- ─────────────────────────────────────────────
CREATE TABLE roles_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('super_admin','owner','manager','salesman') NOT NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_role_module_action (role, module, action),
    INDEX idx_rp_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('owner','manager','salesman') NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_users_tenant_email (tenant_id, email),
    INDEX idx_users_tenant (tenant_id),
    INDEX idx_users_role (role),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- LOGIN ATTEMPTS (rate limiting)
-- ─────────────────────────────────────────────
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_email_ip (email, ip_address),
    INDEX idx_login_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────────
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(10) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_categories_tenant (tenant_id),
    INDEX idx_categories_parent (parent_id),
    CONSTRAINT fk_categories_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- BRANDS
-- ─────────────────────────────────────────────
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_brands_tenant (tenant_id),
    CONSTRAINT fk_brands_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- PRODUCT ATTRIBUTES
-- ─────────────────────────────────────────────
CREATE TABLE product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_pa_tenant (tenant_id),
    CONSTRAINT fk_pa_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_attribute_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    attribute_id INT NOT NULL,
    value VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_pav_tenant (tenant_id),
    INDEX idx_pav_attribute (attribute_id),
    CONSTRAINT fk_pav_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_pav_attribute FOREIGN KEY (attribute_id) REFERENCES product_attributes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- PRODUCTS
-- ─────────────────────────────────────────────
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    product_code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    category_id INT DEFAULT NULL,
    brand_id INT DEFAULT NULL,
    product_type VARCHAR(100) DEFAULT NULL,
    size VARCHAR(50) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    origin VARCHAR(100) DEFAULT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    sale_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    min_sale_price DECIMAL(10,2) DEFAULT 0.00,
    barcode VARCHAR(100) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_products_tenant_code (tenant_id, product_code),
    INDEX idx_products_tenant (tenant_id),
    INDEX idx_products_barcode (tenant_id, barcode),
    INDEX idx_products_name (tenant_id, name),
    CONSTRAINT fk_products_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- PRODUCT VARIANTS
-- ─────────────────────────────────────────────
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    product_id INT NOT NULL,
    attributes JSON DEFAULT NULL,
    additional_price_adjustment DECIMAL(10,2) DEFAULT 0.00,
    stock_qty INT DEFAULT 0,
    barcode VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_pv_tenant (tenant_id),
    INDEX idx_pv_product (product_id),
    INDEX idx_pv_barcode (tenant_id, barcode),
    CONSTRAINT fk_pv_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_pv_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- INVENTORY
-- ─────────────────────────────────────────────
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    qty_in_stock INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_inventory_product_variant (tenant_id, product_id, variant_id),
    INDEX idx_inventory_tenant (tenant_id),
    INDEX idx_inventory_low_stock (tenant_id, qty_in_stock, low_stock_threshold),
    CONSTRAINT fk_inventory_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_inventory_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- STOCK HISTORY
-- ─────────────────────────────────────────────
CREATE TABLE stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    change_qty INT NOT NULL,
    qty_before INT NOT NULL,
    qty_after INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference_type ENUM('purchase','sale','adjustment','return','initial') DEFAULT 'adjustment',
    reference_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_sh_tenant (tenant_id),
    INDEX idx_sh_product (product_id),
    CONSTRAINT fk_sh_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_sh_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SUPPLIERS
-- ─────────────────────────────────────────────
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT,
    city VARCHAR(100) DEFAULT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_suppliers_tenant (tenant_id),
    CONSTRAINT fk_suppliers_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- PURCHASES
-- ─────────────────────────────────────────────
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    supplier_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_purchases_tenant (tenant_id),
    INDEX idx_purchases_date (tenant_id, purchase_date),
    CONSTRAINT fk_purchases_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    qty INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_pi_tenant (tenant_id),
    INDEX idx_pi_purchase (purchase_id),
    CONSTRAINT fk_pi_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- CUSTOMERS
-- ─────────────────────────────────────────────
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT,
    notes TEXT,
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_customers_tenant (tenant_id),
    INDEX idx_customers_phone (tenant_id, phone),
    CONSTRAINT fk_customers_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SALES
-- ─────────────────────────────────────────────
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    sale_number VARCHAR(50) NOT NULL,
    customer_id INT DEFAULT NULL,
    salesman_id INT NOT NULL,
    sale_date DATETIME NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_type ENUM('flat','percent') DEFAULT 'flat',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash','jazzcash','easypaisa','credit','other') DEFAULT 'cash',
    amount_tendered DECIMAL(10,2) DEFAULT NULL,
    change_amount DECIMAL(10,2) DEFAULT NULL,
    notes TEXT,
    invoice_printed TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_sales_number (tenant_id, sale_number),
    INDEX idx_sales_tenant (tenant_id),
    INDEX idx_sales_date (tenant_id, sale_date),
    INDEX idx_sales_salesman (tenant_id, salesman_id),
    CONSTRAINT fk_sales_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_sales_salesman FOREIGN KEY (salesman_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    product_code VARCHAR(50) NOT NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    qty INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_per_item DECIMAL(10,2) DEFAULT 0.00,
    discount_type ENUM('flat','percent') DEFAULT 'flat',
    final_price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_si_tenant (tenant_id),
    INDEX idx_si_sale (sale_id),
    INDEX idx_si_product (product_id),
    CONSTRAINT fk_si_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_si_sale FOREIGN KEY (sale_id) REFERENCES sales(id),
    CONSTRAINT fk_si_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- CUSTOMER LEDGER (Khata / Udhaar)
-- ─────────────────────────────────────────────
CREATE TABLE customer_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    customer_id INT NOT NULL,
    sale_id INT DEFAULT NULL,
    transaction_type ENUM('sale','payment','return') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_cl_tenant (tenant_id),
    INDEX idx_cl_customer (tenant_id, customer_id),
    CONSTRAINT fk_cl_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id),
    CONSTRAINT fk_cl_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_cl_sale FOREIGN KEY (sale_id) REFERENCES sales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- SETTINGS
-- ─────────────────────────────────────────────
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_settings_tenant_key (tenant_id, setting_key),
    INDEX idx_settings_tenant (tenant_id),
    CONSTRAINT fk_settings_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- ACTIVITY LOG
-- ─────────────────────────────────────────────
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    user_type ENUM('super_admin','user') DEFAULT 'user',
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INT DEFAULT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_al_tenant (tenant_id),
    INDEX idx_al_module (module),
    INDEX idx_al_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sale number sequence helper per tenant per day
CREATE TABLE sale_sequences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    sale_date DATE NOT NULL,
    last_sequence INT DEFAULT 0,
    UNIQUE KEY uk_sale_seq (tenant_id, sale_date),
    CONSTRAINT fk_sale_seq_tenant FOREIGN KEY (tenant_id) REFERENCES shops(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
