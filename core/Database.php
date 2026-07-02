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
            $configFile = self::resolveConfigFile();
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
                $message = 'Database connection failed: ' . $e->getMessage();
                $onProduction = str_contains($_SERVER['HTTP_HOST'] ?? '', 'varitaxes.com');
                if (($appConfig['debug'] ?? false) || $onProduction) {
                    if ($onProduction && !self::hasLocalConfig()) {
                        $message .= ' — Open /setup-database.php (credentials are saved in storage/database.local.php)';
                    }
                    throw new \RuntimeException($message, 0, $e);
                }
                throw new \RuntimeException('Database connection failed. Please contact support.', 0, $e);
            }
        }
        return self::$instance;
    }

    public static function configPaths(): array
    {
        $root = dirname(__DIR__);
        return [
            $root . '/storage/database.local.php',
            $root . '/config/database.local.php',
        ];
    }

    public static function hasLocalConfig(): bool
    {
        foreach (self::configPaths() as $path) {
            if (is_file($path)) {
                return true;
            }
        }
        return false;
    }

    private static function resolveConfigFile(): string
    {
        foreach (self::configPaths() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        if (str_contains($_SERVER['HTTP_HOST'] ?? '', 'varitaxes.com')) {
            throw new \RuntimeException(
                'Database not configured on production. Open /setup-database.php to save your Hostinger MySQL credentials.'
            );
        }

        return dirname(__DIR__) . '/config/database.php';
    }
}
