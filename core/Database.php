<?php

namespace core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            if (!is_array($config)) {
                var_dump($config);
                die("❌ CONFIG IS NOT AN ARRAY!");
            }

            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['user'],
                    $config['password']
                );
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die('Database Connection Failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    // Prevent cloning and unserializing to maintain singleton
    private function __clone() {}
    public function __wakeup() {}
}