<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class RoleMiddleware
{
    public static function handle(string ...$roles): void
    {
        AuthMiddleware::handle();
        foreach ($roles as $role) {
            if (Auth::hasRole($role)) {
                return;
            }
        }
        http_response_code(403);
        flash('error', '你的角色沒有此功能的存取權。');
        redirect('home');
    }
}
