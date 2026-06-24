<section class="hero">
    <div class="hero-backdrop" aria-hidden="true"></div>
    <div class="hero-copy">
        <div class="eyebrow"><span>PRIVATE CATALOG / 114-2</span><span>第 06 夜場</span></div>
        <p class="hero-kicker">虛構物品競標與信任交易系統</p>
        <h1>只競標<br><em>不存在</em>的危險。</h1>
        <p class="hero-lede">古代遺物、異星零件與失落情報，在監察員審核與信用協議之下，找到下一位保管人。</p>
        <div class="hero-actions">
            <a class="button" href="#live-auctions">進入本夜拍賣</a>
            <a class="text-link" href="<?= e(url('wanted')) ?>">查閱風險通告 <span aria-hidden="true">↗</span></a>
        </div>
    </div>
    <div class="hero-lot" aria-hidden="true">
        <span>FEATURED LOT</span>
        <strong>N—013</strong>
        <small>RISK / DANGEROUS</small>
    </div>
    <div class="hero-status">
        <span><i class="status-dot"></i> 暗場連線正常</span>
        <span>132 件競標中</span>
        <span>最後同步 <?= date('H:i') ?></span>
    </div>
</section>

<?php if (!empty($announcements)): ?>
<section class="section" aria-label="最新公告">
    <div class="announcement-bar">
        <?php foreach ($announcements as $ann): ?>
            <article class="announcement-card">
                <span class="section-code">BROADCAST</span>
                <h3><?= e($ann['title']) ?></h3>
                <p><?= e($ann['body']) ?></p>
                <time datetime="<?= e($ann['published_at']) ?>"><?= e(date('Y.m.d', strtotime($ann['published_at']))) ?></time>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="search-ledger" aria-labelledby="search-title">
    <div class="section-heading compact">
        <div><span class="section-code">FIND / 01</span><h2 id="search-title">搜尋封存目錄</h2></div>
        <p>名稱、分類、風險與賣家均可交叉查詢。</p>
    </div>
    <form class="search-form" method="get" action="index.php">
        <input type="hidden" name="page" value="home">
        <label class="search-main">
            <span>關鍵字</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="例：月面、情報、灰鴉收藏室">
        </label>
        <label><span>商品分類</span>
            <select name="category">
                <option value="">全部分類</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>" <?= (string) $filters['category'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>風險等級</span>
            <select name="risk">
                <option value="">全部風險</option>
                <option value="low" <?= $filters['risk'] === 'low' ? 'selected' : '' ?>>低風險</option>
                <option value="suspicious" <?= $filters['risk'] === 'suspicious' ? 'selected' : '' ?>>可疑</option>
                <option value="dangerous" <?= $filters['risk'] === 'dangerous' ? 'selected' : '' ?>>危險</option>
            </select>
        </label>
        <button class="button button-search" type="submit">搜尋目錄</button>
        <details class="search-extra" <?= ($filters['min_price'] || $filters['max_price'] || $filters['ending']) ? 'open' : '' ?>>
            <summary>進階條件：價格區間與剩餘時間</summary>
            <div>
                <label><span>最低價格</span><input type="number" name="min_price" min="0" step="1000" value="<?= e($filters['min_price']) ?>" placeholder="不限"></label>
                <label><span>最高價格</span><input type="number" name="max_price" min="0" step="1000" value="<?= e($filters['max_price']) ?>" placeholder="不限"></label>
                <label><span>截標時間</span><select name="ending"><option value="">不限</option><option value="6" <?= $filters['ending'] === '6' ? 'selected' : '' ?>>6 小時內</option><option value="12" <?= $filters['ending'] === '12' ? 'selected' : '' ?>>12 小時內</option><option value="24" <?= $filters['ending'] === '24' ? 'selected' : '' ?>>24 小時內</option><option value="72" <?= $filters['ending'] === '72' ? 'selected' : '' ?>>3 天內</option></select></label>
                <a href="<?= e(url('home')) ?>">清除所有條件</a>
            </div>
        </details>
    </form>
