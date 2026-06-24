<?php $user = current_user(); ?>
<section class="dashboard-shell">
    <aside class="dashboard-sidebar">
        <span class="section-code">MEMBER / <?= e(str_pad((string) $user['id'], 4, '0', STR_PAD_LEFT)) ?></span>
        <div class="profile-block"><div class="profile-avatar"><?= e(mb_substr($user['username'], 0, 1)) ?></div><h1><?= e($user['username']) ?></h1><p><?= e(implode(' / ', $user['roles'] ?? [])) ?></p></div>
        <nav aria-label="會員中心"><a class="active" href="#overview">席位總覽</a><a href="#watchlist">監看名冊</a><a href="#orders">得標訂單</a><a href="#credit">信用軌跡</a></nav>
        <div class="credit-card" id="credit"><span>目前信用</span><strong><?= (int) ($user['credit_score'] ?? 80) ?><small>/100</small></strong><i><b style="width: <?= min(100, (int) ($user['credit_score'] ?? 80)) ?>%"></b></i><p>近 90 天無違約紀錄</p></div>
    </aside>
    <div class="dashboard-content" id="overview">
        <div class="dashboard-heading"><div><span>GOOD EVENING</span><h2>你的交易席位</h2></div><a class="button button-small" href="<?= e(url('home')) ?>">尋找拍賣品</a></div>
        <div class="metric-grid">
            <article><span>監看中</span><strong><?= count($watched) ?></strong><small>其中 2 件將於今晚截標</small></article>
            <article><span>本月出價</span><strong>12</strong><small>4 次由代理系統執行</small></article>
            <article><span>待處理訂單</span><strong><?= count($orders) ?></strong><small>請依交易時限完成操作</small></article>
            <article class="metric-accent"><span>最高代理上限</span><strong><?= e(money(280000)) ?></strong><small>僅你可見</small></article>
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
    </div>
</section>
