<section class="catalog-shell">
    <header class="catalog-header">
        <div>
            <span class="section-code"><i class="status-dot"></i>LIVE MARKET</span>
            <h1>探索拍品</h1>
            <p>瀏覽 <?= count($auctions) ?> 件競標中的虛構物件。登入後即可出價與收藏。</p>
        </div>
        <?php if (!current_user()): ?>
            <a class="button button-small" href="<?= e(url('login')) ?>">登入參與競標</a>
        <?php endif; ?>
    </header>

    <form class="catalog-search" method="get" action="index.php">
        <input type="hidden" name="page" value="home">
        <label class="search-main">
            <span>搜尋拍品</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="搜尋名稱、描述或賣家">
        </label>
        <label><span>分類</span>
            <select name="category">
                <option value="">全部分類</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>" <?= (string) $filters['category'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>風險</span>
            <select name="risk">
                <option value="">全部風險</option>
                <option value="low" <?= $filters['risk'] === 'low' ? 'selected' : '' ?>>低風險</option>
                <option value="suspicious" <?= $filters['risk'] === 'suspicious' ? 'selected' : '' ?>>可疑</option>
                <option value="dangerous" <?= $filters['risk'] === 'dangerous' ? 'selected' : '' ?>>危險</option>
            </select>
        </label>
        <button class="button button-search" type="submit">套用</button>
        <details class="search-extra" <?= ($filters['min_price'] || $filters['max_price'] || $filters['ending']) ? 'open' : '' ?>>
            <summary>價格與截標時間</summary>
            <div>
                <label><span>最低價格</span><input type="number" name="min_price" min="0" step="1000" value="<?= e($filters['min_price']) ?>" placeholder="不限"></label>
                <label><span>最高價格</span><input type="number" name="max_price" min="0" step="1000" value="<?= e($filters['max_price']) ?>" placeholder="不限"></label>
                <label><span>截標時間</span><select name="ending"><option value="">不限</option><option value="6" <?= $filters['ending'] === '6' ? 'selected' : '' ?>>6 小時內</option><option value="12" <?= $filters['ending'] === '12' ? 'selected' : '' ?>>12 小時內</option><option value="24" <?= $filters['ending'] === '24' ? 'selected' : '' ?>>24 小時內</option><option value="72" <?= $filters['ending'] === '72' ? 'selected' : '' ?>>3 天內</option></select></label>
                <a href="<?= e(url('home')) ?>">清除篩選</a>
            </div>
        </details>
    </form>

    <nav class="category-tabs" aria-label="商品分類">
        <a href="<?= e(url('home')) ?>" class="<?= $filters['category'] === '' ? 'active' : '' ?>">全部</a>
        <?php foreach ($categories as $category): ?>
            <a href="<?= e(url('home', ['category' => $category['id']])) ?>" class="<?= (string) $filters['category'] === (string) $category['id'] ? 'active' : '' ?>"><?= e($category['name']) ?><span><?= (int) $category['count'] ?></span></a>
        <?php endforeach; ?>
    </nav>

    <div class="catalog-result-bar">
        <strong><?= count($auctions) ?> 件拍品</strong>
        <span>依精選與截標時間排序</span>
    </div>

    <div class="auction-grid">
        <?php foreach ($auctions as $index => $auction): ?>
            <article class="auction-card">
                <a class="auction-image" href="<?= e(url('auction', ['id' => $auction['id']])) ?>" aria-label="查看 <?= e($auction['title']) ?>">
                    <img src="<?= e($auction['image_path']) ?>" alt="<?= e($auction['title']) ?> 商品影像" loading="<?= $index < 4 ? 'eager' : 'lazy' ?>">
                    <span class="risk-badge risk-<?= e($auction['risk_level']) ?>"><i></i><?= e(risk_label($auction['risk_level'])) ?></span>
                    <span class="lot-stamp"><?= e($auction['lot_no']) ?></span>
                </a>
                <div class="auction-card-body">
                    <div class="auction-category"><span><?= e($auction['category_name']) ?></span><span><?= (int) $auction['bid_count'] ?> 次出價</span></div>
                    <h2><a href="<?= e(url('auction', ['id' => $auction['id']])) ?>"><?= e($auction['title']) ?></a></h2>
                    <div class="auction-meta">
                        <div><span>目前最高價</span><strong><?= e(money($auction['current_price'])) ?></strong></div>
                        <div class="countdown" data-countdown="<?= e(date(DATE_ATOM, strtotime($auction['end_at']))) ?>"><span>距離截標</span><strong>--:--:--</strong></div>
                    </div>
                    <div class="seller-line"><span class="verified" aria-label="已驗證賣家">✓</span><?= e($auction['seller_name']) ?><span>信用 <?= (int) $auction['seller_credit'] ?></span></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!$auctions): ?>
        <div class="empty-state"><strong>找不到符合條件的拍品</strong><p>清除部分篩選條件後再試一次。</p><a class="button button-small" href="<?= e(url('home')) ?>">清除篩選</a></div>
    <?php endif; ?>
</section>
