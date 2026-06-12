<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $configFile = dirname(__DIR__) . '/config/database.php';
            $localFile = dirname(__DIR__) . '/config/database.local.php';
            if (is_file($localFile)) {
                $configFile = $localFile;
            }
            $config = require $configFile;
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['dbname'],
                $config['charset']
            );
            $options = $config['options'] ?? [];
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci';

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                $appConfig = require dirname(__DIR__) . '/config/app.php';
                if ($appConfig['debug'] ?? false) {
                    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
                }
                die('Database connection failed. Please contact support.');
            }
        }
        return self::$instance;
    }
}
