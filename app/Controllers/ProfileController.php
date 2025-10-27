<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\DB;
use App\Lib\Util;
use App\Lib\Validator;
use PDO;

class ProfileController extends BaseController
{
    public function settings(): void
    {
        Auth::requireAuth();
        $this->render('settings/profile', ['user' => Auth::user()]);
    }

    public function update(): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $v = new Validator();
        $v->required(['name','email'], $_POST);
        $v->email('email', $_POST['email'] ?? '');
        $v->url('avatar', $_POST['avatar'] ?? '');
        if (!$v->ok()) {
            Util::flash('error','Fix the errors below.');
            $this->render('settings/profile', ['user'=>Auth::user(),'errors'=>$v->errors]);
            return;
        }
        $pdo = DB::conn();
        $stmt = $pdo->prepare('UPDATE users SET name=:n, email=:e, avatar=:a, currency=:c WHERE id=:id');
        try {
            $stmt->execute([
                ':n'=>$_POST['name'], ':e'=>mb_strtolower($_POST['email']), ':a'=>($_POST['avatar'] ?? ''), ':c'=>($_POST['currency'] ?? 'USD'), ':id'=>Auth::user()['id']
            ]);
            // update session snapshot
            $u = $pdo->query('SELECT id,name,email,is_temporary,currency,avatar,created_at FROM users WHERE id='.(int)Auth::user()['id'])->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user'] = $u;
            Util::flash('success','Profile updated');
            Util::redirect('/settings/profile');
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(),'UNIQUE')) { Util::flash('error','Email already in use'); }
            else { Util::flash('error','Update failed'); }
            $this->render('settings/profile', ['user'=>Auth::user()]);
        }
    }

    public function setPassword(): void
    {
        Auth::requireAuth();
        CSRF::validate();
        $v = new Validator();
        $v->required(['password'], $_POST);
        if (!$v->ok()) { Util::flash('error','Password required'); Util::redirect('/settings/profile'); }
        $pdo = DB::conn();
        $pdo->prepare('UPDATE users SET password_hash=:ph, is_temporary=0 WHERE id=:id')->execute([
            ':ph'=>password_hash($_POST['password'], PASSWORD_DEFAULT), ':id'=>Auth::user()['id']
        ]);
        Util::flash('success','Password set');
        Util::redirect('/settings/profile');
    }
}
