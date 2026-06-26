<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Order
{
    private const INITIAL_WALLET_BALANCE = 500000.0;

    public function find(int $id): ?array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return null;
        }
        $statement = $pdo->prepare(
            'SELECT o.*, a.title, a.slug, u.username AS seller_name, bu.username AS buyer_name
             FROM orders o
             JOIN auctions a ON a.id = o.auction_id
             JOIN users u ON u.id = o.seller_id
             JOIN users bu ON bu.id = o.buyer_id
             WHERE o.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function forBuyer(int $buyerId): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return [[
                'id' => 1042,
                'title' => '北境玻璃種子',
                'seller_name' => '灰鴉收藏室',
                'final_price' => 43000,
                'status' => 'pending_delivery',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ]];
        }
        $statement = $pdo->prepare(
            'SELECT o.*, a.title, u.username AS seller_name,
                    d.delivery_status, d.tracking_code
             FROM orders o
             JOIN auctions a ON a.id = o.auction_id
             JOIN users u ON u.id = o.seller_id
             LEFT JOIN deliveries d ON d.order_id = o.id
             WHERE o.buyer_id = :buyer_id ORDER BY o.created_at DESC'
        );
        $statement->execute(['buyer_id' => $buyerId]);
        return $statement->fetchAll();
    }

    public function forSeller(int $sellerId): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT o.*, a.title, u.username AS buyer_name,
                    d.delivery_status, d.tracking_code
             FROM orders o
             JOIN auctions a ON a.id = o.auction_id
             JOIN users u ON u.id = o.buyer_id
             LEFT JOIN deliveries d ON d.order_id = o.id
             WHERE o.seller_id = :seller_id ORDER BY o.created_at DESC'
        );
        $statement->execute(['seller_id' => $sellerId]);
        return $statement->fetchAll();
    }

    public function markPaid(int $orderId, int $buyerId, string $method): void
    {
        if ($method !== 'escrow') {
            throw new \RuntimeException('目前僅支援錢包託管付款。');
        }
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('示範模式無法付款，請先匯入資料庫。');
        }
        $pdo->beginTransaction();
        try {
            $orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
            $orderStatement->execute(['id' => $orderId]);
            $order = $orderStatement->fetch();
            if (!$order || (int) $order['buyer_id'] !== $buyerId) {
                throw new \RuntimeException('訂單不存在或不屬於目前使用者。');
            }
            if ($order['status'] !== 'pending_payment') {
                throw new \RuntimeException('此訂單無需付款或已付款。');
            }

            $wallet = $this->walletForUpdate($pdo, $buyerId);
            $amount = (float) $order['final_price'];
            if ((float) $wallet['balance'] < $amount) {
                throw new \RuntimeException('錢包餘額不足，請先補足模擬餘額。');
            }

            $payment = $pdo->prepare(
                'INSERT INTO payments (order_id, transaction_ref, method, amount, status, paid_at)
                 VALUES (:order_id, :ref, :method, :amount, "paid", NOW())'
            );
            $payment->execute([
                'order_id' => $orderId,
                'ref' => 'TX-' . strtoupper(bin2hex(random_bytes(6))),
                'method' => $method,
                'amount' => $amount,
            ]);
            $paymentId = (int) $pdo->lastInsertId();
            $balanceAfter = (float) $wallet['balance'] - $amount;

            $walletUpdate = $pdo->prepare('UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE user_id = :user_id');
            $walletUpdate->execute(['balance' => $balanceAfter, 'user_id' => $buyerId]);

            $transaction = $pdo->prepare(
                'INSERT INTO wallet_transactions
                 (user_id, order_id, payment_id, type, amount, balance_after, description)
                 VALUES (:user_id, :order_id, :payment_id, "payment", :amount, :balance_after, :description)'
            );
            $transaction->execute([
                'user_id' => $buyerId,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'description' => '訂單 ' . $order['order_no'] . ' 模擬付款',
            ]);

            $update = $pdo->prepare(
                'UPDATE orders SET status = "pending_delivery", updated_at = NOW() WHERE id = :id'
            );
            $update->execute(['id' => $orderId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateDelivery(int $orderId, int $sellerId, string $status, string $trackingCode = ''): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('示範模式無法更新物流，請先匯入資料庫。');
        }
        $pdo->beginTransaction();
        try {
            $orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
            $orderStatement->execute(['id' => $orderId]);
            $order = $orderStatement->fetch();
            if (!$order || (int) $order['seller_id'] !== $sellerId) {
                throw new \RuntimeException('訂單不存在或不屬於目前賣家。');
            }
            if (!in_array($order['status'], ['pending_delivery', 'disputed'], true)) {
                throw new \RuntimeException('此訂單狀態無法更新物流。');
            }

            $upsert = $pdo->prepare(
                'INSERT INTO deliveries (order_id, delivery_status, tracking_code, shipped_at)
                 VALUES (:order_id, :status, :tracking, NOW())
                 ON DUPLICATE KEY UPDATE
                 delivery_status = VALUES(delivery_status),
                 tracking_code = VALUES(tracking_code),
                 shipped_at = VALUES(shipped_at),
                 updated_at = NOW()'
            );
            $upsert->execute([
                'order_id' => $orderId,
                'status' => $status,
                'tracking' => $trackingCode ?: null,
            ]);

            if ($status === 'delivered') {
                $delivered = $pdo->prepare(
                    'UPDATE deliveries SET delivered_at = NOW() WHERE order_id = :order_id'
                );
                $delivered->execute(['order_id' => $orderId]);

                $complete = $pdo->prepare(
                    'UPDATE orders SET status = "completed", completed_at = NOW() WHERE id = :id'
                );
                $complete->execute(['id' => $orderId]);
                $this->releaseSellerPayout($pdo, $orderId);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function createDispute(int $orderId, int $createdBy, string $reason): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('示範模式無法提出爭議，請先匯入資料庫。');
        }
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT INTO disputes (order_id, created_by, reason, status)
                 VALUES (:order_id, :created_by, :reason, "open")'
            );
            $insert->execute([
                'order_id' => $orderId,
                'created_by' => $createdBy,
                'reason' => $reason,
            ]);

            $update = $pdo->prepare(
                'UPDATE orders SET status = "disputed" WHERE id = :id'
            );
            $update->execute(['id' => $orderId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function createReview(int $orderId, int $reviewerId, int $revieweeId, int $rating, string $comment): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('示範模式無法評價，請先匯入資料庫。');
        }
        $statement = $pdo->prepare(
            'INSERT INTO reviews (order_id, reviewer_id, reviewee_id, rating, comment)
             VALUES (:order_id, :reviewer_id, :reviewee_id, :rating, :comment)'
        );
        $statement->execute([
            'order_id' => $orderId,
            'reviewer_id' => $reviewerId,
            'reviewee_id' => $revieweeId,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }

    public function disputes(): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return [];
        }
        $statement = $pdo->query(
            'SELECT d.*, o.order_no, a.title, cr.username AS creator_name, ad.username AS admin_name
             FROM disputes d
             JOIN orders o ON o.id = d.order_id
             JOIN auctions a ON a.id = o.auction_id
             JOIN users cr ON cr.id = d.created_by
             LEFT JOIN users ad ON ad.id = d.admin_id
             ORDER BY d.created_at DESC'
        );
        return $statement->fetchAll();
    }

    public function resolveDispute(int $disputeId, int $adminId, string $resolution, string $status): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('示範模式無法處理爭議，請先匯入資料庫。');
        }
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare(
                'UPDATE disputes SET admin_id = :admin_id, resolution = :resolution,
                 status = :status, resolved_at = NOW(), updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                'admin_id' => $adminId,
                'resolution' => $resolution,
                'status' => $status,
                'id' => $disputeId,
            ]);

            if (in_array($status, ['resolved_buyer', 'resolved_seller', 'dismissed'], true)) {
                $orderId = $pdo->prepare('SELECT order_id FROM disputes WHERE id = :id');
                $orderId->execute(['id' => $disputeId]);
                $oid = (int) $orderId->fetchColumn();

                $orderStatus = $status === 'resolved_buyer' ? 'refunded' : 'completed';
                $upd = $pdo->prepare('UPDATE orders SET status = :st WHERE id = :oid');
                $upd->execute(['st' => $orderStatus, 'oid' => $oid]);

                if ($status === 'resolved_buyer') {
                    $this->refundBuyer($pdo, $oid);
                } else {
                    $this->releaseSellerPayout($pdo, $oid);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function walletForUpdate(\PDO $pdo, int $userId): array
    {
        $create = $pdo->prepare(
            'INSERT IGNORE INTO wallets (user_id, balance) VALUES (:user_id, :balance)'
        );
        $create->execute(['user_id' => $userId, 'balance' => self::INITIAL_WALLET_BALANCE]);

        $wallet = $pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
        $wallet->execute(['user_id' => $userId]);
        $row = $wallet->fetch();
        if (!$row) {
            throw new \RuntimeException('錢包初始化失敗。');
        }
        return $row;
    }

    private function releaseSellerPayout(\PDO $pdo, int $orderId): void
    {
        $paid = $pdo->prepare('SELECT id FROM payments WHERE order_id = :order_id AND status = "paid" LIMIT 1');
        $paid->execute(['order_id' => $orderId]);
        $paymentId = $paid->fetchColumn();
        if ($paymentId === false) {
            return;
        }

        $exists = $pdo->prepare(
            'SELECT 1 FROM wallet_transactions WHERE order_id = :order_id AND type = "payout" LIMIT 1'
        );
        $exists->execute(['order_id' => $orderId]);
        if ($exists->fetchColumn()) {
            return;
        }

        $orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
        $orderStatement->execute(['id' => $orderId]);
        $order = $orderStatement->fetch();
        if (!$order) {
            return;
        }

        $amount = max(0, (float) $order['final_price'] - (float) $order['platform_fee']);
        if ($amount <= 0) {
            return;
        }

        $wallet = $this->walletForUpdate($pdo, (int) $order['seller_id']);
        $balanceAfter = (float) $wallet['balance'] + $amount;
        $walletUpdate = $pdo->prepare('UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE user_id = :user_id');
        $walletUpdate->execute(['balance' => $balanceAfter, 'user_id' => (int) $order['seller_id']]);

        $transaction = $pdo->prepare(
            'INSERT INTO wallet_transactions
             (user_id, order_id, payment_id, type, amount, balance_after, description)
             VALUES (:user_id, :order_id, :payment_id, "payout", :amount, :balance_after, :description)'
        );
        $transaction->execute([
            'user_id' => (int) $order['seller_id'],
            'order_id' => $orderId,
            'payment_id' => (int) $paymentId,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => '訂單 ' . $order['order_no'] . ' 交付釋款',
        ]);
    }

    private function refundBuyer(\PDO $pdo, int $orderId): void
    {
        $paid = $pdo->prepare('SELECT id FROM payments WHERE order_id = :order_id AND status = "paid" LIMIT 1');
        $paid->execute(['order_id' => $orderId]);
        $paymentId = $paid->fetchColumn();
        if ($paymentId === false) {
            return;
        }

        $exists = $pdo->prepare(
            'SELECT 1 FROM wallet_transactions WHERE order_id = :order_id AND type = "refund" LIMIT 1'
        );
        $exists->execute(['order_id' => $orderId]);
        if ($exists->fetchColumn()) {
            return;
        }

        $orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
        $orderStatement->execute(['id' => $orderId]);
        $order = $orderStatement->fetch();
        if (!$order) {
            return;
        }

        $amount = (float) $order['final_price'];
        $wallet = $this->walletForUpdate($pdo, (int) $order['buyer_id']);
        $balanceAfter = (float) $wallet['balance'] + $amount;
        $walletUpdate = $pdo->prepare('UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE user_id = :user_id');
        $walletUpdate->execute(['balance' => $balanceAfter, 'user_id' => (int) $order['buyer_id']]);

        $paymentUpdate = $pdo->prepare('UPDATE payments SET status = "refunded", updated_at = NOW() WHERE id = :id');
        $paymentUpdate->execute(['id' => (int) $paymentId]);

        $transaction = $pdo->prepare(
            'INSERT INTO wallet_transactions
             (user_id, order_id, payment_id, type, amount, balance_after, description)
             VALUES (:user_id, :order_id, :payment_id, "refund", :amount, :balance_after, :description)'
        );
        $transaction->execute([
            'user_id' => (int) $order['buyer_id'],
            'order_id' => $orderId,
            'payment_id' => (int) $paymentId,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => '訂單 ' . $order['order_no'] . ' 爭議退款',
        ]);
    }
}
