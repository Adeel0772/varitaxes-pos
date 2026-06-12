<?php

namespace Core;

class App
{
    private array $routes = [];
    private string $defaultController = 'Dashboard';
    private string $defaultMethod = 'index';

    public function __construct()
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->routes = [
            ''                    => ['Dashboard', 'index'],
            'auth/login'          => ['Auth', 'login'],
            'auth/logout'         => ['Auth', 'logout'],
            'register'            => ['Auth', 'register'],
            'register/submit'     => ['Auth', 'registerSubmit'],

            // Super Admin
            'admin/dashboard'     => ['Shops', 'adminDashboard'],
            'admin/shops'         => ['Shops', 'index'],
            'admin/shops/approve' => ['Shops', 'approve'],
            'admin/shops/suspend' => ['Shops', 'suspend'],
            'admin/shops/view'    => ['Shops', 'show'],
            'admin/impersonate'   => ['Shops', 'impersonate'],
            'admin/stop-impersonate'=> ['Shops', 'stopImpersonate'],

            // Dashboard
            'dashboard'           => ['Dashboard', 'index'],

            // Users
            'users'               => ['Users', 'index'],
            'users/create'        => ['Users', 'create'],
            'users/store'         => ['Users', 'store'],
            'users/edit'          => ['Users', 'edit'],
            'users/update'        => ['Users', 'update'],
            'users/delete'        => ['Users', 'delete'],

            // Settings
            'settings'            => ['Settings', 'index'],
            'settings/update'     => ['Settings', 'update'],
            'settings/logo'       => ['Settings', 'logo'],

            // Categories
            'categories'          => ['Categories', 'index'],
            'categories/create'   => ['Categories', 'create'],
            'categories/store'    => ['Categories', 'store'],
            'categories/edit'     => ['Categories', 'edit'],
            'categories/update'   => ['Categories', 'update'],
            'categories/delete'   => ['Categories', 'delete'],
            'categories/ajax-store'=> ['Categories', 'ajaxStore'],

            // Brands
            'brands'              => ['Brands', 'index'],
            'brands/create'       => ['Brands', 'create'],
            'brands/store'        => ['Brands', 'store'],
            'brands/edit'         => ['Brands', 'edit'],
            'brands/update'       => ['Brands', 'update'],
            'brands/delete'       => ['Brands', 'delete'],
            'brands/ajax-store'   => ['Brands', 'ajaxStore'],

            // Attributes
            'attributes'          => ['Attributes', 'index'],
            'attributes/create'   => ['Attributes', 'create'],
            'attributes/store'    => ['Attributes', 'store'],
            'attributes/edit'     => ['Attributes', 'edit'],
            'attributes/update'   => ['Attributes', 'update'],
            'attributes/delete'   => ['Attributes', 'delete'],
            'attributes/values'   => ['Attributes', 'values'],
            'attributes/add-value'=> ['Attributes', 'addValue'],
            'attributes/delete-value'=> ['Attributes', 'deleteValue'],

            // Products
            'products'            => ['Products', 'index'],
            'products/create'     => ['Products', 'create'],
            'products/store'      => ['Products', 'store'],
            'products/edit'       => ['Products', 'edit'],
            'products/update'     => ['Products', 'update'],
            'products/delete'     => ['Products', 'delete'],
            'products/view'       => ['Products', 'show'],
            'products/toggle-status'=> ['Products', 'toggleStatus'],
            'products/generate-code'=> ['Products', 'generateCode'],
            'products/search'     => ['Products', 'search'],
            'products/image'      => ['Products', 'image'],

            // Suppliers
            'suppliers'           => ['Suppliers', 'index'],
            'suppliers/create'    => ['Suppliers', 'create'],
            'suppliers/store'     => ['Suppliers', 'store'],
            'suppliers/edit'      => ['Suppliers', 'edit'],
            'suppliers/update'    => ['Suppliers', 'update'],
            'suppliers/delete'    => ['Suppliers', 'delete'],
            'suppliers/ajax-store'=> ['Suppliers', 'ajaxStore'],

            // Purchases
            'purchases'           => ['Purchases', 'index'],
            'purchases/create'    => ['Purchases', 'create'],
            'purchases/store'     => ['Purchases', 'store'],
            'purchases/view'      => ['Purchases', 'show'],
            'purchases/edit'      => ['Purchases', 'edit'],
            'purchases/update'    => ['Purchases', 'update'],

            // Inventory
            'inventory'           => ['Inventory', 'index'],
            'inventory/adjust'    => ['Inventory', 'adjust'],
            'inventory/history'   => ['Inventory', 'history'],
            'inventory/low-stock' => ['Inventory', 'lowStock'],

            // Customers
            'customers'           => ['Customers', 'index'],
            'customers/create'    => ['Customers', 'create'],
            'customers/store'     => ['Customers', 'store'],
            'customers/edit'      => ['Customers', 'edit'],
            'customers/update'    => ['Customers', 'update'],
            'customers/view'      => ['Customers', 'show'],
            'customers/delete'    => ['Customers', 'delete'],
            'customers/payment'   => ['Customers', 'payment'],
            'customers/statement' => ['Customers', 'statement'],
            'customers/search'    => ['Customers', 'search'],

            // Sales / POS
            'sales'               => ['Sales', 'index'],
            'sales/pos'           => ['Sales', 'pos'],
            'sales/complete'      => ['Sales', 'complete'],
            'sales/view'          => ['Sales', 'show'],
            'sales/success'       => ['Sales', 'success'],
            'sales/my-today'      => ['Sales', 'myToday'],
            'sales/variants'      => ['Sales', 'variants'],

            // Invoices
            'invoices/print'      => ['Invoices', 'print'],
            'invoices/pdf'        => ['Invoices', 'pdf'],

            // Barcodes
            'barcodes'            => ['Barcodes', 'index'],
            'barcodes/print'      => ['Barcodes', 'print'],
            'barcodes/image'      => ['Barcodes', 'image'],

            // Reports
            'reports'             => ['Reports', 'index'],
            'reports/sales'       => ['Reports', 'sales'],
            'reports/daily-summary'=> ['Reports', 'dailySummary'],
            'reports/inventory'   => ['Reports', 'inventory'],
            'reports/low-stock'   => ['Reports', 'lowStock'],
            'reports/purchases'   => ['Reports', 'purchases'],
            'reports/profit-loss' => ['Reports', 'profitLoss'],
            'reports/product-sales'=> ['Reports', 'productSales'],
            'reports/salesman'    => ['Reports', 'salesman'],
            'reports/customer-ledger'=> ['Reports', 'customerLedger'],
            'reports/discounts'   => ['Reports', 'discounts'],
            'reports/activity'    => ['Reports', 'activity'],
            'reports/export'      => ['Reports', 'export'],

            // Activity Log
            'activity-log'        => ['ActivityLog', 'index'],
        ];
    }

    public function run(): void
    {
        $url = $this->getUrl();
        $url = rtrim($url, '/');

        if (isset($this->routes[$url])) {
            [$controller, $method] = $this->routes[$url];
            $this->dispatch($controller, $method);
            return;
        }

        $parts = explode('/', $url);
        $controller = ucfirst($parts[0] ?? $this->defaultController);
        $method = $parts[1] ?? $this->defaultMethod;
        $this->dispatch($controller, $method);
    }

    private function dispatch(string $controller, string $method): void
    {
        $controllerFile = dirname(__DIR__) . "/modules/{$this->getModuleFolder($controller)}/{$controller}Controller.php";

        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }

        require_once $controllerFile;
        $className = $this->getModuleNamespace($controller) . "\\{$controller}Controller";

        if (!class_exists($className)) {
            $this->notFound();
            return;
        }

        $instance = new $className();

        if (!method_exists($instance, $method)) {
            $this->notFound();
            return;
        }

        $instance->$method();
    }

    private function getModuleNamespace(string $controller): string
    {
        $folder = $this->getModuleFolder($controller);
        $nsMap = [
            'auth'       => 'Auth',
            'dashboard'  => 'Dashboard',
            'shops'      => 'Shops',
            'users'      => 'Users',
            'settings'   => 'Settings',
            'products'   => 'Products',
            'suppliers'  => 'Suppliers',
            'purchases'  => 'Purchases',
            'inventory'  => 'Inventory',
            'customers'  => 'Customers',
            'sales'      => 'Sales',
            'invoices'   => 'Invoices',
            'barcodes'   => 'Barcodes',
            'reports'    => 'Reports',
        ];
        return 'Modules\\' . ($nsMap[$folder] ?? ucfirst($folder));
    }

    private function getModuleFolder(string $controller): string
    {
        $map = [
            'Auth'        => 'auth',
            'Dashboard'   => 'dashboard',
            'Shops'       => 'shops',
            'Users'       => 'users',
            'Settings'    => 'settings',
            'Categories'  => 'products',
            'Brands'      => 'products',
            'Attributes'  => 'products',
            'Products'    => 'products',
            'Suppliers'   => 'suppliers',
            'Purchases'   => 'purchases',
            'Inventory'   => 'inventory',
            'Customers'   => 'customers',
            'Sales'       => 'sales',
            'Invoices'    => 'invoices',
            'Barcodes'    => 'barcodes',
            'Reports'     => 'reports',
            'ActivityLog' => 'reports',
        ];
        return $map[$controller] ?? strtolower($controller);
    }

    private function getUrl(): string
    {
        $url = $_GET['url'] ?? '';
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return rtrim($url, '/');
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
    }
}
