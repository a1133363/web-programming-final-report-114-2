<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Middleware\RoleMiddleware;
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
}
