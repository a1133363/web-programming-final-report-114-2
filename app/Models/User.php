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

    public function findByUsername(string $username): ?array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return null;
        }
        $statement = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        return $statement->fetch() ?: null;
    }

    public function updateProfile(int $userId, string $username, string $email, ?string $avatarPath = null): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('資料庫尚未連線，請先匯入 SQL。');
        }
        $sql = 'UPDATE users SET username = :username, email = :email, updated_at = NOW()';
        $params = ['username' => $username, 'email' => $email, 'id' => $userId];
        if ($avatarPath !== null) {
            $sql .= ', avatar_path = :avatar_path';
            $params['avatar_path'] = $avatarPath;
        }
        $sql .= ' WHERE id = :id';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('資料庫尚未連線，請先匯入 SQL。');
        }
        $statement = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            throw new \RuntimeException('目前密碼不正確。');
        }
        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('新密碼至少需要 8 個字元。');
        }
        $update = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $update->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
    }

    public function deleteAccount(int $userId): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('資料庫尚未連線，請先匯入 SQL。');
        }
        $pdo->beginTransaction();
        try {
            $blockers = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM auctions WHERE seller_id = :id) AS auctions,
                    (SELECT COUNT(*) FROM orders WHERE buyer_id = :id OR seller_id = :id) AS orders'
            );
            $blockers->execute(['id' => $userId]);
            $counts = $blockers->fetch();
            if ((int) $counts['auctions'] > 0 || (int) $counts['orders'] > 0) {
                throw new \RuntimeException('帳號仍有拍賣品或訂單紀錄，無法刪除。');
            }
            $pdo->prepare('DELETE FROM bids WHERE buyer_id = :id')->execute(['id' => $userId]);
            $pdo->prepare('DELETE FROM proxy_bids WHERE buyer_id = :id')->execute(['id' => $userId]);
            $pdo->prepare('DELETE FROM watchlists WHERE user_id = :id')->execute(['id' => $userId]);
            $pdo->prepare('DELETE FROM reviews WHERE reviewer_id = :id OR reviewee_id = :id')->execute(['id' => $userId]);
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $userId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
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
            try {
                $wallet = $pdo->prepare(
                    'INSERT INTO wallets (user_id, balance) VALUES (:user_id, :balance)'
                );
                $wallet->execute(['user_id' => $userId, 'balance' => Wallet::INITIAL_BALANCE]);
            } catch (\Throwable) {
                // ponytail: older imported schemas may not have wallets yet; payment creates one after SQL update.
            }
            $pdo->commit();
            return $userId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
