<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class BidService
{
    public function place(int $auctionId, int $buyerId, float $amount, ?float $proxyMax = null): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new RuntimeException('示範模式無法寫入出價，請先匯入資料庫。');
        }

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('SELECT * FROM auctions WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $auctionId]);
            $auction = $statement->fetch();
            if (!$auction || $auction['status'] !== 'active') {
                throw new RuntimeException('此拍賣目前無法出價。');
            }
            $now = time();
            if ($now < strtotime($auction['start_at']) || $now >= strtotime($auction['end_at'])) {
                throw new RuntimeException('出價時間不在拍賣期間內。');
            }
            if ((int) $auction['seller_id'] === $buyerId) {
                throw new RuntimeException('賣家不可對自己的拍賣品出價。');
            }

            $minimum = (float) $auction['current_price'] + (float) $auction['min_increment'];
            if ($amount < $minimum) {
                throw new RuntimeException('出價至少需為 ' . number_format($minimum) . '。');
            }

            $bid = $pdo->prepare(
                'INSERT INTO bids (auction_id, buyer_id, bid_amount, is_auto) VALUES (:auction_id, :buyer_id, :amount, 0)'
            );
            $bid->execute(['auction_id' => $auctionId, 'buyer_id' => $buyerId, 'amount' => $amount]);

            if ($proxyMax !== null) {
                if ($proxyMax < $amount) {
                    throw new RuntimeException('代理出價上限不可低於本次出價。');
                }
                $proxy = $pdo->prepare(
                    'INSERT INTO proxy_bids (auction_id, buyer_id, max_amount, is_active)
                     VALUES (:auction_id, :buyer_id, :max_amount, 1)
                     ON DUPLICATE KEY UPDATE max_amount = VALUES(max_amount), is_active = 1, updated_at = NOW()'
                );
                $proxy->execute(['auction_id' => $auctionId, 'buyer_id' => $buyerId, 'max_amount' => $proxyMax]);
            }

            $update = $pdo->prepare('UPDATE auctions SET current_price = :amount WHERE id = :id');
            $update->execute(['amount' => $amount, 'id' => $auctionId]);
            $this->settleProxyBids($auctionId, (float) $auction['min_increment']);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function settleProxyBids(int $auctionId, float $increment): void
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return;
        }
        $statement = $pdo->prepare(
            'SELECT buyer_id, max_amount FROM proxy_bids
             WHERE auction_id = :auction_id AND is_active = 1
             ORDER BY max_amount DESC, created_at ASC LIMIT 2'
        );
        $statement->execute(['auction_id' => $auctionId]);
        $proxies = $statement->fetchAll();
        if (!$proxies) {
            return;
        }

        $currentStatement = $pdo->prepare('SELECT current_price FROM auctions WHERE id = :id');
        $currentStatement->execute(['id' => $auctionId]);
        $current = (float) $currentStatement->fetchColumn();
        $winner = $proxies[0];
        $target = isset($proxies[1])
            ? min((float) $winner['max_amount'], (float) $proxies[1]['max_amount'] + $increment)
            : min((float) $winner['max_amount'], $current + $increment);
        if ($target <= $current) {
            return;
        }
        $bid = $pdo->prepare(
            'INSERT INTO bids (auction_id, buyer_id, bid_amount, is_auto) VALUES (:auction_id, :buyer_id, :amount, 1)'
        );
        $bid->execute(['auction_id' => $auctionId, 'buyer_id' => $winner['buyer_id'], 'amount' => $target]);
        $update = $pdo->prepare('UPDATE auctions SET current_price = :amount WHERE id = :id');
        $update->execute(['amount' => $target, 'id' => $auctionId]);
    }
}