</section>

<section id="live-auctions" class="section auctions-section">
    <div class="section-heading">
        <div><span class="section-code">LIVE / 02</span><h2>正在競標</h2></div>
        <p><?= count($auctions) ?> 件符合條件的封存物</p>
    </div>
    <?php if (!$databaseAvailable): ?>
        <div class="demo-banner"><span>DEMO</span>目前顯示示範資料；匯入 <code>database/schema.sql</code> 與 <code>database/seed.sql</code> 後即可啟用真實交易。</div>
    <?php endif; ?>
    <div class="auction-grid">
        <?php foreach ($auctions as $index => $auction): ?>
            <article class="auction-card <?= $index === 0 ? 'auction-card-featured' : '' ?>">
                <a class="auction-image" href="<?= e(url('auction', ['id' => $auction['id']])) ?>" aria-label="查看 <?= e($auction['title']) ?>">
                    <img src="<?= e($auction['image_path']) ?>" alt="<?= e($auction['title']) ?> 商品影像" loading="<?= $index < 2 ? 'eager' : 'lazy' ?>">
                    <span class="risk-badge risk-<?= e($auction['risk_level']) ?>"><i></i><?= e(risk_label($auction['risk_level'])) ?></span>
                    <span class="lot-stamp"><?= e($auction['lot_no']) ?></span>
                </a>
                <div class="auction-card-body">
                    <div class="auction-category"><span><?= e($auction['category_name']) ?></span><span><?= (int) $auction['bid_count'] ?> 次出價</span></div>
                    <h3><a href="<?= e(url('auction', ['id' => $auction['id']])) ?>"><?= e($auction['title']) ?></a></h3>
                    <div class="auction-meta">
                        <div><span>目前最高價</span><strong><?= e(money($auction['current_price'])) ?></strong></div>
                        <div class="countdown" data-countdown="<?= e(date(DATE_ATOM, strtotime($auction['end_at']))) ?>">
                            <span>距離截標</span><strong>--:--:--</strong>
                        </div>
                    </div>
                    <div class="seller-line"><span class="verified" aria-label="已驗證賣家">✓</span><?= e($auction['seller_name']) ?><span>信用 <?= (int) $auction['seller_credit'] ?></span></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$auctions): ?>
        <div class="empty-state"><strong>沒有符合條件的拍賣。</strong><p>移除部分篩選條件後再試一次。</p></div>
    <?php endif; ?>
</section>

<section class="section category-section">
    <div class="section-heading">
        <div><span class="section-code">INDEX / 03</span><h2>依來源查閱</h2></div>
        <p>每個分類都有不同的預設審核強度。</p>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a href="<?= e(url('home', ['category' => $category['id']])) ?>" class="category-card">
                <span class="category-code"><?= e($category['code'] ?? 'ARC') ?></span>
                <div><h3><?= e($category['name']) ?></h3><p><?= (int) $category['count'] ?> 件封存物</p></div>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="trust-section">
    <div class="trust-intro">
        <span class="section-code">PROTOCOL / 04</span>
        <h2>黑市不代表<br>沒有規則。</h2>
        <p>每一次上架、出價與裁決都留下可追溯紀錄。風險不被隱藏，而是被清楚標示。</p>
        <a class="text-link" href="<?= e(url('register')) ?>">閱讀席位協議 <span aria-hidden="true">→</span></a>
    </div>
    <div class="protocol-list">
        <article><span>01</span><div><h3>人工審核</h3><p>商品由監察員設定最終風險等級，AI 僅提供建議。</p></div></article>
        <article><span>02</span><div><h3>代理出價</h3><p>只在必要時依最低加價幅度競價，不直接揭露你的上限。</p></div></article>
        <article><span>03</span><div><h3>信用軌跡</h3><p>付款、交付與爭議結果共同形成會員信用分數。</p></div></article>
        <article><span>04</span><div><h3>完整稽核</h3><p>所有後台操作寫入紀錄，避免直接修改資料庫而失去軌跡。</p></div></article>
    </div>
</section>
