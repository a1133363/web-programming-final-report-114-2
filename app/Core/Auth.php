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

        $user = (new User())->findWithRoles((int) $_SESSION['user_id']);
        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            self::logout();
            return null;
        }
        unset($user['password_hash']);
        $_SESSION['auth_user'] = $user;

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
        unset($user['password_hash']);
        $_SESSION['auth_user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['auth_user']);
        session_regenerate_id(true);
    }

    public static function refresh(int $userId): void
    {
        $user = (new User())->findWithRoles($userId);
        if ($user) {
            unset($user['password_hash']);
            $_SESSION['auth_user'] = $user;
        }
    }
}
