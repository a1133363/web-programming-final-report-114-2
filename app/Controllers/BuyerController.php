<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Auction;
use App\Models\Order;
use App\Models\Wallet;

final class BuyerController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        $orderModel = new Order();
        View::render('buyer/dashboard', [
            'pageTitle' => '會員中心',
            'orders' => $orderModel->forBuyer((int) Auth::user()['id']),
            'watched' => array_slice((new Auction())->featured(), 0, 3),
            'wallet' => (new Wallet())->summary((int) Auth::user()['id']),
        ]);
    }

    public function payment(): void
    {
        AuthMiddleware::handle();
        $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0;
        $order = (new Order())->find($orderId);
        if (!$order || (int) $order['buyer_id'] !== (int) Auth::user()['id']) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => '找不到訂單']);
            return;
        }
        if ($order['status'] !== 'pending_payment') {
            flash('error', '此訂單無需付款或已付款。');
            redirect('buyer');
        }
        View::render('buyer/payment', [
            'pageTitle' => '訂單付款',
            'order' => $order,
            'wallet' => (new Wallet())->summary((int) Auth::user()['id']),
        ]);
    }

    public function pay(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('buyer');
        }
        $orderId = filter_var($_POST['order_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        if (empty($_POST['confirm_payment'])) {
            flash('error', '請先確認付款內容');
            if ($orderId > 0) {
                redirect('buyer-payment', ['order_id' => $orderId]);
            }
            redirect('buyer');
        }
        $method = (string) ($_POST['method'] ?? 'escrow');
        if ($method !== 'escrow') {
            flash('error', '目前僅支援錢包託管付款');
            redirect('buyer');
        }
        try {
            (new Order())->markPaid($orderId, (int) Auth::user()['id'], $method);
            flash('success', '付款完成，已通知賣家準備交付。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('buyer');
    }

    public function disputeForm(): void
    {
        AuthMiddleware::handle();
        $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0;
        $order = (new Order())->find($orderId);
        if (!$order || (int) $order['buyer_id'] !== (int) Auth::user()['id']) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => '找不到訂單']);
            return;
        }
        if (!in_array($order['status'], ['pending_delivery', 'completed', 'disputed'], true)) {
            flash('error', '此訂單狀態無法提出爭議。');
            redirect('buyer');
        }
        View::render('buyer/dispute', [
            'pageTitle' => '提出爭議',
            'order' => $order,
        ]);
    }

    public function dispute(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('buyer');
        }
        $orderId = filter_var($_POST['order_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if (mb_strlen($reason) < 5) {
            flash('error', '請填寫爭議原因（至少 5 個字）。');
            redirect('buyer');
        }
        try {
            (new Order())->createDispute($orderId, (int) Auth::user()['id'], $reason);
            flash('success', '爭議已提交，管理員將介入處理。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('buyer');
    }

    public function reviewForm(): void
    {
        AuthMiddleware::handle();
        $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT) ?: 0;
        $order = (new Order())->find($orderId);
        if (!$order || (int) $order['buyer_id'] !== (int) Auth::user()['id']) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => '找不到訂單']);
            return;
        }
        if ($order['status'] !== 'completed') {
            flash('error', '訂單完成後才能評價。');
            redirect('buyer');
        }
        View::render('buyer/review', [
            'pageTitle' => '評價交易',
            'order' => $order,
        ]);
    }

    public function review(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('buyer');
        }
        $orderId = filter_var($_POST['order_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($rating < 1 || $rating > 5) {
            flash('error', '請選擇 1–5 星評分。');
            redirect('buyer');
        }
        $order = (new Order())->find($orderId);
        if (!$order || (int) $order['buyer_id'] !== (int) Auth::user()['id']) {
            flash('error', '訂單不存在。');
            redirect('buyer');
        }
        try {
            (new Order())->createReview($orderId, (int) Auth::user()['id'], (int) $order['seller_id'], $rating, $comment);
            flash('success', '評價已送出，感謝你的回饋。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('buyer');
    }
}
