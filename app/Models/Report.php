<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Report
{
    public function dashboard(): array
    {
        $pdo = Database::connection();
        if (!$pdo) {
            return [
                'totals' => ['volume' => 2849000, 'active' => 132, 'disputes' => 7, 'risk_ratio' => 18.4],
                'daily' => [180000, 260000, 210000, 460000, 390000, 630000, 719000],
                'categories' => [38, 24, 51, 19],
                'pending' => array_slice(DemoData::auctions(), 0, 3),
                'wanted' => DemoData::wanted(),
            ];
        }

        $totals = $pdo->query(
            'SELECT
                COALESCE((SELECT SUM(final_price) FROM orders WHERE status IN ("pending_delivery", "completed")), 0) AS volume,
                (SELECT COUNT(*) FROM auctions WHERE status = "active") AS active,
                (SELECT COUNT(*) FROM disputes WHERE status IN ("open", "investigating")) AS disputes,
                ROUND(100 * (SELECT COUNT(*) FROM auctions WHERE risk_level IN ("dangerous", "prohibited")) /
                    NULLIF((SELECT COUNT(*) FROM auctions), 0), 1) AS risk_ratio'
        )->fetch() ?: [];
        $pending = $pdo->query(
            'SELECT a.*, u.username AS seller_name, c.name AS category_name
             FROM auctions a JOIN users u ON u.id = a.seller_id
             JOIN categories c ON c.id = a.category_id
             WHERE a.status = "pending_review" ORDER BY a.created_at ASC LIMIT 8'
        )->fetchAll();
        $wanted = $pdo->query(
            'SELECT w.*, u.username FROM wanted_list w JOIN users u ON u.id = w.user_id
             WHERE w.status = "active" ORDER BY FIELD(w.level, "critical", "high", "medium", "low")'
        )->fetchAll();

        $dailyRows = $pdo->query(
            'SELECT DATE(created_at) AS report_date, SUM(final_price) AS total
             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)'
        )->fetchAll();
        $dailyMap = array_column($dailyRows, 'total', 'report_date');
        $daily = [];
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = date('Y-m-d', strtotime('-' . $daysAgo . ' day'));
            $daily[] = (float) ($dailyMap[$date] ?? 0);
        }

        $categoryRows = $pdo->query(
            'SELECT c.id, COUNT(b.id) AS bid_count
             FROM categories c
             LEFT JOIN auctions a ON a.category_id = c.id
             LEFT JOIN bids b ON b.auction_id = a.id
             WHERE c.id BETWEEN 1 AND 4 GROUP BY c.id ORDER BY c.id'
        )->fetchAll();
        $categoryMap = array_column($categoryRows, 'bid_count', 'id');
        $categories = [];
        for ($id = 1; $id <= 4; $id++) {
            $categories[] = (int) ($categoryMap[$id] ?? 0);
        }

        return [
            'totals' => $totals,
            'daily' => $daily,
            'categories' => $categories,
            'pending' => $pending,
            'wanted' => $wanted,
        ];
    }
}
