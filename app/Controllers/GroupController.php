<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\DB;
use App\Lib\Util;
use App\Lib\Validator;
use PDO;

class GroupController extends BaseController
{
    public function createForm(): void
    {
        Auth::requireAuth();
        $this->render('groups/create');
    }

    public function create(): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $v = new Validator();
        $v->required(['name'], $_POST);
        if (!$v->ok()) {
            $this->render('groups/create', ['errors' => $v->errors, 'old' => $_POST]);
            return;
        }
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $invite = Util::generateInviteCode();
            $stmt = $pdo->prepare('INSERT INTO groups(name, description, currency, owner_id, invite_code, is_public_readonly, created_at) VALUES (:n,:d,:c,:o,:code,0,:now)');
            $stmt->execute([
                ':n' => $_POST['name'],
                ':d' => $_POST['description'] ?? '',
                ':c' => $_POST['currency'] ?? Auth::user()['currency'],
                ':o' => Auth::user()['id'],
                ':code' => $invite,
                ':now' => Util::now(),
            ]);
            $gid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO group_members(group_id,user_id,role,created_at) VALUES(:g,:u,\'owner\',:now)')->execute([
                ':g' => $gid,
                ':u' => Auth::user()['id'],
                ':now' => Util::now(),
            ]);
            $pdo->commit();
            Util::flash('success', 'Group created.');
            Util::redirect('/groups/' . $gid);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function joinForm(): void
    {
        Auth::requireAuth();
        $this->render('groups/join');
    }

    public function join(): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Util::rateLimit('join_' . $ip, 20, 3600)) {
            Util::flash('error', 'Rate limit exceeded.');
            Util::redirect('/groups/join');
        }
        $code = trim($_POST['code'] ?? '');
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT * FROM groups WHERE invite_code=:code');
        $stmt->execute([':code' => $code]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            Util::flash('error', 'Invalid code');
            Util::redirect('/groups/join');
        }
        try {
            $pdo->prepare('INSERT OR IGNORE INTO group_members(group_id,user_id,role,created_at) VALUES(:g,:u,\'member\',:now)')->execute([
                ':g' => $group['id'], ':u' => Auth::user()['id'], ':now' => Util::now()
            ]);
            $pdo->prepare('INSERT INTO notifications(user_id,type,payload_json,is_read,created_at) VALUES (:uid,\'group_join\',:payload,0,:now)')
                ->execute([
                    ':uid' => $group['owner_id'],
                    ':payload' => json_encode(['group_id' => $group['id'], 'user_id' => Auth::user()['id']]),
                    ':now' => Util::now(),
                ]);
        } catch (\Throwable $e) {}
        Util::redirect('/groups/' . $group['id']);
    }

    private function requireMember(int $gid): array
    {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT g.*, gm.role FROM groups g JOIN group_members gm ON gm.group_id=g.id WHERE g.id=:g AND gm.user_id=:u');
        $stmt->execute([':g' => $gid, ':u' => Auth::user()['id']]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        return $group;
    }

    public function view(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $pdo = DB::conn();
        $members = $pdo->prepare('SELECT u.id,u.name,u.email FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id=:g');
        $members->execute([':g' => $gid]);
        $members = $members->fetchAll(PDO::FETCH_ASSOC);
        $recentExpenses = $pdo->prepare('SELECT e.*, u.name payer_name FROM expenses e JOIN users u ON u.id=e.payer_id WHERE e.group_id=:g ORDER BY created_at DESC LIMIT 10');
        $recentExpenses->execute([':g' => $gid]);
        $recentExpenses = $recentExpenses->fetchAll(PDO::FETCH_ASSOC);
        $recentSettlements = $pdo->prepare('SELECT s.*, fu.name from_name, tu.name to_name FROM settlements s JOIN users fu ON fu.id=s.from_user_id JOIN users tu ON tu.id=s.to_user_id WHERE s.group_id=:g ORDER BY created_at DESC LIMIT 10');
        $recentSettlements->execute([':g' => $gid]);
        $recentSettlements = $recentSettlements->fetchAll(PDO::FETCH_ASSOC);
        $this->render('groups/view', compact('group','members','recentExpenses','recentSettlements'));
    }

    public function invite(int $gid): void
    {
        Auth::requireAuth();
        $group = $this->requireMember($gid);
        $this->render('groups/invite', ['group' => $group]);
    }
}
