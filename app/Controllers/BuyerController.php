<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Auction;
use App\Models\Order;

final class BuyerController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        View::render('buyer/dashboard', [
            'pageTitle' => '會員中心',
            'orders' => (new Order())->forBuyer((int) Auth::user()['id']),
            'watched' => array_slice((new Auction())->featured(), 0, 3),
        ]);
    }
}
