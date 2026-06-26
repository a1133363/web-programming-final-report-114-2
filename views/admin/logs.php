<section class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 3 38 10v15c0 9-5.7 16-14 20C15.7 41 10 34 10 25V10L24 3Z"/><path d="M17 20h14M18.5 27h11M24 14v19"/></svg></span><div><strong>監察後台</strong><small>CONTROL ROOM</small></div></div>
        <nav aria-label="後台功能"><a href="<?= e(url('admin')) ?>">總覽</a><a href="<?= e(url('admin')) ?>#reviews">商品審核</a><a href="<?= e(url('admin')) ?>#reports">報表中心</a><a href="<?= e(url('admin-disputes')) ?>">爭議處理</a><a href="<?= e(url('admin')) ?>#wanted">通緝名單</a><a class="active" href="<?= e(url('admin-logs')) ?>">操作紀錄</a></nav>
        <div class="system-health"><span><i></i> SYSTEM HEALTH</span><strong>98.7%</strong></div>
    </aside>
    <div class="admin-content">
        <div class="admin-topbar"><div><span>CONTROL ROOM / <?= date('Y.m.d') ?></span><h1>操作稽核紀錄</h1></div></div>
        <section class="dashboard-panel">
            <div class="panel-heading"><div><span>AUDIT LOG</span><h3>最近 200 筆後台操作</h3></div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>時間</th><th>管理員</th><th>動作</th><th>目標類型</th><th>目標 ID</th><th>詳細資訊</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= e(date('m/d H:i', strtotime($log['created_at']))) ?></td>
                                <td><?= e($log['admin_name']) ?></td>
                                <td><code><?= e($log['action']) ?></code></td>
                                <td><?= e($log['target_type']) ?></td>
                                <td><?= (int) ($log['target_id'] ?? 0) ?></td>
                                <td><small><?= e(mb_substr((string) json_encode($log['details'] ?? ''), 0, 60)) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?>
                            <tr><td colspan="6" class="empty-state">尚無操作紀錄，或目前處於示範模式</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
