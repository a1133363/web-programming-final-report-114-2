<section class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 3 38 10v15c0 9-5.7 16-14 20C15.7 41 10 34 10 25V10L24 3Z"/><path d="M17 20h14M18.5 27h11M24 14v19"/></svg></span><div><strong>監察後台</strong><small>CONTROL ROOM</small></div></div>
        <nav aria-label="後台功能"><a href="<?= e(url('admin')) ?>">總覽</a><a href="<?= e(url('admin')) ?>#reviews">商品審核</a><a href="<?= e(url('admin')) ?>#reports">報表中心</a><a class="active" href="<?= e(url('admin-disputes')) ?>">爭議處理</a><a href="<?= e(url('admin')) ?>#wanted">通緝名單</a><a href="<?= e(url('admin-logs')) ?>">操作紀錄</a></nav>
        <div class="system-health"><span><i></i> SYSTEM HEALTH</span><strong>98.7%</strong></div>
    </aside>
    <div class="admin-content">
        <div class="admin-topbar"><div><span>CONTROL ROOM / <?= date('Y.m.d') ?></span><h1>爭議處理中心</h1></div></div>
        <section class="dashboard-panel review-panel">
            <div class="panel-heading"><div><span>DISPUTE QUEUE</span><h3>待處理爭議</h3></div><span><?= count($disputes) ?> 件紀錄</span></div>
            <div class="review-list">
                <?php foreach ($disputes as $d): ?>
                    <article>
                        <div class="review-info">
                            <span><?= e($d['order_no']) ?> · <?= e($d['title']) ?></span>
                            <h4>申請人：<?= e($d['creator_name']) ?></h4>
                            <p><?= e($d['reason']) ?></p>
                            <small>狀態：<span class="status-pill"><?= e(status_label($d['status'])) ?></span> <?= $d['admin_name'] ? '· 處理人：' . e($d['admin_name']) : '' ?></small>
                        </div>
                        <?php if (in_array($d['status'], ['open', 'investigating'], true)): ?>
                            <form method="post" action="<?= e(url('admin-dispute-resolve')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="dispute_id" value="<?= (int) $d['id'] ?>">
                                <label><span>裁決結果</span>
                                    <select name="status">
                                        <option value="resolved_buyer">買方勝訴 / 退款</option>
                                        <option value="resolved_seller">賣方勝訴 / 完成</option>
                                        <option value="dismissed">駁回申請</option>
                                    </select>
                                </label>
                                <label><span>裁決說明</span><textarea name="resolution" rows="2" placeholder="簡述裁決理由…" required></textarea></label>
                                <div><button class="button button-small" type="submit">送出裁決</button></div>
                            </form>
                        <?php else: ?>
                            <div class="review-info"><p><strong>裁決：</strong><?= e($d['resolution'] ?? '—') ?></p></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$disputes): ?>
                    <div class="empty-state"><strong>目前沒有爭議紀錄</strong></div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>
