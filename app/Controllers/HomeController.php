<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Auction;

final class HomeController
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category' => (string) ($_GET['category'] ?? ''),
            'risk' => (string) ($_GET['risk'] ?? ''),
            'min_price' => filter_var($_GET['min_price'] ?? null, FILTER_VALIDATE_FLOAT) ?: '',
            'max_price' => filter_var($_GET['max_price'] ?? null, FILTER_VALIDATE_FLOAT) ?: '',
            'ending' => in_array($_GET['ending'] ?? '', ['6', '12', '24', '72'], true) ? $_GET['ending'] : '',
        ];
        $model = new Auction();

        $announcements = [];
        $pdo = Database::connection();
        if ($pdo) {
            $announcements = $pdo->query(
                'SELECT title, body, published_at FROM announcements
                 WHERE status = "published" ORDER BY published_at DESC LIMIT 3'
            )->fetchAll();
        }

        View::render('front/home', [
            'pageTitle' => '探索拍品',
            'auctions' => $model->featured($filters),
            'categories' => $model->categories(),
            'filters' => $filters,
            'announcements' => $announcements,
        ]);
    }

    public function about(): void
    {
        View::render('front/about', ['pageTitle' => '關於暗標局']);
    }

    public function wanted(): void
    {
        $wanted = [];
        $pdo = Database::connection();
        if ($pdo) {
            $wanted = $pdo->query(
                'SELECT u.username, w.reason, w.level, DATE(w.created_at) AS reported_at
                 FROM wanted_list w JOIN users u ON u.id = w.user_id
                 WHERE w.status = "active"
                 ORDER BY FIELD(w.level, "critical", "high", "medium", "low")'
            )->fetchAll();
        }
        View::render('front/wanted', [
            'pageTitle' => '黑市通緝名單',
            'wanted' => $wanted,
        ]);
    }
}
