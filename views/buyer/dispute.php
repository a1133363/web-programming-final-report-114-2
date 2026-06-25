<section class="auth-shell">
    <div class="auth-aside">
        <span class="section-code">DISPUTE / RAISE</span>
        <h1>提出<br>交易爭議。</h1>
        <p>若交付內容與描述不符、賣家未出貨或其他違規行為，可在此提交爭議申請，管理員將介入調查。</p>
    </div>
    <div class="auth-card">
        <div class="auth-heading"><span>DISPUTE FORM</span><h2>爭議申請</h2><p><?= e($order['order_no'] ?? '#'.$order['id']) ?> · <?= e($order['title']) ?></p></div>
        <form method="post" action="<?= e(url('buyer-dispute-submit')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <label><span>爭議原因</span><textarea name="reason" rows="5" minlength="5" placeholder="請描述問題，例如：收到空箱、與描述嚴重不符、賣家失聯…" required></textarea></label>
            <p class="form-note">提交後訂單狀態將變更為「爭議中」，管理員可能聯繫你取得更多證據。</p>
            <button class="button button-full" type="submit">提交爭議</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('buyer')) ?>">← 返回會員中心</a></p>
    </div>
</section>
