<?php
$user = current_user();
$wallet = $wallet ?? ['balance' => 0, 'transactions' => []];
$transactionLabels = ['payment' => '付款', 'refund' => '退款', 'payout' => '收款', 'deposit' => '入金'];
$pendingPaymentOrders = array_values(array_filter($orders, static fn($order) => ($order['status'] ?? '') === 'pending_payment'));
$pendingPaymentTotal = array_sum(array_map(static fn($order) => (float) ($order['final_price'] ?? 0), $pendingPaymentOrders));
$walletBalance = (float) ($wallet['balance'] ?? 0);
$balanceAfterPending = $walletBalance - $pendingPaymentTotal;
?>
<section class="dashboard-shell">
    <aside class="dashboard-sidebar">
        <span class="section-code">MEMBER / <?= e(str_pad((string) $user['id'], 4, '0', STR_PAD_LEFT)) ?></span>
        <div class="profile-block"><div class="profile-avatar"><?php if (!empty($user['avatar_path'])): ?><img src="<?= e($user['avatar_path']) ?>" alt=""><?php else: ?><?= e(mb_substr($user['username'], 0, 1)) ?><?php endif; ?></div><h1><?= e($user['username']) ?></h1><p><?= e(implode(' / ', array_map(static fn($r) => $r === 'user' ? '使用者' : $r, $user['roles'] ?? []))) ?></p></div>
        <nav aria-label="會員中心"><a class="active" href="#overview">席位總覽</a><a href="#watchlist">監看名冊</a><a href="#orders">得標訂單</a><a href="#transactions">錢包</a><a href="#credit">信用軌跡</a><a href="<?= e(url('buyer-profile')) ?>">帳號管理</a></nav>
        <div class="credit-card" id="credit"><span>目前信用</span><strong><?= (int) ($user['credit_score'] ?? 80) ?><small>/100</small></strong><i><b style="width: <?= min(100, (int) ($user['credit_score'] ?? 80)) ?>%"></b></i><p>近 90 天無違約紀錄</p></div>
    </aside>
    <div class="dashboard-content" id="overview">
        <div class="dashboard-heading"><div><span>GOOD EVENING</span><h2>你的交易席位</h2></div><div class="panel-actions"><a class="button button-small button-ghost" href="<?= e(url('seller')) ?>">上架拍品</a><a class="button button-small" href="<?= e(url('home')) ?>">尋找拍賣品</a></div></div>
        <div class="metric-grid">
            <article><span>監看中</span><strong><?= count($watched) ?></strong><small>其中 2 件將於今晚截標</small></article>
            <article><span>本月出價</span><strong>12</strong><small>4 次由代理系統執行</small></article>
            <article><span>待付款訂單</span><strong><?= count($pendingPaymentOrders) ?></strong><small><?= $pendingPaymentTotal > 0 ? '待付 ' . e(money($pendingPaymentTotal)) : '目前沒有待付款' ?></small></article>
            <article class="metric-accent"><span>錢包餘額</span><strong><?= e(money($walletBalance)) ?></strong><small><?= $pendingPaymentTotal > 0 ? '待付後 ' . e(money($balanceAfterPending)) : '可用於託管扣款' ?></small></article>
        </div>
        <section id="watchlist" class="dashboard-panel">
            <div class="panel-heading"><div><span>WATCHLIST</span><h3>監看名冊</h3></div><a href="<?= e(url('home')) ?>">查看全部 →</a></div>
            <div class="watch-list">
                <?php foreach ($watched as $item): ?>
                    <a href="<?= e(url('auction', ['id' => $item['id']])) ?>"><img src="<?= e($item['image_path']) ?>" alt=""><div><span><?= e($item['lot_no']) ?> · <?= e(risk_label($item['risk_level'])) ?></span><h4><?= e($item['title']) ?></h4><strong><?= e(money($item['current_price'])) ?></strong></div><div class="countdown" data-countdown="<?= e(date(DATE_ATOM, strtotime($item['end_at']))) ?>"><span>剩餘</span><strong>--:--:--</strong></div></a>
                <?php endforeach; ?>
            </div>
        </section>
        <section id="orders" class="dashboard-panel">
            <div class="panel-heading"><div><span>ORDERS</span><h3>得標與交付</h3></div></div>
            <div class="table-wrap"><table><thead><tr><th>訂單</th><th>物件</th><th>賣家</th><th>成交金額</th><th>物流</th><th>狀態</th><th>操作</th></tr></thead><tbody>
                <?php foreach ($orders as $order): ?><tr>
                    <td>#<?= e($order['id']) ?></td>
                    <td><?= e($order['title']) ?></td>
                    <td><?= e($order['seller_name']) ?></td>
                    <td><?= e(money($order['final_price'])) ?></td>
                    <td><?= e(status_label($order['delivery_status'] ?? 'pending')) ?><?= !empty($order['tracking_code']) ? '<br><small>' . e($order['tracking_code']) . '</small>' : '' ?></td>
                    <td><span class="status-pill"><?= e(status_label($order['status'])) ?></span></td>
                    <td>
                        <?php if ($order['status'] === 'pending_payment'): ?>
                            <a class="button button-small" href="<?= e(url('buyer-payment', ['order_id' => $order['id']])) ?>">付款</a>
                        <?php elseif ($order['status'] === 'pending_delivery'): ?>
                            <a class="button button-small button-ghost" href="<?= e(url('buyer-dispute', ['order_id' => $order['id']])) ?>">爭議</a>
                        <?php elseif ($order['status'] === 'completed'): ?>
                            <a class="button button-small button-ghost" href="<?= e(url('buyer-review', ['order_id' => $order['id']])) ?>">評價</a>
                        <?php elseif ($order['status'] === 'disputed'): ?>
                            <span class="status-pill">處理中</span>
                        <?php endif; ?>
                    </td>
                </tr><?php endforeach; ?>
            </tbody></table></div>
        </section>
        <section id="transactions" class="dashboard-panel">
            <div class="panel-heading"><div><span>WALLET ACCOUNT</span><h3>錢包與交易紀錄</h3></div><span>餘額 <?= e(money($walletBalance)) ?></span></div>
            <div class="wallet-overview">
                <article><span>可用餘額</span><strong><?= e(money($walletBalance)) ?></strong><small>付款時直接扣除模擬餘額</small></article>
                <article><span>待付款金額</span><strong><?= e(money($pendingPaymentTotal)) ?></strong><small><?= count($pendingPaymentOrders) ?> 筆訂單等待付款</small></article>
                <article><span>預估付款後</span><strong class="<?= $balanceAfterPending < 0 ? 'negative' : '' ?>"><?= e(money($balanceAfterPending)) ?></strong><small><?= $balanceAfterPending < 0 ? '餘額不足，無法送出付款' : '足以支付目前待付款' ?></small></article>
            </div>
            <div class="table-wrap"><table><thead><tr><th>時間</th><th>類型</th><th>訂單</th><th>金額</th><th>餘額</th><th>說明</th></tr></thead><tbody>
                <?php foreach (($wallet['transactions'] ?? []) as $transaction): ?><tr>
                    <td><?= e(date('m/d H:i', strtotime($transaction['created_at']))) ?></td>
                    <td><span class="type-tag"><?= e($transactionLabels[$transaction['type']] ?? $transaction['type']) ?></span></td>
                    <td><?= e($transaction['order_no'] ?? '-') ?><br><small><?= e($transaction['title'] ?? '') ?></small></td>
                    <td><?= ($transaction['type'] ?? '') === 'payment' ? '-' : '+' ?><?= e(money($transaction['amount'])) ?></td>
                    <td><?= e(money($transaction['balance_after'])) ?></td>
                    <td><?= e($transaction['description'] ?? '') ?></td>
                </tr><?php endforeach; ?>
                <?php if (empty($wallet['transactions'])): ?>
                    <tr><td colspan="6" class="empty-state">尚無交易紀錄</td></tr>
                <?php endif; ?>
            </tbody></table></div>
        </section>
        <section id="credit" class="dashboard-panel">
            <div class="panel-heading"><div><span>CREDIT TRACK</span><h3>信用軌跡</h3></div></div>
            <div class="empty-state" style="padding: 40px; border: 1px solid var(--line-strong);">
                <strong>信用席位等級：優良</strong>
                <p style="margin: 15px 0;">當前信用評分：<?= (int) ($user['credit_score'] ?? 80) ?> / 100 分</p>
                <div class="credit-meter" style="max-width: 300px; margin: 0 auto 20px;">
                    <i><b style="width: <?= min(100, (int) ($user['credit_score'] ?? 80)) ?>%"></b></i>
                </div>
                <p style="font-size: 13px; max-width: 580px; margin: 0 auto;">信用紀錄說明：近 90 天內無任何拍賣違約、棄單或未付款紀錄。誠信等級將直接影響您託管付款的釋放速度以及單筆拍賣的最大出價額度。</p>
            </div>
        </section>
    </div>
</section>

