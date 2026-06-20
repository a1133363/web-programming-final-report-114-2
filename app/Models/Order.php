<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Order
{
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
            'SELECT o.*, a.title, u.username AS seller_name
             FROM orders o JOIN auctions a ON a.id = o.auction_id
             JOIN users u ON u.id = o.seller_id
             WHERE o.buyer_id = :buyer_id ORDER BY o.created_at DESC'
        );
        $statement->execute(['buyer_id' => $buyerId]);
        return $statement->fetchAll();
    }
}
