<?php

namespace App\Lib;

class CSRF
{
    public static function token(): string
    {
        $config = require __DIR__ . '/../../config/config.php';
        $key = $config['csrf_key'];
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$key];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . Util::e(self::token()) . '">';
    }

    public static function validate(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $config = require __DIR__ . '/../../config/config.php';
            $key = $config['csrf_key'];
            $sent = $_POST['_csrf'] ?? '';
            $valid = isset($_SESSION[$key]) && hash_equals($_SESSION[$key], $sent);
            if (!$valid) {
                http_response_code(400);
                echo 'Invalid CSRF token';
                exit;
            }
        }
    }
}
