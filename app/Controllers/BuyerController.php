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
use App\Models\User;
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
        $order = (new Order())->find($orderId);
        if (!$order || (int) $order['buyer_id'] !== (int) Auth::user()['id']) {
            flash('error', '訂單不存在。');
            redirect('buyer');
        }
        if (!in_array($order['status'], ['pending_delivery', 'completed', 'disputed'], true)) {
            flash('error', '此訂單狀態無法提出爭議。');
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
        if ($order['status'] !== 'completed') {
            flash('error', '訂單完成後才能評價。');
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

    public function profileForm(): void
    {
        AuthMiddleware::handle();
        $user = (new User())->findWithRoles((int) Auth::user()['id']);
        View::render('buyer/profile', [
            'pageTitle' => '帳號管理',
            'profile' => $user,
        ]);
    }

    public function updateProfile(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('buyer-profile');
        }
        $userId = (int) Auth::user()['id'];
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        if (mb_strlen($username) < 2 || mb_strlen($username) > 40) {
            flash('error', '匿名代號需為 2–40 個字元。');
            redirect('buyer-profile');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', '電子信箱格式不正確。');
            redirect('buyer-profile');
        }
        $userModel = new User();
        $existing = $userModel->findByEmail($email);
        if ($existing && (int) $existing['id'] !== $userId) {
            flash('error', '此電子信箱已被使用。');
            redirect('buyer-profile');
        }
        $existingName = $userModel->findByUsername($username);
        if ($existingName && (int) $existingName['id'] !== $userId) {
            flash('error', '此匿名代號已被使用。');
            redirect('buyer-profile');
        }
        $avatarPath = $this->handleAvatar($userId);
        try {
            $userModel->updateProfile($userId, $username, $email, $avatarPath);
            Auth::refresh($userId);
            flash('success', '帳號資料已更新。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('buyer-profile');
    }

    public function updatePassword(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('buyer-profile');
        }
        $userId = (int) Auth::user()['id'];
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($new !== $confirm) {
            flash('error', '新密碼與確認密碼不一致。');
            redirect('buyer-profile');
        }
        try {
            (new User())->updatePassword($userId, $current, $new);
            flash('success', '密碼已更新。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('buyer-profile');
    }

    public function deleteAccount(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('buyer-profile');
        }
        $confirmText = trim((string) ($_POST['confirm_text'] ?? ''));
        if ($confirmText !== 'DELETE') {
            flash('error', '請輸入 DELETE 以確認刪除帳號。');
            redirect('buyer-profile');
        }
        $userId = (int) Auth::user()['id'];
        try {
            (new User())->deleteAccount($userId);
            Auth::logout();
            flash('success', '帳號已刪除，感謝你使用。');
            redirect('home');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('buyer-profile');
        }
    }

    private function handleAvatar(int $userId): ?string
    {
        if (empty($_FILES['avatar']['tmp_name']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            return null;
        }
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        if ((int) $_FILES['avatar']['size'] > $config['max_upload_size']) {
            throw new \RuntimeException('頭像不可超過 5 MB。');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);
        if (!in_array($mime, $config['allowed_mime_types'], true)) {
            throw new \RuntimeException('僅接受 JPG、PNG 或 WebP 圖片。');
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $filename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $uploadDir = $config['upload_dir'];
        if (!is_dir($uploadDir)) {
            $oldUmask = umask(0);
            @mkdir($uploadDir, 0775, true);
            umask($oldUmask);
        }
        $target = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            throw new \RuntimeException('頭像儲存失敗。');
        }
        return 'index.php?page=upload&file=' . rawurlencode($filename);
    }
}
