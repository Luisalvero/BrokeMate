<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\DB;
use App\Lib\Util;
use App\Lib\Validator;
use PDO;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        $this->render('auth/login', []);
    }

    public function login(): void
    {
        CSRF::validate();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Util::rateLimit('login_' . $ip, 10, 60)) {
            Util::flash('error', 'Too many attempts. Try again later.');
            Util::redirect('/login');
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (Auth::login($email, $password)) {
            Util::flash('success', 'Welcome back!');
            Util::redirect('/dashboard');
        }
        Util::flash('error', 'Invalid credentials');
        Util::redirect('/login');
    }

    public function showRegister(): void
    {
        $this->render('auth/register', []);
    }

    public function register(): void
    {
        CSRF::validate();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Util::rateLimit('register_' . $ip, 5, 3600)) {
            Util::flash('error', 'Rate limit exceeded.');
            Util::redirect('/register');
        }
        $v = new Validator();
        $v->required(['name','email','password'], $_POST);
        $v->email('email', $_POST['email'] ?? '');
        if (!$v->ok()) {
            Util::flash('error', 'Please correct the errors.');
            $this->render('auth/register', ['errors' => $v->errors, 'old' => $_POST]);
            return;
        }
        try {
            $user = Auth::register($_POST);
            $_SESSION['user'] = $user;
            Util::flash('success', 'Account created.');
            Util::redirect('/dashboard');
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                Util::flash('error', 'Email already in use.');
                $this->render('auth/register', ['errors' => ['email' => 'Email already in use'], 'old' => $_POST]);
                return;
            }
            throw $e;
        }
    }

    public function guest(): void
    {
        CSRF::validate();
        $name = trim($_POST['name'] ?? 'Guest' . random_int(100,999));
        $user = Auth::createGuest($name);
        Util::flash('info', 'Temporary account created. Set a password in settings to keep it.');
        Util::redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Util::redirect('/');
    }
}
