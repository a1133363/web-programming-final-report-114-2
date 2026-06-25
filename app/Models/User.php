<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findByEmail(string $email): ?array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return null;
        }

        $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function findWithRoles(int $id): ?array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return $_SESSION['auth_user'] ?? null;
        }

        $statement = $pdo->prepare(
            'SELECT u.*, GROUP_CONCAT(r.name ORDER BY r.name) AS role_names
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = :id
             GROUP BY u.id'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        if (!$user) {
            return null;
        }
        $user['roles'] = array_values(array_filter(explode(',', (string) $user['role_names'])));
        return $user;
    }

    public function create(array $data): int
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('資料庫尚未連線，請先匯入 SQL。');
        }

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, credit_score, status)
                 VALUES (:username, :email, :password_hash, 80, "active")'
            );
            $statement->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);
            $userId = (int) $pdo->lastInsertId();
            $role = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id)
                 SELECT :user_id, id FROM roles WHERE name = :role'
            );
            $role->execute(['user_id' => $userId, 'role' => $data['role'] ?? 'user']);
            $pdo->commit();
            return $userId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
