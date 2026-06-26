<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Auction;
use App\Services\BidService;

final class AuctionController
{
    public function show(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
        $model = new Auction();
        $auction = $model->find($id);
        if (!$auction) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => '找不到拍賣品']);
            return;
        }

        View::render('front/auction', [
            'pageTitle' => $auction['title'],
            'auction' => $auction,
            'bids' => $model->bids($id),
            'related' => array_values(array_filter(
                $model->featured(),
                static fn (array $item): bool => (int) $item['id'] !== $id
            )),
        ]);
    }

    public function bid(): never
    {
        AuthMiddleware::handle();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新送出。');
            redirect('auction', ['id' => (int) ($_POST['auction_id'] ?? 0)]);
        }

        $auctionId = filter_var($_POST['auction_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $proxyMax = ($_POST['proxy_max'] ?? '') !== ''
            ? filter_var($_POST['proxy_max'], FILTER_VALIDATE_FLOAT)
            : null;
        if (!$auctionId || $amount === false || $amount <= 0 || $proxyMax === false) {
            flash('error', '請輸入有效的出價金額。');
            redirect('auction', ['id' => $auctionId]);
        }

        try {
            (new BidService())->place($auctionId, (int) Auth::user()['id'], (float) $amount, $proxyMax === null ? null : (float) $proxyMax);
            flash('success', '出價已登記，系統已重新計算最高價。');
        } catch (\Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('auction', ['id' => $auctionId]);
    }

    public function watch(): never
    {
        AuthMiddleware::handle();
        $auctionId = filter_var($_POST['auction_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            flash('error', '表單已過期，請重新操作。');
            redirect('auction', ['id' => $auctionId]);
        }

        $pdo = \App\Core\Database::connection();
        if (!$pdo) {
            flash('error', '資料庫連線失敗，請稍後再試。');
        } else {
            $statement = $pdo->prepare(
                'INSERT IGNORE INTO watchlists (user_id, auction_id) VALUES (:user_id, :auction_id)'
            );
            $statement->execute(['user_id' => Auth::user()['id'], 'auction_id' => $auctionId]);
            flash('success', '已加入你的監看名冊。');
        }
        redirect('auction', ['id' => $auctionId]);
    }
}
