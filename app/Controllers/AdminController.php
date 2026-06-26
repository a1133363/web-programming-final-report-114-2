<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Models\Auction;
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

    public function export(): never
    {
        RoleMiddleware::handle('admin');
        $format = strtolower((string) ($_GET['format'] ?? 'excel'));
        $rows = $this->reportRows((new Report())->dashboard());

        if ($format === 'pdf') {
            $this->downloadPdf($rows);
        }
        $this->downloadExcel($rows);
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

    public function deleteAuction(): never
    {
        RoleMiddleware::handle('admin');
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('admin');
        }
        $auctionId = filter_var($_POST['auction_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $pdo = Database::connection();
        if (!$pdo) {
            flash('error', '示範模式無法刪除，請先匯入資料庫。');
            redirect('admin');
        }
        try {
            (new Auction())->delete($auctionId, (int) Auth::user()['id'], true);
            $log = $pdo->prepare(
                'INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address)
                 VALUES (:admin_id, :action, "auction", :target_id, :details, :ip_address)'
            );
            $log->execute([
                'admin_id' => Auth::user()['id'],
                'action' => 'auction.delete',
                'target_id' => $auctionId,
                'details' => json_encode(['source' => 'admin_dashboard'], JSON_UNESCAPED_UNICODE),
                'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? @inet_pton($_SERVER['REMOTE_ADDR']) : null,
            ]);
            flash('success', '拍賣品已刪除。');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('admin');
    }

    private function reportRows(array $report): array
    {
        $totals = $report['totals'] ?? [];
        $rows = [
            ['項目', '數值'],
            ['匯出時間', date('Y-m-d H:i:s')],
            ['本月成交總額', money($totals['volume'] ?? 0)],
            ['進行中拍賣', (string) (int) ($totals['active'] ?? 0)],
            ['未結爭議', (string) (int) ($totals['disputes'] ?? 0)],
            ['高風險比例', (string) ($totals['risk_ratio'] ?? 0) . '%'],
            ['待審商品', (string) count($report['pending'] ?? [])],
            ['高風險帳號', (string) count($report['wanted'] ?? [])],
        ];

        foreach (($report['daily'] ?? []) as $index => $amount) {
            $rows[] = [date('m/d', strtotime('-' . (6 - (int) $index) . ' day')) . ' 成交額', money($amount)];
        }

        return $rows;
    }

    private function downloadExcel(array $rows): never
    {
        $filename = 'nocturne-report-' . date('Ymd-His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo '<table border="1">';
        foreach ($rows as $row) {
            echo '<tr><td>' . e($row[0]) . '</td><td>' . e($row[1]) . '</td></tr>';
        }
        echo '</table>';
        exit;
    }

    private function downloadPdf(array $rows): never
    {
        $lines = ['NOCTURNE 暗標局報表'];
        foreach ($rows as $row) {
            $lines[] = $row[0] . '：' . $row[1];
        }
        $pdf = $this->makePdf($lines);
        $filename = 'nocturne-report-' . date('Ymd-His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function makePdf(array $lines): string
    {
        $content = "BT\n/F1 16 Tf\n50 790 Td\n";
        foreach (array_slice($lines, 0, 28) as $index => $line) {
            if ($index > 0) {
                $content .= "0 -26 Td\n";
            }
            $content .= '<' . $this->pdfText($line) . "> Tj\n";
        }
        $content .= "ET\n";

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type0 /BaseFont /MSung-Light /Encoding /UniCNS-UTF16-H /DescendantFonts [6 0 R] >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n",
            "6 0 obj\n<< /Type /Font /Subtype /CIDFontType0 /BaseFont /MSung-Light /CIDSystemInfo << /Registry (Adobe) /Ordering (CNS1) /Supplement 5 >> /FontDescriptor 7 0 R >>\nendobj\n",
            "7 0 obj\n<< /Type /FontDescriptor /FontName /MSung-Light /Flags 6 /FontBBox [0 -160 1000 880] /ItalicAngle 0 /Ascent 880 /Descent -160 /CapHeight 700 /StemV 80 >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    }

    private function pdfText(string $text): string
    {
        return strtoupper(bin2hex(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')));
    }
}
