<?php
/**
 * Copy to database.local.php on the server (not in git) OR edit database.php directly.
 * Hostinger: use MySQL hostname from hPanel → Databases.
 */
return [
    'host'     => 'localhost',
    'dbname'   => 'u149761999_pos_saas',
    'username' => 'u149761999_posuser',
    'password' => 'YOUR_DB_PASSWORD',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
