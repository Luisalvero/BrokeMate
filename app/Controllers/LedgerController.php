<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\DB;
use App\Lib\Util;
use PDO;

class LedgerController extends BaseController
{
    private function requireMember(int $gid): array
    {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT g.*, gm.role FROM groups g JOIN group_members gm ON gm.group_id=g.id WHERE g.id=:g AND gm.user_id=:u');
        $stmt->execute([':g' => $gid, ':u' => Auth::user()['id']]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) { http_response_code(403); echo 'Forbidden'; exit; }
        return $group;
    }

    private function computeBalances(int $gid): array
    {
        $pdo = DB::conn();
        $balances = [];
        $rows = $pdo->prepare('SELECT u.id FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id=:g');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $balances[(int)$r['id']] = 0.0; }
        $rows = $pdo->prepare('SELECT payer_id, SUM(amount) total FROM expenses WHERE group_id=:g GROUP BY payer_id');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $balances[(int)$r['payer_id']] = ($balances[(int)$r['payer_id']] ?? 0) + (float)$r['total']; }
        $rows = $pdo->prepare('SELECT user_id, SUM(owed_amount) owed FROM expense_allocations ea JOIN expenses e ON e.id=ea.expense_id WHERE e.group_id=:g GROUP BY user_id');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $balances[(int)$r['user_id']] = ($balances[(int)$r['user_id']] ?? 0) - (float)$r['owed']; }
        $rows = $pdo->prepare('SELECT from_user_id, to_user_id, SUM(amount) amt FROM settlements WHERE group_id=:g GROUP BY from_user_id,to_user_id');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $balances[(int)$r['from_user_id']] -= (float)$r['amt'];
            $balances[(int)$r['to_user_id']] += (float)$r['amt'];
        }
        return $balances;
    }

    public function view(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $pdo = DB::conn();
        $members = $pdo->prepare('SELECT u.id,u.name FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id=:g');
        $members->execute([':g'=>$gid]);
        $members = $members->fetchAll(PDO::FETCH_ASSOC);
        $balances = $this->computeBalances($gid);
        // ledger matrix who owes whom
        $transfers = Util::simplifyDebts($balances);
        $this->render('ledger/view', compact('group','members','balances','transfers'));
    }
}
