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
        'seller' => [SellerController::class, 'index'],
        'admin' => [AdminController::class, 'index'],
        'upload' => [UploadController::class, 'show'],
    ],
    'POST' => [
        'login' => [AuthController::class, 'login'],
        'register' => [AuthController::class, 'register'],
        'logout' => [AuthController::class, 'logout'],
        'bid' => [AuctionController::class, 'bid'],
        'watch' => [AuctionController::class, 'watch'],
        'seller-create' => [SellerController::class, 'create'],
        'ai-description' => [SellerController::class, 'aiDescription'],
        'admin-review' => [AdminController::class, 'review'],
    ],
];

if (!isset($routes[$method][$page])) {
    http_response_code(404);
    \App\Core\View::render('errors/404', ['pageTitle' => '找不到頁面']);
    exit;
}

[$controller, $action] = $routes[$method][$page];
(new $controller())->{$action}();
