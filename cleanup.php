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
// POS SaaS front controller v1.0.2

if (str_contains($_SERVER['HTTP_HOST'] ?? '', 'varitaxes.com')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

ob_start();

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
require_once __DIR__ . '/core/helpers_global.php';

$appConfig = require __DIR__ . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

\Core\Auth::init();

try {
    $app = new \Core\App();
    $app->run();
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    $appConfig = require __DIR__ . '/config/app.php';
    $showDetails = ($appConfig['debug'] ?? false)
        || str_contains($_SERVER['HTTP_HOST'] ?? '', 'varitaxes.com');
    if ($showDetails) {
        echo '<h1>Application Error</h1><pre>' . htmlspecialchars(
            $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
        ) . '</pre>';
    } else {
        echo '<h1>Server Error</h1><p>The application could not start. Please check database configuration (config/database.local.php).</p>';
    }
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

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

$authLayoutContent = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Login - ' . APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= \Core\Auth::asset('css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100">
        <div class="auth-card card shadow-lg border-0" style="width:100%;max-width:420px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-shop display-4 text-primary"></i>
                    <h4 class="mt-2"><?= htmlspecialchars(APP_NAME) ?></h4>
                </div>
                <?php if (!empty($error = \Core\Auth::flash('error'))): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success = \Core\Auth::flash('success'))): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

$authLoginContent = <<<'HTML'
<form method="POST" action="<?= \Core\Auth::baseUrl('auth/login') ?>">
    <?= \Core\Auth::csrfField() ?>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
    <div class="text-center mt-3">
        <a href="<?= \Core\Auth::baseUrl('register') ?>">Register your shop</a>
    </div>
</form>
HTML;

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

$authLayout = is_file($root . '/views/layouts/auth.php')
    ? (string) file_get_contents($root . '/views/layouts/auth.php')
    : '';
if ($authLayout === '') {
    line('auth layout: MISSING');
} elseif (str_contains($authLayout, 'font-awesome')) {
    line('auth layout: WRONG (old PakPOS layout — redeploy views/layouts/auth.php)');
} elseif (str_contains($authLayout, 'bootstrap-icons')) {
    line('auth layout: ok (POS SaaS)');
} else {
    line('auth layout: unknown variant');
}

$authCtrl = is_file($root . '/modules/auth/AuthController.php')
    ? (string) file_get_contents($root . '/modules/auth/AuthController.php')
    : '';
line('auth lazy model: ' . (str_contains($authCtrl, 'private function model()') ? 'ok' : 'OLD (needs redeploy)'));

$loginView = is_file($root . '/views/auth/login.php')
    ? (string) file_get_contents($root . '/views/auth/login.php')
    : '';
if ($loginView === '') {
    line('login view: MISSING');
} elseif (str_contains($loginView, 'assetUrl(')) {
    line('login view: WRONG (old PakPOS — run repair)');
} else {
    line('login view: ok (POS SaaS)');
}

line('database.local.php: ' . (is_file($root . '/config/database.local.php') ? 'ok' : 'MISSING'));
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

foreach (['views/auth', 'views/layouts', 'core'] as $dir) {
    $path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

$wroteLayout = file_put_contents($root . '/views/layouts/auth.php', $authLayoutContent);
line(($wroteLayout !== false ? 'wrote' : 'FAILED') . ': views/layouts/auth.php');

$wroteLogin = file_put_contents($root . '/views/auth/login.php', $authLoginContent);
line(($wroteLogin !== false ? 'wrote' : 'FAILED') . ': views/auth/login.php');

$helpersGlobalContent = <<<'PHP'
<?php

declare(strict_types=1);

if (!function_exists('assetUrl')) {
    function assetUrl(string $path = ''): string
    {
        return \Core\Auth::asset($path);
    }
}

if (!function_exists('baseUrl')) {
    function baseUrl(string $path = ''): string
    {
        return \Core\Auth::baseUrl($path);
    }
}

PHP;

$wroteHelpers = file_put_contents($root . '/core/helpers_global.php', $helpersGlobalContent);
line(($wroteHelpers !== false ? 'wrote' : 'FAILED') . ': core/helpers_global.php');

$restoreFiles = [
    'modules/auth/AuthController.php',
];
foreach ($restoreFiles as $rel) {
    $src = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($src)) {
        line('MISSING on server (upload from GitHub): ' . $rel);
        continue;
    }
    line('ok: ' . $rel . ' (' . filesize($src) . ' bytes)');
}

line('');
line('If database.local.php is missing, open: /setup-database.php');
line('Done. Test: /auth/login');
line('Delete cleanup.php and health.php when finished.');
