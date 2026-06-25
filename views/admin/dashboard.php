<section class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 3 38 10v15c0 9-5.7 16-14 20C15.7 41 10 34 10 25V10L24 3Z"/><path d="M17 20h14M18.5 27h11M24 14v19"/></svg></span><div><strong>監察後台</strong><small>CONTROL ROOM</small></div></div>
        <nav aria-label="後台功能"><a class="active" href="<?= e(url('admin')) ?>#overview">總覽</a><a href="<?= e(url('admin')) ?>#reviews">商品審核 <b><?= count($pending) ?></b></a><a href="<?= e(url('admin')) ?>#reports">報表中心</a><a href="<?= e(url('admin-disputes')) ?>">爭議處理</a><a href="<?= e(url('admin')) ?>#wanted">通緝名單</a><a href="<?= e(url('admin-logs')) ?>">操作紀錄</a></nav>
        <div class="system-health"><span><i></i> SYSTEM HEALTH</span><strong>98.7%</strong><small><?= $databaseAvailable ? 'MySQL 已連線' : '示範資料模式' ?></small></div>
    </aside>
    <div class="admin-content" id="overview">
        <div class="admin-topbar"><div><span>CONTROL ROOM / <?= date('Y.m.d') ?></span><h1>夜班監察總覽</h1></div><div class="admin-user"><span><?= e(mb_substr(current_user()['username'], 0, 1)) ?></span><div><strong><?= e(current_user()['username']) ?></strong><small>SUPER ADMIN</small></div></div></div>
        <div class="metric-grid admin-metrics">
            <article><span>本月成交總額</span><strong><?= e(money($totals['volume'] ?? 0)) ?></strong><small class="positive">↑ 18.2% 對比上月</small></article>
            <article><span>進行中拍賣</span><strong><?= (int) ($totals['active'] ?? 0) ?></strong><small>12 件將於今晚截標</small></article>
            <article><span>未結爭議</span><strong><?= (int) ($totals['disputes'] ?? 0) ?></strong><small class="warning">2 件超過處理時限</small></article>
            <article class="metric-accent"><span>高風險比例</span><strong><?= e($totals['risk_ratio'] ?? 0) ?>%</strong><small>包含危險與禁止流通</small></article>
        </div>
        <section id="reports" class="admin-chart-grid">
            <article class="chart-panel"><div class="panel-heading"><div><span>VOLUME / 7 DAYS</span><h3>成交金額趨勢</h3></div><div class="panel-actions"><a class="button button-small button-ghost" href="<?= e(url('admin-export', ['format' => 'excel'])) ?>">Excel</a><a class="button button-small button-ghost" href="<?= e(url('admin-export', ['format' => 'pdf'])) ?>">PDF</a></div></div><div class="chart-box"><canvas id="volumeChart" data-values="<?= e(json_encode($daily)) ?>" aria-label="近七日成交金額折線圖"></canvas></div></article>
            <article class="chart-panel"><div class="panel-heading"><div><span>RISK DISTRIBUTION</span><h3>熱門商品分類</h3></div></div><div class="chart-box"><canvas id="categoryChart" data-values="<?= e(json_encode($categories)) ?>" aria-label="商品分類甜甜圈圖"></canvas></div></article>
        </section>
        <section id="reviews" class="dashboard-panel review-panel">
            <div class="panel-heading"><div><span>REVIEW QUEUE</span><h3>待審商品</h3></div><span><?= count($pending) ?> 件等待裁定</span></div>
            <div class="review-list">
                <?php foreach ($pending as $auction): ?>
                    <article><div class="review-thumb"><?php if (!empty($auction['image_path'])): ?><img src="<?= e($auction['image_path']) ?>" alt=""><?php else: ?><span><?= e($auction['lot_no'] ?? 'NEW') ?></span><?php endif; ?></div><div class="review-info"><span><?= e($auction['category_name'] ?? '待分類') ?> · <?= e($auction['seller_name'] ?? '未知賣家') ?></span><h4><?= e($auction['title']) ?></h4><p><?= e(mb_substr($auction['description'] ?? '', 0, 70)) ?>…</p></div><form method="post" action="<?= e(url('admin-review')) ?>"><?= csrf_field() ?><input type="hidden" name="auction_id" value="<?= (int) $auction['id'] ?>"><label><span>最終風險</span><select name="risk_level"><option value="low">低風險</option><option value="suspicious" selected>可疑</option><option value="dangerous">危險</option><option value="prohibited">禁止流通</option></select></label><div><button class="button button-small" name="decision" value="approve">核准</button><button class="button-danger" name="decision" value="reject">退回</button></div></form></article>
                <?php endforeach; ?>
            </div>
        </section>
        <section id="wanted" class="dashboard-panel wanted-admin">
            <div class="panel-heading"><div><span>WATCH / WANTED</span><h3>高風險帳號</h3></div><a href="<?= e(url('wanted')) ?>">公開名冊 →</a></div>
            <?php foreach ($wanted as $item): ?><div class="wanted-row"><span class="wanted-dot level-<?= e($item['level']) ?>"></span><strong><?= e($item['username']) ?></strong><p><?= e($item['reason']) ?></p><span><?= e(strtoupper($item['level'])) ?></span></div><?php endforeach; ?>
        </section>
        <section id="announcements" class="dashboard-panel">
            <div class="panel-heading"><div><span>BROADCAST</span><h3>發布公告</h3></div></div>
            <form method="post" action="<?= e(url('admin-announce')) ?>" class="stack-form">
                <?= csrf_field() ?>
                <label><span>標題</span><input type="text" name="title" minlength="3" maxlength="120" placeholder="例：第六夜場風險協議更新" required></label>
                <label><span>內文</span><textarea name="body" rows="3" minlength="10" placeholder="公告內容…" required></textarea></label>
                <button class="button button-small" type="submit">發布公告</button>
            </form>
        </section>
    </div>
</section>
