<?php

declare(strict_types=1);

/**
 * ONE-TIME production repair — run once then delete this file.
 * Visit: https://pos.varitaxes.com/cleanup.php?run=1
 */

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$run = isset($_GET['run']) && $_GET['run'] === '1';

function line(string $msg): void
{
    echo $msg . "\n";
}

function removePath(string $path): bool
{
    if (!file_exists($path)) {
        return false;
    }
    if (is_file($path) || is_link($path)) {
        return @unlink($path);
    }
    $items = scandir($path);
    if ($items === false) {
        return false;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        removePath($path . DIRECTORY_SEPARATOR . $item);
    }
    return @rmdir($path);
}

$indexContent = <<<'PHP'
<?php

declare(strict_types=1);

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

$app = new \Core\App();
$app->run();

PHP;

$htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteBase /

DirectoryIndex index.php

# Explicit routes (work even if a physical auth/ folder exists on server)
RewriteRule ^auth/login/?$ index.php?url=auth/login [L,QSA]
RewriteRule ^auth/logout/?$ index.php?url=auth/logout [L,QSA]
RewriteRule ^dashboard/?$ index.php?url=dashboard [L,QSA]
RewriteRule ^sales/pos/?$ index.php?url=sales/pos [L,QSA]

# Block sensitive directories
RewriteRule ^(config|core|modules|database|uploads)/ - [F,L]

# Front controller — do not skip directories (!-d)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

Options -Indexes +FollowSymLinks
HTACCESS;

line('=== POS cleanup diagnostic ===');

$indexHead = is_file($root . '/index.php')
    ? substr((string) file_get_contents($root . '/index.php'), 0, 120)
    : 'MISSING';
line('index.php head: ' . str_replace("\n", ' ', $indexHead));
line('wrong bootstrap.php: ' . (is_file($root . '/bootstrap.php') ? 'YES (bad)' : 'no'));
line('wrong app/ folder: ' . (is_dir($root . '/app') ? 'YES (bad)' : 'no'));
line('wrong views/errors/: ' . (is_dir($root . '/views/errors') ? 'YES (bad)' : 'no'));
line('root auth/ folder: ' . (is_dir($root . '/auth') ? 'YES (blocks routes)' : 'no'));
line('correct modules/: ' . (is_dir($root . '/modules/auth') ? 'ok' : 'MISSING'));
line('');

if (!$run) {
    line('To repair production, open:');
    line('  ' . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . '/cleanup.php?run=1');
    line('');
    line('Then delete cleanup.php and health.php from the server.');
    exit;
}

line('=== Running repair ===');

$removeList = [
    'bootstrap.php',
    'public_uploads.php',
    'install.php',
    '.ftp-deploy-ignore',
    'app',
    'routes',
    'migrations',
    'storage',
    'auth',
    'views/errors',
    'views/company',
    'views/superadmin',
    'views/expenses',
    'views/layouts/main.php',
    'views/layouts/pos.php',
    'views/layouts/superadmin.php',
];

foreach ($removeList as $rel) {
    $path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (file_exists($path)) {
        $ok = removePath($path);
        line(($ok ? 'removed' : 'FAILED') . ': ' . $rel);
    }
}

$wroteIndex = file_put_contents($root . '/index.php', $indexContent);
line(($wroteIndex !== false ? 'wrote' : 'FAILED') . ': index.php');

$wroteHtaccess = file_put_contents($root . '/.htaccess', $htaccessContent);
line(($wroteHtaccess !== false ? 'wrote' : 'FAILED') . ': .htaccess');

line('');
line('Done. Test: /auth/login');
line('Delete cleanup.php and health.php when finished.');
