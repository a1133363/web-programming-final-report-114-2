<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Wallet
{
    public const INITIAL_BALANCE = 500000.0;

    public function summary(int $userId): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return [
                'available' => false,
                'balance' => 557000,
                'transactions' => [[
                    'type' => 'payment',
                    'amount' => 43000,
                    'balance_after' => 557000,
                    'description' => '示範訂單付款',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'order_no' => 'DEMO-1042',
                    'title' => '北境玻璃種子',
                ]],
            ];
        }

        try {
            $balance = $pdo->prepare('SELECT balance FROM wallets WHERE user_id = :user_id LIMIT 1');
            $balance->execute(['user_id' => $userId]);
            $value = $balance->fetchColumn();

            $transactions = $pdo->prepare(
                'SELECT wt.*, o.order_no, a.title
                 FROM wallet_transactions wt
                 LEFT JOIN orders o ON o.id = wt.order_id
                 LEFT JOIN auctions a ON a.id = o.auction_id
                 WHERE wt.user_id = :user_id
                 ORDER BY wt.created_at DESC LIMIT 20'
            );
            $transactions->execute(['user_id' => $userId]);

            return [
                'available' => true,
                'balance' => $value === false ? self::INITIAL_BALANCE : (float) $value,
                'transactions' => $transactions->fetchAll(),
            ];
        } catch (\Throwable) {
            return ['available' => false, 'balance' => 0, 'transactions' => []];
        }
    }
}
