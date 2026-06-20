<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            flash('error', '請先登入以繼續操作。');
            redirect('login');
        }
    }
}
