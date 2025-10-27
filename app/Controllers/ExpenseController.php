<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\DB;
use App\Lib\Util;
use App\Lib\Validator;
use PDO;

class ExpenseController extends BaseController
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

    public function list(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $pdo = DB::conn();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10; $offset = ($page-1)*$limit;
        $q = trim($_GET['q'] ?? '');
        $where = 'WHERE e.group_id=:g';
        $params = [':g'=>$gid];
        if ($q !== '') {
            $where .= ' AND (e.title LIKE :q OR e.category LIKE :q OR e.notes LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $stmt = $pdo->prepare("SELECT e.*, u.name payer_name FROM expenses e JOIN users u ON u.id=e.payer_id $where ORDER BY e.expense_date DESC, e.created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('expenses/list', compact('group','expenses','page','q'));
    }

    public function createForm(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $pdo = DB::conn();
        $members = $pdo->prepare('SELECT u.id,u.name FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id=:g');
        $members->execute([':g'=>$gid]);
        $members = $members->fetchAll(PDO::FETCH_ASSOC);
        $this->render('expenses/create', compact('group','members'));
    }

    public function create(int $gid): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $group = $this->requireMember($gid);
        $pdo = DB::conn();
        $v = new Validator();
        $v->required(['title','amount','payer_id','expense_date','split_method'], $_POST);
        $v->decimal('amount', $_POST['amount'] ?? '');
        if (!$v->ok()) {
            Util::flash('error', 'Please fix errors.');
            $this->createForm($gid); // keep simple
            return;
        }
        $participants = array_map('intval', $_POST['participants'] ?? []);
        if (empty($participants)) {
            Util::flash('error', 'Select at least one participant.');
            $this->createForm($gid);
            return;
        }
        $amount = round((float)$_POST['amount'], 2);
        $split = $_POST['split_method'];
        $allocations = [];
        switch ($split) {
            case 'even':
                $n = count($participants);
                $each = floor(($amount / $n) * 100) / 100; // truncate to 2 decimals
                $sum = 0;
                foreach ($participants as $i=>$uid) {
                    $alloc = ($i === $n-1) ? round($amount - $sum, 2) : $each;
                    $sum += $alloc;
                    $allocations[] = ['user_id'=>$uid,'share_type'=>'even','share_value'=>1,'owed'=>$alloc];
                }
                break;
            case 'shares':
                $shares = $_POST['shares'] ?? [];
                $totalShares = 0;
                foreach ($participants as $uid) { $totalShares += max(0, (int)($shares[$uid] ?? 0)); }
                if ($totalShares <= 0) { Util::flash('error','Invalid shares'); $this->createForm($gid); return; }
                $sum = 0; $i=0; $n=count($participants);
                foreach ($participants as $uid) {
                    $i++;
                    $portion = (int)($shares[$uid] ?? 0);
                    $owed = $i === $n ? round($amount - $sum, 2) : round($amount * $portion / $totalShares, 2);
                    $sum += $owed;
                    $allocations[] = ['user_id'=>$uid,'share_type'=>'shares','share_value'=>$portion,'owed'=>$owed];
                }
                break;
            case 'exact':
                $exact = $_POST['exact'] ?? [];
                $sum = 0; $i=0; $n=count($participants);
                foreach ($participants as $uid) {
                    $i++;
                    $val = round((float)($exact[$uid] ?? 0), 2);
                    $sum += $val;
                    $allocations[] = ['user_id'=>$uid,'share_type'=>'exact','share_value'=>$val,'owed'=>$val];
                }
                if (round($sum, 2) !== round($amount, 2)) { Util::flash('error','Exact amounts must sum to total'); $this->createForm($gid); return; }
                break;
            case 'percent':
                $perc = $_POST['percent'] ?? [];
                $total=0; foreach ($participants as $uid) { $total += (float)($perc[$uid] ?? 0); }
                if (round($total,2) !== 100.00) { Util::flash('error','Percentages must sum to 100'); $this->createForm($gid); return; }
                $sum = 0; $i=0; $n=count($participants);
                foreach ($participants as $uid) {
                    $i++;
                    $p = (float)($perc[$uid] ?? 0);
                    $owed = $i === $n ? round($amount - $sum, 2) : round($amount * $p / 100.0, 2);
                    $sum += $owed;
                    $allocations[] = ['user_id'=>$uid,'share_type'=>'percent','share_value'=>$p,'owed'=>$owed];
                }
                break;
            default:
                Util::flash('error', 'Invalid split method');
                $this->createForm($gid);
                return;
        }
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO expenses(group_id,payer_id,title,amount,category,notes,expense_date,created_at,updated_at) VALUES (:g,:p,:t,:a,:c,:n,:d,:now,:now)');
            $stmt->execute([
                ':g'=>$gid,
                ':p'=>(int)$_POST['payer_id'],
                ':t'=>$_POST['title'],
                ':a'=>$amount,
                ':c'=>$_POST['category'] ?? 'Other',
                ':n'=>$_POST['notes'] ?? '',
                ':d'=>$_POST['expense_date'],
                ':now'=>Util::now(),
            ]);
            $eid = (int)$pdo->lastInsertId();
            $insP = $pdo->prepare('INSERT INTO expense_participants(expense_id,user_id) VALUES (:e,:u)');
            foreach ($participants as $uid) { $insP->execute([':e'=>$eid, ':u'=>$uid]); }
            $insA = $pdo->prepare('INSERT INTO expense_allocations(expense_id,user_id,share_type,share_value,owed_amount) VALUES (:e,:u,:st,:sv,:o)');
            foreach ($allocations as $al) {
                $insA->execute([':e'=>$eid, ':u'=>$al['user_id'], ':st'=>$al['share_type'], ':sv'=>$al['share_value'], ':o'=>$al['owed']]);
                if ($al['user_id'] != (int)$_POST['payer_id']) {
                    // notify participant
                    $pdo->prepare('INSERT INTO notifications(user_id,type,payload_json,is_read,created_at) VALUES (:uid,\'expense_included\',:payload,0,:now)')
                        ->execute([':uid'=>$al['user_id'], ':payload'=>json_encode(['group_id'=>$gid,'expense_id'=>$eid]), ':now'=>Util::now()]);
                }
            }
            $pdo->commit();
            // activity log
            try {
                $pdo->prepare('INSERT INTO activity_log(user_id,group_id,action,entity,entity_id,created_at) VALUES (:u,:g,\'created\',\'expense\',:e,:now)')
                    ->execute([':u'=>Auth::user()['id'], ':g'=>$gid, ':e'=>$eid, ':now'=>Util::now()]);
            } catch (\Throwable $e) {}
            Util::flash('success','Expense added.');
            Util::redirect('/groups/' . $gid . '/expenses');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
