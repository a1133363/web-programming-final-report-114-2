<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Models\Order;
use App\Models\Report;

final class AdminController
{
    public function index(): void
    {
        RoleMiddleware::handle('admin');
        View::render('admin/dashboard', array_merge([
            'pageTitle' => '監察後台',
            'databaseAvailable' => Database::available(),
        ], (new Report())->dashboard()));
    }

    public function review(): never
    {
        RoleMiddleware::handle('admin');
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('admin');
        }
        $auctionId = filter_var($_POST['auction_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $decision = $_POST['decision'] ?? '';
        $risk = in_array($_POST['risk_level'] ?? '', ['low', 'suspicious', 'dangerous', 'prohibited'], true)
            ? $_POST['risk_level'] : 'suspicious';
        $status = $decision === 'approve' ? 'active' : 'rejected';
        $pdo = Database::connection();
        if (!$pdo) {
            flash('error', '示範模式無法執行審核，請先匯入資料庫。');
            redirect('admin');
        }
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare(
                'UPDATE auctions SET status = :status, risk_level = :risk, reviewed_by = :admin_id,
                 reviewed_at = NOW(), rejection_reason = :reason WHERE id = :id'
            );
            $update->execute([
                'status' => $status,
                'risk' => $risk,
                'admin_id' => Auth::user()['id'],
                'reason' => $status === 'rejected' ? trim((string) ($_POST['reason'] ?? '內容不符合刊登規範')) : null,
                'id' => $auctionId,
            ]);
            $log = $pdo->prepare(
                'INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address)
                 VALUES (:admin_id, :action, "auction", :target_id, :details, :ip_address)'
            );
            $log->execute([
                'admin_id' => Auth::user()['id'],
                'action' => 'auction.' . $status,
                'target_id' => $auctionId,
                'details' => json_encode(['risk_level' => $risk], JSON_UNESCAPED_UNICODE),
                'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? @inet_pton($_SERVER['REMOTE_ADDR']) : null,
            ]);
            $pdo->commit();
            flash('success', $status === 'active' ? '拍賣已核准上架。' : '拍賣已退回賣家。');
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', $exception->getMessage());
        }
        redirect('admin');
    }

    public function disputes(): void
    {
        RoleMiddleware::handle('admin');
        View::render('admin/disputes', [
            'pageTitle' => '爭議處理',
            'disputes' => (new Order())->disputes(),
        ]);
    }

    public function resolveDispute(): never
    {
        RoleMiddleware::handle('admin');
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('admin');
        }
        $disputeId = filter_var($_POST['dispute_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $status = in_array($_POST['status'] ?? '', ['resolved_buyer', 'resolved_seller', 'dismissed'], true)
            ? $_POST['status'] : 'dismissed';
        $resolution = trim((string) ($_POST['resolution'] ?? ''));
        try {
            (new Order())->resolveDispute($disputeId, (int) Auth::user()['id'], $resolution, $status);
            flash('success', '爭議已裁決。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('admin');
    }

    public function announce(): never
    {
        RoleMiddleware::handle('admin');
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('admin');
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if (mb_strlen($title) < 3 || mb_strlen($body) < 10) {
            flash('error', '請填寫標題與內文。');
            redirect('admin');
        }
        $pdo = Database::connection();
        if (!$pdo) {
            flash('error', '示範模式無法發布公告，請先匯入資料庫。');
            redirect('admin');
        }
        $statement = $pdo->prepare(
            'INSERT INTO announcements (author_id, title, body, status, published_at)
             VALUES (:author_id, :title, :body, "published", NOW())'
        );
        $statement->execute([
            'author_id' => Auth::user()['id'],
            'title' => $title,
            'body' => $body,
        ]);
        flash('success', '公告已發布。');
        redirect('admin');
    }

    public function logs(): void
    {
        RoleMiddleware::handle('admin');
        $pdo = Database::connection();
        $logs = [];
        if ($pdo) {
            $statement = $pdo->query(
                'SELECT l.*, u.username AS admin_name
                 FROM admin_logs l
                 JOIN users u ON u.id = l.admin_id
                 ORDER BY l.created_at DESC LIMIT 200'
            );
            $logs = $statement->fetchAll();
        }
        View::render('admin/logs', [
            'pageTitle' => '操作紀錄',
            'logs' => $logs,
        ]);
    }
}
