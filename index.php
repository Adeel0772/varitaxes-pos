<?php

declare(strict_types=1);
// POS SaaS front controller v1.0.1

require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'Modules\\')) {
        return;
    }
    $parts = explode('\\', $class);
    if (count($parts) < 3) {
        return;
    }
    $ns = $parts[1];
    $className = $parts[2];
    $folderMap = [
        'Auth' => 'auth', 'Dashboard' => 'dashboard', 'Shops' => 'shops',
        'Users' => 'users', 'Settings' => 'settings', 'Products' => 'products',
        'Suppliers' => 'suppliers', 'Purchases' => 'purchases', 'Inventory' => 'inventory',
        'Customers' => 'customers', 'Sales' => 'sales', 'Invoices' => 'invoices',
        'Barcodes' => 'barcodes', 'Reports' => 'reports',
    ];
    $folder = $folderMap[$ns] ?? strtolower($ns);
    $file = __DIR__ . "/modules/{$folder}/{$className}.php";
    if (is_file($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/config/constants.php';

$appConfig = require __DIR__ . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

\Core\Auth::init();

try {
    $app = new \Core\App();
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    $appConfig = require __DIR__ . '/config/app.php';
    if ($appConfig['debug'] ?? false) {
        echo '<h1>Application Error</h1><pre>' . htmlspecialchars(
            $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
        ) . '</pre>';
    } else {
        echo '<h1>Server Error</h1><p>The application could not start. Please check database configuration (config/database.local.php).</p>';
    }
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
