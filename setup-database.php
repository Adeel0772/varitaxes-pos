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
        $config = [
            'host'     => $host,
            'dbname'   => $dbname,
            'username' => $username,
            'password' => $password,
            'charset'  => 'utf8mb4',
            'options'  => [
                'PDO::ATTR_ERRMODE'            => 'PDO::ERRMODE_EXCEPTION',
                'PDO::ATTR_DEFAULT_FETCH_MODE' => 'PDO::FETCH_ASSOC',
                'PDO::ATTR_EMULATE_PREPARES'   => false,
            ],
        ];

        $php = "<?php\n\nreturn " . var_export([
            'host'     => $config['host'],
            'dbname'   => $config['dbname'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset'  => $config['charset'],
            'options'  => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
        ], true) . ";\n";

        if (file_put_contents($target, $php) === false) {
            $errors[] = 'Could not write config/database.local.php — check folder permissions.';
        } else {
            try {
                require_once $root . '/vendor/autoload.php';
                \Core\Database::getInstance()->query('SELECT 1');
                $saved = true;
            } catch (Throwable $e) {
                @unlink($target);
                $errors[] = 'Connection test failed: ' . $e->getMessage();
            }
        }
    }
}

$alreadyConfigured = is_file($target);
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
<div class="container py-5" style="max-width:520px;">
    <h1 class="h4 mb-4">Hostinger database setup</h1>

    <?php if ($saved): ?>
        <div class="alert alert-success">
            Database configured successfully. Test <a href="/auth/login">/auth/login</a>,
            then delete <code>setup-database.php</code>, <code>health.php</code>, and <code>cleanup.php</code>.
        </div>
    <?php elseif ($alreadyConfigured): ?>
        <div class="alert alert-info">
            <code>config/database.local.php</code> already exists.
            Delete this file if you need to reconfigure.
        </div>
    <?php else: ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <p class="text-muted small">
            Use credentials from hPanel → Databases. Your database name is likely
            <strong>u149761999_pos</strong>.
        </p>

        <form method="POST" class="card card-body shadow-sm">
            <div class="mb-3">
                <label class="form-label">Host</label>
                <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($_POST['host'] ?? 'localhost') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Database name</label>
                <input type="text" name="dbname" class="form-control" value="<?= htmlspecialchars($_POST['dbname'] ?? 'u149761999_pos') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? 'u149761999_pos') ?>" required>
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
