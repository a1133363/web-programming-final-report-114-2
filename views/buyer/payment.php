<section class="auth-shell">
    <div class="auth-aside">
        <span class="section-code">PAYMENT / ESCROW</span>
        <h1>完成<br>託管付款。</h1>
        <p>選擇付款方式後，款項將進入託管，直到交付確認才會釋放給賣家。</p>
        <div class="auth-seal" aria-hidden="true"><span>₮</span><small>TRUST<br>PROTOCOL</small></div>
    </div>
    <div class="auth-card">
        <div class="auth-heading"><span>SECURE CHECKOUT</span><h2>訂單付款</h2><p><?= e($order['order_no'] ?? '#'.$order['id']) ?></p></div>
        <div class="lot-summary">
            <strong><?= e($order['title']) ?></strong>
            <p>賣家：<?= e($order['seller_name']) ?></p>
            <p>應付金額：<strong><?= e(money($order['final_price'])) ?></strong></p>
            <p>平台手續費：<?= e(money($order['platform_fee'] ?? 0)) ?></p>
        </div>
        <form method="post" action="<?= e(url('buyer-pay')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <fieldset class="role-choice">
                <legend>付款方式</legend>
                <label><input type="radio" name="method" value="escrow" checked><span><strong>託管付款</strong><small>推薦：款項由平台代管，確認交付後釋放</small></span></label>
                <label><input type="radio" name="method" value="bank_transfer"><span><strong>銀行轉帳</strong><small>轉帳後需手動上傳憑證</small></span></label>
                <label><input type="radio" name="method" value="virtual_credit"><span><strong>虛擬信用額度</strong><small>直接扣除帳戶餘額</small></span></label>
            </fieldset>
            <button class="button button-full" type="submit">確認付款</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('buyer')) ?>">← 返回會員中心</a></p>
    </div>
</section>
