<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\DB;
use PDO;

class DashboardController extends BaseController
{
    public function index(): void
    {
        Auth::requireAuth();
        $pdo = DB::conn();
        $uid = (int)Auth::user()['id'];
        $groups = $pdo->prepare('SELECT g.*, gm.role FROM groups g JOIN group_members gm ON gm.group_id=g.id WHERE gm.user_id=:uid ORDER BY g.created_at DESC');
        $groups->execute([':uid' => $uid]);
        $groups = $groups->fetchAll(PDO::FETCH_ASSOC);
        $activity = $pdo->prepare('SELECT * FROM activity_log WHERE user_id=:uid OR group_id IN (SELECT group_id FROM group_members WHERE user_id=:uid) ORDER BY created_at DESC LIMIT 20');
        $activity->execute([':uid' => $uid]);
        $activity = $activity->fetchAll(PDO::FETCH_ASSOC);
        $this->render('dashboard/index', [
            'groups' => $groups,
            'activity' => $activity,
        ]);
    }
}
