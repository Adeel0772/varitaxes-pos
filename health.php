<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$checks = [
    'php_version'     => PHP_VERSION,
    'index_php'       => is_file($root . '/index.php') ? 'ok' : 'MISSING',
    'htaccess'        => is_file($root . '/.htaccess') ? 'ok' : 'MISSING',
    'vendor_autoload' => is_file($root . '/vendor/autoload.php') ? 'ok' : 'MISSING',
    'core_app'        => is_file($root . '/core/App.php') ? 'ok' : 'MISSING',
    'auth_controller' => is_file($root . '/modules/auth/AuthController.php') ? 'ok' : 'MISSING',
    'auth_layout'     => (function () use ($root): string {
        $file = $root . '/views/layouts/auth.php';
        if (!is_file($file)) {
            return 'MISSING';
        }
        $html = (string) file_get_contents($file);
        if (str_contains($html, 'font-awesome')) {
            return 'WRONG (old PakPOS layout)';
        }
        return str_contains($html, 'bootstrap-icons') ? 'ok' : 'unknown';
    })(),
    'auth_lazy_model' => (function () use ($root): string {
        $file = $root . '/modules/auth/AuthController.php';
        if (!is_file($file)) {
            return 'MISSING';
        }
        return str_contains((string) file_get_contents($file), 'private function model()') ? 'ok' : 'OLD';
    })(),
    'mod_rewrite'     => (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules(), true)) ? 'ok' : 'unknown',
    'database_local'  => (function () use ($root): string {
        $paths = [
            $root . '/storage/database.local.php',
            $root . '/config/database.local.php',
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                return 'ok (' . basename($path) . ')';
            }
        }
        return 'MISSING — open /setup-database.php';
    })(),];

foreach ($checks as $key => $value) {
    echo $key . ': ' . $value . "\n";
}

if ($checks['vendor_autoload'] === 'ok') {
    try {
        require_once $root . '/vendor/autoload.php';
        require_once $root . '/config/constants.php';
        echo "bootstrap: ok\n";

        if (is_file($root . '/storage/database.local.php') || is_file($root . '/config/database.local.php')) {
            try {
                \Core\Database::getInstance()->query('SELECT 1');
                echo "database_connect: ok\n";
            } catch (Throwable $e) {
                echo "database_connect: FAIL - " . $e->getMessage() . "\n";
            }
        } else {
            echo "database_connect: skipped (no database.local.php)\n";
        }
    } catch (Throwable $e) {
        echo "bootstrap: FAIL - " . $e->getMessage() . "\n";
    }
}

echo "\nIf database_local is MISSING, open /setup-database.php\n";
echo "If you see this, PHP is working. Delete health.php after fixing the site.\n";
