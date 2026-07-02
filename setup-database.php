<?php

declare(strict_types=1);

/**
 * ONE-TIME Hostinger database setup — delete after use.
 * Visit: https://pos.varitaxes.com/setup-database.php
 */

$root = __DIR__;
$target = $root . '/config/database.local.php';
$errors = [];
$saved = false;
$force = isset($_GET['reconfigure']) && $_GET['reconfigure'] === '1';
$connectionOk = false;
$connectionError = null;

function testDatabaseConnection(string $root): array
{
    try {
        if (class_exists(\Core\Database::class)) {
            \Core\Database::getInstance()->query('SELECT 1');
        } else {
            require_once $root . '/vendor/autoload.php';
            \Core\Database::getInstance()->query('SELECT 1');
        }
        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

if (is_file($target) && !$force) {
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
        $configDir = $root . '/config';
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0755, true);
        }

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

        if (file_put_contents($target, $php) === false) {
            $errors[] = 'Could not write config/database.local.php. In hPanel File Manager, create that file manually with your MySQL credentials.';
        } else {
            require_once $root . '/vendor/autoload.php';
            $result = testDatabaseConnection($root);
            if ($result['ok']) {
                $saved = true;
                $connectionOk = true;
            } else {
                @unlink($target);
                $errors[] = 'Connection test failed: ' . $result['error'];
            }
        }
    }
}

$showForm = $force || !is_file($target) || (!$connectionOk && $_SERVER['REQUEST_METHOD'] !== 'POST');
$existingConfig = is_file($target) ? (require $target) : null;
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
            Database configured successfully. <a href="/auth/login">Go to login</a>
        </div>
    <?php elseif ($connectionOk && !$force): ?>
        <div class="alert alert-success">
            Database connection is working.
            <a href="/auth/login">Go to login</a>
        </div>
        <p class="small text-muted">
            <a href="?reconfigure=1">Reconfigure database</a>
        </p>
    <?php else: ?>
        <?php if (is_file($target) && $connectionError): ?>
            <div class="alert alert-warning">
                Config file exists but connection failed:<br>
                <code><?= htmlspecialchars($connectionError) ?></code>
            </div>
        <?php elseif (!is_file($target)): ?>
            <div class="alert alert-warning">
                <code>config/database.local.php</code> is missing. Login cannot verify users until this is configured.
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <p class="text-muted small">
            In <strong>hPanel → Databases → MySQL Databases</strong>, copy the database name, username, and password.
            Common values for this account:
        </p>
        <ul class="small text-muted">
            <li>Host: <code>localhost</code></li>
            <li>Database: <code>u149761999_pos</code></li>
            <li>Username: often <code>u149761999_pos</code> or <code>u149761999_posuser</code></li>
        </ul>

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
                <input type="password" name="password" class="form-control" required
                       placeholder="<?= is_file($target) ? 'Enter MySQL password' : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100">Save and test connection</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
