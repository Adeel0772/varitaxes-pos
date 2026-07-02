<?php

declare(strict_types=1);

/**
 * CI / deploy helper — writes storage/database.local.php from environment variables.
 * Required env: DB_PASSWORD. Optional: DB_HOST, DB_NAME, DB_USER.
 */

$root = dirname(__DIR__);
$target = $root . '/storage/database.local.php';
$password = getenv('DB_PASSWORD') ?: '';

if ($password === '') {
    fwrite(STDOUT, "DB_PASSWORD not set — skipping database.local.php generation.\n");
    exit(0);
}

$dir = dirname($target);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$config = [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'dbname'   => getenv('DB_NAME') ?: 'u149761999_pos',
    'username' => getenv('DB_USER') ?: 'u149761999_pos',
    'password' => $password,
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];

$php = "<?php\n\nreturn " . var_export($config, true) . ";\n";
file_put_contents($target, $php);
fwrite(STDOUT, "Wrote storage/database.local.php for deploy.\n");
