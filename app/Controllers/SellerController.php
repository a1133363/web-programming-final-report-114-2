<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Models\Auction;
use App\Services\AiDescriptionService;
use App\Services\RiskService;

final class SellerController
{
    public function index(): void
    {
        RoleMiddleware::handle('seller', 'admin');
        $model = new Auction();
        View::render('seller/dashboard', [
            'pageTitle' => '賣家控制室',
            'auctions' => $model->sellerAuctions((int) Auth::user()['id']),
            'categories' => $model->categories(),
        ]);
    }

    public function create(): never
    {
        RoleMiddleware::handle('seller', 'admin');
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新送出。');
            redirect('seller');
        }
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'category_id' => filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT),
            'starting_price' => filter_var($_POST['starting_price'] ?? 0, FILTER_VALIDATE_FLOAT),
            'reserve_price' => filter_var($_POST['reserve_price'] ?? 0, FILTER_VALIDATE_FLOAT),
            'min_increment' => filter_var($_POST['min_increment'] ?? 0, FILTER_VALIDATE_FLOAT),
            'start_at' => str_replace('T', ' ', (string) ($_POST['start_at'] ?? '')),
            'end_at' => str_replace('T', ' ', (string) ($_POST['end_at'] ?? '')),
        ];
        if (mb_strlen($data['title']) < 4 || mb_strlen($data['description']) < 20 || !$data['category_id']
            || !$data['starting_price'] || !$data['min_increment'] || strtotime($data['end_at']) <= strtotime($data['start_at'])) {
            flash('error', '請完整填寫商品、價格與有效的拍賣期間。');
            redirect('seller');
        }
        $risk = (new RiskService())->suggest($data['title'], $data['description'], (int) (Auth::user()['credit_score'] ?? 80));
        $data['risk_level'] = $risk['level'];
        $data['ai_risk_suggestion'] = json_encode($risk, JSON_UNESCAPED_UNICODE);

        try {
            $auctionId = (new Auction())->create((int) Auth::user()['id'], $data);
            $this->storeImage($auctionId);
            flash('success', '拍賣品已送交監察員審核；AI 建議風險為「' . risk_label($risk['level']) . '」。');
        } catch (\Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('seller');
    }

    public function aiDescription(): never
    {
        RoleMiddleware::handle('seller', 'admin');
        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo json_encode(['error' => '表單已過期'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'description' => (new AiDescriptionService())->generate((string) ($_POST['keywords'] ?? '')),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function storeImage(int $auctionId): void
    {
        if (empty($_FILES['image']['tmp_name']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
            return;
        }
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        if ((int) $_FILES['image']['size'] > $config['max_upload_size']) {
            throw new \RuntimeException('圖片不可超過 5 MB。');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
        if (!in_array($mime, $config['allowed_mime_types'], true)) {
            throw new \RuntimeException('僅接受 JPG、PNG 或 WebP 圖片。');
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $target = $config['upload_dir'] . '/' . $filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            throw new \RuntimeException('圖片儲存失敗。');
        }
        $pdo = Database::connection();
        $statement = $pdo?->prepare(
            'INSERT INTO auction_images (auction_id, file_path, is_cover) VALUES (:auction_id, :file_path, 1)'
        );
        $statement?->execute([
            'auction_id' => $auctionId,
            'file_path' => 'index.php?page=upload&file=' . rawurlencode($filename),
        ]);
    }
}
