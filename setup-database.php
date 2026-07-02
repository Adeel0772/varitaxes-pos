<?php

declare(strict_types=1);

/**
 * ONE-TIME Hostinger database setup — delete after use.
 * Visit: https://pos.varitaxes.com/setup-database.php
 *
 * Saves to storage/database.local.php so future GitHub deploys do not wipe credentials.
 */

$root = __DIR__;
$targets = [
    $root . '/storage/database.local.php',
    $root . '/config/database.local.php',
];
$errors = [];
$saved = false;
$force = isset($_GET['reconfigure']) && $_GET['reconfigure'] === '1';
$connectionOk = false;
$connectionError = null;
$configPath = null;

function existingConfigPath(array $targets): ?string
{
    foreach ($targets as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

function writeDatabaseConfig(array $targets, string $php): array
{
    $results = [];
    foreach ($targets as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $results[$path] = file_put_contents($path, $php) !== false;
    }
    return $results;
}

function testDatabaseConnection(string $root): array
{
    try {
        if (!class_exists(\Core\Database::class)) {
            require_once $root . '/vendor/autoload.php';
        }
        \Core\Database::getInstance()->query('SELECT 1');
        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

$configPath = existingConfigPath($targets);

if ($configPath !== null && !$force) {
    require_once $root . '/vendor/autoload.php';
    $result = testDatabaseConnection($root);
    $connectionOk = $result['ok'];
    $connectionError = $result['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $dbname = trim($_POST['dbname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($dbname === '') {
        $errors[] = 'Database name is required.';
    }
    if ($username === '') {
        $errors[] = 'Database username is required.';
    }

    if (!$errors) {
        $php = "<?php\n\nreturn " . var_export([
            'host'     => $host,
            'dbname'   => $dbname,
            'username' => $username,
            'password' => $password,
            'charset'  => 'utf8mb4',
            'options'  => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
        ], true) . ";\n";

        $writeResults = writeDatabaseConfig($targets, $php);
        if (!$writeResults[$targets[0]]) {
            $errors[] = 'Could not write storage/database.local.php — check folder permissions in hPanel.';
        } else {
            require_once $root . '/vendor/autoload.php';
            $result = testDatabaseConnection($root);
            if ($result['ok']) {
                $saved = true;
                $connectionOk = true;
                $configPath = $targets[0];
            } else {
                foreach ($targets as $path) {
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                $errors[] = 'Connection test failed: ' . $result['error'];
            }
        }
    }
}

$existingConfig = $configPath !== null ? (require $configPath) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - POS SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:560px;">
    <h1 class="h4 mb-4">Hostinger database setup</h1>

    <?php if ($saved): ?>
        <div class="alert alert-success">
            Database saved to <code>storage/database.local.php</code> (survives GitHub deploys).
            <a href="/auth/login">Go to login</a>
        </div>
    <?php elseif ($connectionOk && !$force): ?>
        <div class="alert alert-success">
            Database connection is working via <code><?= htmlspecialchars(str_replace($root, '', $configPath ?? '')) ?></code>.
            <a href="/auth/login">Go to login</a>
        </div>
        <p class="small text-muted"><a href="?reconfigure=1">Reconfigure database</a></p>
    <?php else: ?>
        <?php if ($configPath && $connectionError): ?>
            <div class="alert alert-warning">
                Config exists but connection failed:<br>
                <code><?= htmlspecialchars($connectionError) ?></code>
            </div>
        <?php elseif ($configPath === null): ?>
            <div class="alert alert-warning">
                No database config found. Recent deploys reset <code>config/database.php</code> to local dev defaults.
                Enter your Hostinger MySQL credentials below — they will be stored safely in
                <code>storage/database.local.php</code>.
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" class="card card-body shadow-sm">
            <div class="mb-3">
                <label class="form-label">Host</label>
                <input type="text" name="host" class="form-control"
                       value="<?= htmlspecialchars($_POST['host'] ?? $existingConfig['host'] ?? 'localhost') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Database name</label>
                <input type="text" name="dbname" class="form-control"
                       value="<?= htmlspecialchars($_POST['dbname'] ?? $existingConfig['dbname'] ?? 'u149761999_pos') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($_POST['username'] ?? $existingConfig['username'] ?? 'u149761999_pos') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Save and test connection</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
