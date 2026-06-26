<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    public function loginForm(): void
    {
        View::render('auth/login', ['pageTitle' => '登入暗標局']);
    }

    public function login(): never
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新登入。');
            redirect('login');
        }
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        if (!$email || $password === '') {
            flash('error', '請輸入有效的電子信箱與密碼。');
            redirect('login');
        }

        if (!Database::available()) {
            flash('error', '系統暫時無法連線，請稍後再試。');
            redirect('login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail((string) $email);
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts'] = ((int) ($_SESSION['login_attempts'] ?? 0)) + 1;
            flash('error', '帳號、密碼錯誤或帳號已停權。');
            redirect('login');
        }
        Auth::login($userModel->findWithRoles((int) $user['id']) ?? $user);
        unset($_SESSION['login_attempts']);
        flash('success', '身分驗證完成。');
        redirect('buyer');
    }

    public function registerForm(): void
    {
        View::render('auth/register', ['pageTitle' => '建立匿名席位']);
    }

    public function register(): never
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新送出。');
            redirect('register');
        }
        $data = [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'role' => 'user',
        ];
        $errors = [];
        if (mb_strlen($data['username']) < 2 || mb_strlen($data['username']) > 40) {
            $errors[] = '匿名代號需為 2–40 個字元。';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = '電子信箱格式不正確。';
        }
        if (strlen($data['password']) < 8) {
            $errors[] = '密碼至少需要 8 個字元。';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('register');
        }

        try {
            $id = (new User())->create($data);
            Auth::login((new User())->findWithRoles($id) ?? ['id' => $id, 'username' => $data['username'], 'roles' => ['user']]);
            flash('success', '席位已建立，初始信用分數為 80。');
            redirect('buyer');
        } catch (\Throwable $exception) {
            $msg = $exception->getMessage();
            if (str_contains($msg, 'Duplicate')) {
                $error = str_contains($msg, 'username') || str_contains($msg, 'uq_users_username')
                    ? '此匿名代號已被使用。'
                    : '此電子信箱已被使用。';
            } else {
                $error = $msg;
            }
            flash('error', $error);
            redirect('register');
        }
    }

    public function logout(): never
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::logout();
        }
        redirect('home');
    }
}
