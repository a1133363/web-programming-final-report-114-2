<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
            return $_SESSION['auth_user'];
        }

        $user = (new User())->findWithRoles((int) $_SESSION['user_id']);
        if ($user) {
            $_SESSION['auth_user'] = $user;
        }

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user !== null && in_array($role, $user['roles'] ?? [], true);
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['auth_user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['auth_user']);
        session_regenerate_id(true);
    }
}
