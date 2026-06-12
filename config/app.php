<?php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/');

return [
    'base_url'    => $protocol . '://' . $host . $basePath,
    'base_path'   => $basePath,
    'timezone'    => 'Asia/Karachi',
    'debug'       => (getenv('APP_ENV') ?: 'local') !== 'production',
    'session_name'=> 'pos_saas_session',
];
