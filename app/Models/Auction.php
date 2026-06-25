<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Auction
{
    public function featured(array $filters = []): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return $this->filterDemo(DemoData::auctions(), $filters);
        }

        $where = ['a.status = "active"', 'a.end_at > NOW()'];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(a.title LIKE :q1 OR a.description LIKE :q2 OR u.username LIKE :q3)';
            $params['q1'] = '%' . $filters['q'] . '%';
            $params['q2'] = '%' . $filters['q'] . '%';
            $params['q3'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['risk'])) {
            $where[] = 'a.risk_level = :risk';
            $params['risk'] = $filters['risk'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'a.category_id = :category';
            $params['category'] = (int) $filters['category'];
        }
        if (!empty($filters['min_price'])) {
            $where[] = 'a.current_price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = 'a.current_price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }
        if (!empty($filters['ending'])) {
            $where[] = 'a.end_at <= DATE_ADD(NOW(), INTERVAL ' . (int) $filters['ending'] . ' HOUR)';
        }

        $sql = 'SELECT a.*, c.name AS category_name, u.username AS seller_name,
                       u.credit_score AS seller_credit,
                       COALESCE(ai.file_path, "assets/images/item-chronometer.webp") AS image_path,
                       (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) AS bid_count
                FROM auctions a
                JOIN categories c ON c.id = a.category_id
                JOIN users u ON u.id = a.seller_id
                LEFT JOIN auction_images ai ON ai.auction_id = a.id AND ai.is_cover = 1
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.featured DESC, a.end_at ASC LIMIT 12';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            foreach (DemoData::auctions() as $auction) {
                if ((int) $auction['id'] === $id) {
                    return $auction;
                }
            }
            return null;
        }

        $statement = $pdo->prepare(
            'SELECT a.*, c.name AS category_name, u.username AS seller_name,
                    u.credit_score AS seller_credit,
                    COALESCE(ai.file_path, "assets/images/item-chronometer.webp") AS image_path,
                    (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) AS bid_count
             FROM auctions a
             JOIN categories c ON c.id = a.category_id
             JOIN users u ON u.id = a.seller_id
             LEFT JOIN auction_images ai ON ai.auction_id = a.id AND ai.is_cover = 1
             WHERE a.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function bids(int $auctionId): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return DemoData::bids($auctionId);
        }

        $statement = $pdo->prepare(
            'SELECT u.username, b.bid_amount, b.is_auto, b.created_at
             FROM bids b JOIN users u ON u.id = b.buyer_id
             WHERE b.auction_id = :auction_id
             ORDER BY b.bid_amount DESC, b.created_at DESC LIMIT 20'
        );
        $statement->execute(['auction_id' => $auctionId]);
        return $statement->fetchAll();
    }

    public function categories(): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return DemoData::categories();
        }

        return $pdo->query(
            'SELECT c.id, c.name, c.code, COUNT(a.id) AS count
             FROM categories c LEFT JOIN auctions a ON a.category_id = c.id
             GROUP BY c.id ORDER BY c.sort_order, c.name'
        )->fetchAll();
    }

    public function sellerAuctions(int $sellerId): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return DemoData::auctions();
        }
        $statement = $pdo->prepare(
            'SELECT a.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) AS bid_count
             FROM auctions a JOIN categories c ON c.id = a.category_id
             WHERE a.seller_id = :seller_id ORDER BY a.created_at DESC'
        );
        $statement->execute(['seller_id' => $sellerId]);
        return $statement->fetchAll();
    }

    public function create(int $sellerId, array $data): int
    {
        $pdo = Database::connection();
        if (!$pdo) {
            throw new \RuntimeException('資料庫尚未連線，請先匯入 SQL。');
        }
        $statement = $pdo->prepare(
            'INSERT INTO auctions
             (seller_id, category_id, lot_no, title, slug, description, starting_price,
              current_price, reserve_price, min_increment, risk_level, ai_risk_suggestion,
              status, start_at, end_at)
             VALUES
             (:seller_id, :category_id, :lot_no, :title, :slug, :description, :starting_price,
              :current_price, :reserve_price, :min_increment, :risk_level, :ai_risk_suggestion,
              "pending_review", :start_at, :end_at)'
        );
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $data['title']), '-')) ?: 'auction-' . time();
        $statement->execute([
            'seller_id' => $sellerId,
            'category_id' => $data['category_id'],
            'lot_no' => 'P-' . date('ymdHis'),
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'],
            'starting_price' => $data['starting_price'],
            'current_price' => $data['starting_price'],
            'reserve_price' => $data['reserve_price'] ?: null,
            'min_increment' => $data['min_increment'],
            'risk_level' => $data['risk_level'],
            'ai_risk_suggestion' => $data['ai_risk_suggestion'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    private function filterDemo(array $auctions, array $filters): array
    {
        $categoryMap = ['1' => '古代遺物', '2' => '異星科技', '3' => '失落情報', '4' => '禁忌文獻'];
        return array_values(array_filter($auctions, static function (array $auction) use ($filters, $categoryMap): bool {
            $query = trim((string) ($filters['q'] ?? ''));
            if ($query !== '' && !str_contains($auction['title'] . $auction['description'] . $auction['seller_name'], $query)) {
                return false;
            }
            if (!empty($filters['risk']) && $auction['risk_level'] !== $filters['risk']) {
                return false;
            }
            if (!empty($filters['category']) && ($categoryMap[(string) $filters['category']] ?? '') !== $auction['category_name']) {
                return false;
            }
            if (!empty($filters['min_price']) && (float) $auction['current_price'] < (float) $filters['min_price']) {
                return false;
            }
            if (!empty($filters['max_price']) && (float) $auction['current_price'] > (float) $filters['max_price']) {
                return false;
            }
            return empty($filters['ending']) || strtotime($auction['end_at']) <= time() + ((int) $filters['ending'] * 3600);
        }));
    }
}
