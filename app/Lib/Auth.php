<?php

namespace App\Lib;

use App\Lib\DB;
use PDO;

class Auth
{
    public static function startSession(): void
    {
        $config = require __DIR__ . '/../../config/config.php';
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($config['session_name']);
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => false, // set true behind HTTPS
            ]);
        }
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Util::flash('error', 'Please log in');
            Util::redirect('/login');
        }
    }

    public static function login(string $email, string $password): bool
    {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => mb_strtolower($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function register(array $data): array
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users(name, email, password_hash, is_temporary, currency, created_at) VALUES (:name,:email,:ph,0,:currency,:now)');
            $stmt->execute([
                ':name' => $data['name'],
                ':email' => mb_strtolower($data['email']),
                ':ph' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':currency' => $data['currency'] ?? 'USD',
                ':now' => Util::now(),
            ]);
            $id = (int)$pdo->lastInsertId();
            $user = $pdo->query('SELECT id, name, email, is_temporary, currency, created_at, avatar FROM users WHERE id=' . (int)$id)->fetch(PDO::FETCH_ASSOC);
            $pdo->commit();
            return $user;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function createGuest(string $name): array
    {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash,is_temporary,currency,created_at) VALUES (:name,:email,:ph,1,:currency,:now)');
        $email = strtolower(preg_replace('/\s+/', '', $name)) . '+' . bin2hex(random_bytes(3)) . '@guest.local';
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':ph' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            ':currency' => 'USD',
            ':now' => Util::now(),
        ]);
        $id = (int)DB::conn()->lastInsertId();
        $user = DB::conn()->query('SELECT id, name, email, is_temporary, currency, created_at FROM users WHERE id=' . $id)->fetch(PDO::FETCH_ASSOC);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        return $user;
    }
}
