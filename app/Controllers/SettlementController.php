<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\DB;
use App\Lib\Util;
use PDO;

class SettlementController extends BaseController
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
        // initialize members
        $rows = $pdo->prepare('SELECT u.id FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id=:g');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $balances[(int)$r['id']] = 0.0; }
        // paid amounts per payer
        $rows = $pdo->prepare('SELECT payer_id, SUM(amount) total FROM expenses WHERE group_id=:g GROUP BY payer_id');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $balances[(int)$r['payer_id']] = ($balances[(int)$r['payer_id']] ?? 0) + (float)$r['total']; }
        // owed per allocation
        $rows = $pdo->prepare('SELECT user_id, SUM(owed_amount) owed FROM expense_allocations ea JOIN expenses e ON e.id=ea.expense_id WHERE e.group_id=:g GROUP BY user_id');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) { $balances[(int)$r['user_id']] = ($balances[(int)$r['user_id']] ?? 0) - (float)$r['owed']; }
        // settlements
        $rows = $pdo->prepare('SELECT from_user_id, to_user_id, SUM(amount) amt FROM settlements WHERE group_id=:g GROUP BY from_user_id,to_user_id');
        $rows->execute([':g'=>$gid]);
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $balances[(int)$r['from_user_id']] -= (float)$r['amt'];
            $balances[(int)$r['to_user_id']] += (float)$r['amt'];
        }
        return $balances;
    }

    public function list(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $pdo = DB::conn();
        $page = max(1,(int)($_GET['page'] ?? 1)); $limit=10; $offset=($page-1)*$limit;
        $stmt = $pdo->prepare("SELECT s.*, fu.name from_name, tu.name to_name FROM settlements s JOIN users fu ON fu.id=s.from_user_id JOIN users tu ON tu.id=s.to_user_id WHERE s.group_id=:g ORDER BY settled_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute([':g'=>$gid]);
        $settlements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $balances = $this->computeBalances($gid);
        $this->render('settlements/list', compact('group','settlements','balances','page'));
    }

    public function create(int $gid): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $group = $this->requireMember($gid);
        $from = (int)$_POST['from_user_id'];
        $to = (int)$_POST['to_user_id'];
        $amount = round((float)$_POST['amount'],2);
        if ($from === $to || $amount <= 0) { Util::flash('error','Invalid settlement'); Util::redirect('/groups/'.$gid.'/settlements'); }
        // validate against current debt
        $balances = $this->computeBalances($gid);
        // debt from -> to: if from owes to, then from balance is negative and to is positive
        // max allowed is min(-balance[from], balance[to])
        $max = min(max(0, -($balances[$from] ?? 0)), max(0, ($balances[$to] ?? 0)));
        if ($amount - $max > 0.009) {
            Util::flash('error', 'Amount exceeds current debt (max ' . Util::money($max) . ').');
            Util::redirect('/groups/'.$gid.'/settlements');
        }
        $pdo = DB::conn();
        $pdo->prepare('INSERT INTO settlements(group_id,from_user_id,to_user_id,amount,method,note,settled_at,created_at) VALUES (:g,:f,:t,:a,:m,:n,:d,:now)')
            ->execute([
                ':g'=>$gid,
                ':f'=>$from,
                ':t'=>$to,
                ':a'=>$amount,
                ':m'=>$_POST['method'] ?? 'other',
                ':n'=>$_POST['note'] ?? '',
                ':d'=>$_POST['settled_at'] ?? date('Y-m-d'),
                ':now'=>Util::now(),
            ]);
        try {
            $sid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO activity_log(user_id,group_id,action,entity,entity_id,created_at) VALUES (:u,:g,\'created\',\'settlement\',:id,:now)')
                ->execute([':u'=>Auth::user()['id'], ':g'=>$gid, ':id'=>$sid, ':now'=>Util::now()]);
        } catch (\Throwable $e) {}
        // notify recipient
        $pdo->prepare('INSERT INTO notifications(user_id,type,payload_json,is_read,created_at) VALUES (:uid,\'settlement_received\',:payload,0,:now)')
            ->execute([':uid'=>$to, ':payload'=>json_encode(['group_id'=>$gid,'from'=>$from,'amount'=>$amount]), ':now'=>Util::now()]);
        Util::flash('success','Settlement recorded.');
        Util::redirect('/groups/'.$gid.'/settlements');
    }

    public function simplifyPreview(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $balances = $this->computeBalances($gid);
        $transfers = Util::simplifyDebts($balances);
        $this->render('ledger/simplify', compact('group','balances','transfers'));
    }

    public function simplifyConfirm(int $gid): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $balances = $this->computeBalances($gid);
        $transfers = Util::simplifyDebts($balances);
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO settlements(group_id,from_user_id,to_user_id,amount,method,note,settled_at,created_at) VALUES (:g,:f,:t,:a,\'other\',\'simplified\',:d,:now)');
            foreach ($transfers as $t) {
                $ins->execute([':g'=>$gid, ':f'=>$t['from'], ':t'=>$t['to'], ':a'=>$t['amount'], ':d'=>date('Y-m-d'), ':now'=>Util::now()]);
            }
            $pdo->commit();
            Util::flash('success','Simplified settlements recorded.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Util::flash('error','Failed to record simplified settlements.');
        }
        Util::redirect('/groups/'.$gid.'/ledger');
    }
}
