<?php
$wallet = $wallet ?? ['balance' => 0, 'transactions' => []];
$transactionLabels = ['payment' => '付款', 'refund' => '退款', 'payout' => '收款', 'deposit' => '入金'];
$payAmount = (float) ($order['final_price'] ?? 0);
$platformFee = (float) ($order['platform_fee'] ?? 0);
$sellerPayout = max(0, $payAmount - $platformFee);
$walletBalance = (float) ($wallet['balance'] ?? 0);
$balanceAfter = $walletBalance - $payAmount;
$canPay = $balanceAfter >= 0;
$orderNo = $order['order_no'] ?? '#' . $order['id'];
$dueAt = !empty($order['payment_due_at']) ? date('Y/m/d H:i', strtotime($order['payment_due_at'])) : '得標後 48 小時內';
?>
<section class="auth-shell">
    <div class="auth-aside">
        <span class="section-code">PAYMENT / ESCROW</span>
        <h1>錢包<br>託管付款</h1>
        <p>本專題使用模擬餘額完成扣款，流程仍保留正式結帳需要的餘額確認、託管狀態與交易紀錄</p>
        <div class="auth-seal" aria-hidden="true"><span>₮</span><small>TRUST<br>PROTOCOL</small></div>
    </div>
    <div class="auth-card payment-card">
        <div class="auth-heading"><span>SECURE CHECKOUT</span><h2>確認付款</h2><p><?= e($orderNo) ?></p></div>

        <div class="payment-wallet">
            <div>
                <span>可用錢包餘額</span>
                <strong><?= e(money($walletBalance)) ?></strong>
                <small><?= $canPay ? '付款後餘額 ' . e(money($balanceAfter)) : '尚差 ' . e(money(abs($balanceAfter))) ?></small>
            </div>
            <span class="status-pill <?= $canPay ? 'status-ok' : 'status-danger' ?>"><?= $canPay ? '可付款' : '餘額不足' ?></span>
        </div>

        <div class="payment-grid">
            <section class="payment-summary" aria-label="訂單摘要">
                <span>拍品</span>
                <strong><?= e($order['title']) ?></strong>
                <small>賣家 <?= e($order['seller_name']) ?></small>
            </section>
            <dl class="payment-breakdown">
                <div><dt>成交金額</dt><dd><?= e(money($payAmount)) ?></dd></div>
                <div><dt>平台留存手續費</dt><dd><?= e(money($platformFee)) ?></dd></div>
                <div><dt>賣家完成交付後釋款</dt><dd><?= e(money($sellerPayout)) ?></dd></div>
                <div><dt>付款期限</dt><dd><?= e($dueAt) ?></dd></div>
            </dl>
        </div>

        <div class="payment-flow" aria-label="託管流程">
            <span><b>1</b>扣除錢包餘額</span>
            <span><b>2</b>款項進入平台託管</span>
            <span><b>3</b>交付完成後釋款</span>
        </div>

        <form method="post" action="<?= e(url('buyer-pay')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <input type="hidden" name="method" value="escrow">
            <div class="payment-method">
                <span>付款方式</span>
                <strong>錢包託管扣款</strong>
                <small>付款成功後會產生交易編號，並寫入錢包流水</small>
            </div>
            <label class="confirm-check">
                <input type="checkbox" name="confirm_payment" required <?= $canPay ? '' : 'disabled' ?>>
                <span>我確認此為模擬付款，扣款後訂單會進入待交付狀態</span>
            </label>
            <button class="button button-full" type="submit" <?= $canPay ? '' : 'disabled' ?>><?= $canPay ? '確認付款' : '餘額不足' ?></button>
        </form>

        <?php if (!empty($wallet['transactions'])): ?>
            <div class="recent-ledger">
                <span>最近錢包流水</span>
                <?php foreach (array_slice($wallet['transactions'], 0, 3) as $transaction): ?>
                    <div>
                        <b><?= e($transactionLabels[$transaction['type']] ?? $transaction['type']) ?></b>
                        <small><?= e($transaction['order_no'] ?? '-') ?></small>
                        <strong><?= ($transaction['type'] ?? '') === 'payment' ? '-' : '+' ?><?= e(money($transaction['amount'])) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="auth-switch"><a href="<?= e(url('buyer')) ?>">← 返回會員中心</a></p>
    </div>
</section>
