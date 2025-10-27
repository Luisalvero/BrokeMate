<?php

namespace App\Controllers;

use App\Lib\DB;
use PDO;

class PublicController extends BaseController
{
    public function readOnly(string $token): void
    {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT g.* FROM public_links pl JOIN groups g ON g.id=pl.group_id WHERE pl.read_token=:t AND g.is_public_readonly=1');
        $stmt->execute([':t'=>$token]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) { http_response_code(404); echo 'Not found'; return; }
        $members = $pdo->prepare('SELECT u.id, SUBSTR(u.name,1,1) as initial FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id=:g');
        $members->execute([':g'=>$group['id']]);
        $members = $members->fetchAll(PDO::FETCH_ASSOC);
        $expenses = $pdo->prepare('SELECT e.title, e.amount, e.category, e.expense_date FROM expenses e WHERE e.group_id=:g ORDER BY e.expense_date DESC LIMIT 50');
        $expenses->execute([':g'=>$group['id']]);
        $expenses = $expenses->fetchAll(PDO::FETCH_ASSOC);
        $this->render('public/read', compact('group','members','expenses'));
    }
}
