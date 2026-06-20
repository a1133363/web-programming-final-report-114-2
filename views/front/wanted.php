<section class="page-hero page-hero-wanted">
    <span class="section-code">PUBLIC NOTICE / WANTED</span>
    <h1>黑市通緝名冊</h1>
    <p>列出反覆違反付款、交付或刊登規則的虛構帳號。此頁不公開真實個人資料。</p>
</section>
<section class="section wanted-list">
    <?php foreach ($wanted as $index => $item): ?>
        <article class="wanted-card">
            <span class="wanted-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div><small>ACCOUNT ALIAS</small><h2><?= e($item['username']) ?></h2><p><?= e($item['reason']) ?></p></div>
            <div class="wanted-level level-<?= e($item['level']) ?>"><span>警戒等級</span><strong><?= e(strtoupper($item['level'])) ?></strong></div>
            <time datetime="<?= e($item['reported_at']) ?>">列管 <?= e($item['reported_at']) ?></time>
        </article>
    <?php endforeach; ?>
    <div class="wanted-note"><strong>名冊處理原則</strong><p>管理員需保存證據、處理申訴，且所有新增、調整與解除列管操作都會寫入 <code>admin_logs</code>。</p></div>
</section>
