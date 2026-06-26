<section class="lot-header">
    <a class="back-link" href="<?= e(url('home')) ?>">← 返回拍賣目錄</a>
    <div class="lot-breadcrumb"><?= e($auction['category_name']) ?> / <?= e($auction['lot_no']) ?></div>
</section>

<section class="lot-layout">
    <div class="lot-gallery">
        <div class="lot-main-image">
            <img src="<?= e($auction['image_path']) ?>" alt="<?= e($auction['title']) ?> 的商品照片">
            <span class="risk-badge risk-<?= e($auction['risk_level']) ?>"><i></i><?= e(risk_label($auction['risk_level'])) ?></span>
        </div>
        <div class="image-note"><span>檔案影像 / 01</span><span>已完成 MIME 安全檢核</span></div>
    </div>

    <div class="lot-details">
        <span class="lot-number">LOT <?= e($auction['lot_no']) ?></span>
        <h1><?= e($auction['title']) ?></h1>
        <p class="lot-description"><?= e($auction['description']) ?></p>
        <div class="seller-panel">
            <div class="seller-avatar" aria-hidden="true"><?= e(mb_substr($auction['seller_name'], 0, 1)) ?></div>
            <div><span>委託賣家</span><strong><?= e($auction['seller_name']) ?></strong></div>
            <div class="credit-meter"><span>信用 <?= (int) $auction['seller_credit'] ?></span><i><b style="width: <?= min(100, (int) $auction['seller_credit']) ?>%"></b></i></div>
        </div>
        <div class="bid-panel">
            <div class="current-bid">
                <span>目前最高出價</span>
                <strong><?= e(money($auction['current_price'])) ?></strong>
                <small><?= (int) $auction['bid_count'] ?> 次出價 · 最低加價 <?= e(money($auction['min_increment'])) ?></small>
            </div>
            <div class="countdown countdown-large" data-countdown="<?= e(date(DATE_ATOM, strtotime($auction['end_at']))) ?>">
                <span>截標倒數</span><strong>--:--:--</strong><small><?= e(date('Y.m.d H:i', strtotime($auction['end_at']))) ?></small>
            </div>
            <?php if (current_user()): ?>
                <form class="bid-form" method="post" action="<?= e(url('bid')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="auction_id" value="<?= (int) $auction['id'] ?>">
                    <label><span>本次出價</span><input type="number" name="amount" min="<?= (float) $auction['current_price'] + (float) $auction['min_increment'] ?>" step="<?= (float) $auction['min_increment'] ?>" value="<?= (float) $auction['current_price'] + (float) $auction['min_increment'] ?>" required></label>
                    <label><span>代理出價上限 <small>選填，以萬為單位</small></span><input type="number" name="proxy_max" min="<?= (float) $auction['current_price'] + (float) $auction['min_increment'] ?>" step="10000" placeholder="例：200000"></label>
                    <button class="button button-full" type="submit">確認出價</button>
                </form>
                <form class="watch-form" method="post" action="<?= e(url('watch')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="auction_id" value="<?= (int) $auction['id'] ?>">
                    <button type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 20-1.4-1.3C5.4 14 2 10.9 2 7.1 2 4 4.4 2 7.3 2c1.7 0 3.4.8 4.7 2.1C13.3 2.8 15 2 16.7 2 19.6 2 22 4 22 7.1c0 3.8-3.4 6.9-8.6 11.6L12 20Z"/></svg>加入監看名冊</button>
                </form>
            <?php else: ?>
                <a class="button button-full" href="<?= e(url('login')) ?>">登入後參與競標</a>
                <p class="form-note">尚無席位？<a href="<?= e(url('register')) ?>">建立匿名席位</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="lot-lower section">
    <div class="lot-facts">
        <div class="section-heading compact"><div><span class="section-code">DOSSIER</span><h2>物件檔案</h2></div></div>
        <dl>
            <div><dt>分類</dt><dd><?= e($auction['category_name']) ?></dd></div>
            <div><dt>起標價格</dt><dd><?= e(money($auction['starting_price'])) ?></dd></div>
            <div><dt>風險標示</dt><dd><?= e(risk_label($auction['risk_level'])) ?></dd></div>
            <div><dt>交易保護</dt><dd>爭議裁決 / 操作稽核</dd></div>
        </dl>
        <div class="risk-callout risk-<?= e($auction['risk_level']) ?>"><strong>風險說明</strong><p>此等級依商品描述、分類與賣家信用初步計算，最終由管理員審核。所有異常效應均屬虛構設定。</p></div>
    </div>
    <div class="bid-history">
        <div class="section-heading compact"><div><span class="section-code">LEDGER</span><h2>公開出價紀錄</h2></div><p>代理上限不公開</p></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>席位</th><th>類型</th><th>出價</th><th>時間</th></tr></thead>
                <tbody>
                <?php foreach ($bids as $bid): ?>
                    <tr><td><?= e($bid['username']) ?></td><td><span class="type-tag"><?= $bid['is_auto'] ? '代理' : '手動' ?></span></td><td><?= e(money($bid['bid_amount'])) ?></td><td><?= e(date('m/d H:i', strtotime($bid['created_at']))) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="section related-section">
    <div class="section-heading"><div><span class="section-code">NEXT LOTS</span><h2>接續拍品</h2></div></div>
    <div class="related-row">
        <?php foreach (array_slice($related, 0, 3) as $item): ?>
            <a class="related-card" href="<?= e(url('auction', ['id' => $item['id']])) ?>">
                <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" loading="lazy"><div><span><?= e($item['lot_no']) ?></span><h3><?= e($item['title']) ?></h3><strong><?= e(money($item['current_price'])) ?></strong></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
