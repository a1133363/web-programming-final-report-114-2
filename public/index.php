<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AuctionController;
use App\Controllers\AuthController;
use App\Controllers\BuyerController;
use App\Controllers\HomeController;
use App\Controllers\SellerController;
use App\Controllers\UploadController;

$page = (string) ($_GET['page'] ?? 'home');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$routes = [
    'GET' => [
        'home' => [HomeController::class, 'index'],
        'about' => [HomeController::class, 'about'],
        'auction' => [AuctionController::class, 'show'],
        'wanted' => [HomeController::class, 'wanted'],
        'login' => [AuthController::class, 'loginForm'],
        'register' => [AuthController::class, 'registerForm'],
        'buyer' => [BuyerController::class, 'index'],
        'buyer-payment' => [BuyerController::class, 'payment'],
        'buyer-dispute' => [BuyerController::class, 'disputeForm'],
        'buyer-review' => [BuyerController::class, 'reviewForm'],
        'seller' => [SellerController::class, 'index'],
        'admin' => [AdminController::class, 'index'],
        'admin-disputes' => [AdminController::class, 'disputes'],
        'admin-logs' => [AdminController::class, 'logs'],
        'admin-export' => [AdminController::class, 'export'],
        'upload' => [UploadController::class, 'show'],
    ],
    'POST' => [
        'login' => [AuthController::class, 'login'],
        'register' => [AuthController::class, 'register'],
        'logout' => [AuthController::class, 'logout'],
        'bid' => [AuctionController::class, 'bid'],
        'watch' => [AuctionController::class, 'watch'],
        'buyer-pay' => [BuyerController::class, 'pay'],
        'buyer-dispute-submit' => [BuyerController::class, 'dispute'],
        'buyer-review-submit' => [BuyerController::class, 'review'],
        'seller-create' => [SellerController::class, 'create'],
        'seller-delivery' => [SellerController::class, 'updateDelivery'],
        'ai-description' => [SellerController::class, 'aiDescription'],
        'admin-review' => [AdminController::class, 'review'],
        'admin-dispute-resolve' => [AdminController::class, 'resolveDispute'],
        'admin-announce' => [AdminController::class, 'announce'],
    ],
];

if (!isset($routes[$method][$page])) {
    http_response_code(404);
    \App\Core\View::render('errors/404', ['pageTitle' => '找不到頁面']);
    exit;
}

[$controller, $action] = $routes[$method][$page];
(new $controller())->{$action}();
