<section class="auth-shell">
    <div class="auth-aside">
        <span class="section-code">REVIEW / CREDIT</span>
        <h1>留下<br>交易評價。</h1>
        <p>評價將影響對方的信用分數與其他會員的交易決策。請根據實際體驗給予公正評分。</p>
    </div>
    <div class="auth-card">
        <div class="auth-heading"><span>REVIEW FORM</span><h2>評價交易</h2><p><?= e($order['title']) ?> · <?= e($order['seller_name']) ?></p></div>
        <form method="post" action="<?= e(url('buyer-review-submit')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <label><span>評分（1–5 星）</span>
                <select name="rating" required>
                    <option value="">選擇評分</option>
                    <option value="5">★★★★★ 完美</option>
                    <option value="4">★★★★☆ 滿意</option>
                    <option value="3">★★★☆☆ 普通</option>
                    <option value="2">★★☆☆☆ 不佳</option>
                    <option value="1">★☆☆☆☆ 很差</option>
                </select>
            </label>
            <label><span>評語（選填）</span><textarea name="comment" rows="3" maxlength="1000" placeholder="包裝、溝通、交付速度…"></textarea></label>
            <button class="button button-full" type="submit">送出評價</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('buyer')) ?>">← 返回會員中心</a></p>
    </div>
</section>
