<?php

namespace App\Lib;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $dbPath = $config['db_path'];
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            try {
                self::$pdo = new PDO('sqlite:' . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$pdo->exec('PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                http_response_code(500);
                echo 'Database connection failed.';
                error_log('DB connection error: ' . $e->getMessage());
                exit;
            }
        }
        return self::$pdo;
    }
}
