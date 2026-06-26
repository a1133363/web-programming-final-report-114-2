<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class AuctionService
{
    public function closeExpired(): int
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new RuntimeException('無法連線 MySQL。');
        }

        $ids = $pdo->query(
            'SELECT id FROM auctions WHERE status = "active" AND end_at <= NOW() ORDER BY id'
        )->fetchAll(\PDO::FETCH_COLUMN);
        $closed = 0;
        foreach ($ids as $id) {
            $pdo->beginTransaction();
            try {
                $auctionStatement = $pdo->prepare('SELECT * FROM auctions WHERE id = :id FOR UPDATE');
                $auctionStatement->execute(['id' => $id]);
                $auction = $auctionStatement->fetch();
                if (!$auction || $auction['status'] !== 'active') {
                    $pdo->rollBack();
                    continue;
                }
                $bidStatement = $pdo->prepare(
                    'SELECT buyer_id, bid_amount FROM bids WHERE auction_id = :id
                     ORDER BY bid_amount DESC, created_at ASC LIMIT 1'
                );
                $bidStatement->execute(['id' => $id]);
                $winner = $bidStatement->fetch();
                $reserveMet = $winner && (!$auction['reserve_price'] || (float) $winner['bid_amount'] >= (float) $auction['reserve_price']);
                if (!$reserveMet) {
                    $update = $pdo->prepare('UPDATE auctions SET status = "unsold" WHERE id = :id');
                    $update->execute(['id' => $id]);
                } else {
                    $update = $pdo->prepare('UPDATE auctions SET status = "ended", current_price = :price WHERE id = :id');
                    $update->execute(['price' => $winner['bid_amount'], 'id' => $id]);
                    $order = $pdo->prepare(
                        'INSERT INTO orders (order_no, auction_id, buyer_id, seller_id, final_price, platform_fee, status, payment_due_at)
                         VALUES (:order_no, :auction_id, :buyer_id, :seller_id, :price, :fee, "pending_payment", DATE_ADD(NOW(), INTERVAL 48 HOUR))'
                    );
                    $order->execute([
                        'order_no' => 'NO-' . date('Ymd') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                        'auction_id' => $id,
                        'buyer_id' => $winner['buyer_id'],
                        'seller_id' => $auction['seller_id'],
                        'price' => $winner['bid_amount'],
                        'fee' => round((float) $winner['bid_amount'] * 0.05, 2),
                    ]);
                    $notice = $pdo->prepare(
                        'INSERT INTO notifications (user_id, type, title, message, action_url)
                         VALUES (:user_id, "won", "你已得標", :message, "index.php?page=buyer")'
                    );
                    $notice->execute([
                        'user_id' => $winner['buyer_id'],
                        'message' => '你已得標「' . $auction['title'] . '」，請於 48 小時內完成付款。',
                    ]);
                }
                $pdo->commit();
                $closed++;
            } catch (\Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('closeExpired failed for auction ' . $id . ': ' . $exception->getMessage());
                continue;
            }
        }
        return $closed;
    }
}
