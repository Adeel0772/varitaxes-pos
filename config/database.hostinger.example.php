<?php
/**
 * COPY THIS FILE on Hostinger to: config/database.local.php
 * hPanel → Databases → use your MySQL database name, username, password.
 *
 * Your database name from phpMyAdmin is likely: u149761999_pos
 */
return [
    'host'     => 'localhost',
    'dbname'   => 'u149761999_pos',
    'username' => 'u149761999_posuser',
    'password' => 'PUT_YOUR_MYSQL_PASSWORD_HERE',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
