<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Auction;
use App\Models\DemoData;

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
        View::render('front/home', [
            'pageTitle' => '地下黑市拍賣會',
            'auctions' => $model->featured($filters),
            'categories' => $model->categories(),
            'filters' => $filters,
            'databaseAvailable' => Database::available(),
        ]);
    }

    public function wanted(): void
    {
        View::render('front/wanted', [
            'pageTitle' => '黑市通緝名單',
            'wanted' => DemoData::wanted(),
        ]);
    }
}
