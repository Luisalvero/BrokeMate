<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\DB;
use App\Lib\Util;
use PDO;

class NotificationController extends BaseController
{
    public function list(): void
    {
        Auth::requireAuth();
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id=:u ORDER BY created_at DESC LIMIT 100');
        $stmt->execute([':u'=>Auth::user()['id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('notifications/list', compact('notifications'));
    }

    public function markRead(): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $pdo = DB::conn();
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=:u')->execute([':u'=>Auth::user()['id']]);
        header('Content-Type: application/json');
        echo json_encode(['status'=>'ok']);
    }
}
