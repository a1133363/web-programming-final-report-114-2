<section class="console-header">
    <div><span class="section-code">USER CONSOLE</span><h1>使用者控制室</h1><p>建立拍賣草稿、使用 AI 輔助描述，並送交監察員審核。</p></div>
    <button class="button" type="button" data-dialog-open="create-auction">建立新拍賣</button>
</section>
<section class="section seller-overview">
    <div class="metric-grid seller-metrics">
        <article><span>競標中</span><strong><?= count(array_filter($auctions, static fn($a) => $a['status'] === 'active')) ?></strong><small>即時追蹤出價紀錄</small></article>
        <article><span>待審核</span><strong><?= count(array_filter($auctions, static fn($a) => $a['status'] === 'pending_review')) ?></strong><small>平均 3.2 小時完成</small></article>
        <article><span>本月成交</span><strong><?= e(money(486000)) ?></strong><small>較上月 +18.2%</small></article>
        <article class="metric-accent"><span>使用者信用</span><strong><?= (int) (current_user()['credit_score'] ?? 92) ?></strong><small>交付準時率 96%</small></article>
    </div>
    <div class="dashboard-panel">
        <div class="panel-heading"><div><span>INVENTORY</span><h3>我的拍賣品</h3></div><span><?= count($auctions) ?> 件</span></div>
        <div class="table-wrap"><table><thead><tr><th>拍品</th><th>分類</th><th>風險</th><th>出價</th><th>目前價格</th><th>狀態</th></tr></thead><tbody>
            <?php foreach ($auctions as $auction): ?><tr><td><strong><?= e($auction['lot_no']) ?></strong><br><?= e($auction['title']) ?></td><td><?= e($auction['category_name']) ?></td><td><span class="risk-text risk-<?= e($auction['risk_level']) ?>"><?= e(risk_label($auction['risk_level'])) ?></span></td><td><?= (int) ($auction['bid_count'] ?? 0) ?></td><td><?= e(money($auction['current_price'])) ?></td><td><span class="status-pill"><?= e(status_label($auction['status'])) ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
    <div class="dashboard-panel">
        <div class="panel-heading"><div><span>ORDERS</span><h3>待交付訂單</h3></div></div>
        <div class="table-wrap"><table><thead><tr><th>訂單</th><th>物件</th><th>買家</th><th>成交金額</th><th>物流狀態</th><th>更新</th></tr></thead><tbody>
            <?php foreach ($orders as $order): ?><tr>
                <td>#<?= e($order['id']) ?></td>
                <td><?= e($order['title']) ?></td>
                <td><?= e($order['buyer_name']) ?></td>
                <td><?= e(money($order['final_price'])) ?></td>
                <td><?= e(status_label($order['delivery_status'] ?? 'pending')) ?><?= !empty($order['tracking_code']) ? '<br><small>' . e($order['tracking_code']) . '</small>' : '' ?></td>
                <td>
                    <?php if (in_array($order['status'], ['pending_delivery', 'disputed'], true)): ?>
                        <form method="post" action="<?= e(url('seller-delivery')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                            <select name="status">
                                <option value="pending" <?= ($order['delivery_status'] ?? '') === 'pending' ? 'selected' : '' ?>>待處理</option>
                                <option value="prepared" <?= ($order['delivery_status'] ?? '') === 'prepared' ? 'selected' : '' ?>>已備貨</option>
                                <option value="in_transit" <?= ($order['delivery_status'] ?? '') === 'in_transit' ? 'selected' : '' ?>>運送中</option>
                                <option value="delivered" <?= ($order['delivery_status'] ?? '') === 'delivered' ? 'selected' : '' ?>>已送達</option>
                            </select>
                            <input type="text" name="tracking_code" value="<?= e($order['tracking_code'] ?? '') ?>" placeholder="追蹤碼">
                            <button class="button button-small" type="submit">更新</button>
                        </form>
                    <?php else: ?>
                        <span class="status-pill"><?= e(status_label($order['status'])) ?></span>
                    <?php endif; ?>
                </td>
            </tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</section>

<?php
$defaultStartAt = date('Y-m-d\TH:i');
$defaultEndAt = date('Y-m-d\TH:i', strtotime('+3 days'));
?>
<dialog id="create-auction" class="auction-dialog">
    <form method="post" action="<?= e(url('seller-create')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="dialog-heading"><div><span>NEW CONSIGNMENT</span><h2>建立新拍賣</h2></div><button type="button" data-dialog-close aria-label="關閉">×</button></div>
        <div class="dialog-grid">
            <label class="span-2"><span>拍賣品名稱</span><input type="text" name="title" minlength="4" maxlength="120" placeholder="例：第十三時辰黑曜計時器" required></label>
            <label><span>商品分類</span><select name="category_id" required><option value="">選擇分類</option><?php foreach ($categories as $category): ?><option value="<?= e($category['id']) ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
            <label><span>商品影像</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
            <div class="span-2 ai-writer">
                <label><span>AI 描述關鍵字</span><input type="text" name="ai_keywords" placeholder="材質、來源、異常現象"></label>
                <button type="button" class="button button-ghost" data-ai-generate data-endpoint="<?= e(url('ai-description')) ?>">生成世界觀描述</button>
            </div>
            <label class="span-2"><span>商品描述</span><textarea name="description" rows="5" minlength="20" placeholder="描述來源、外觀、已知異常與交付內容…" required></textarea></label>
            <label><span>起標價</span><input type="number" name="starting_price" min="1" step="1" required></label>
            <label><span>底價（選填）</span><input type="number" name="reserve_price" min="0" step="1"></label>
            <label class="span-2"><span>最低加價</span><input type="number" name="min_increment" min="1" step="1" required></label>
            <label><span>開始時間</span><input type="datetime-local" name="start_at" value="<?= e($defaultStartAt) ?>" min="<?= e($defaultStartAt) ?>" required></label>
            <label><span>拍賣長度</span><select data-auction-duration><option value="12">12 小時</option><option value="24">1 天</option><option value="72" selected>3 天</option><option value="168">7 天</option><option value="">自訂結束時間</option></select></label>
            <label class="span-2"><span>結束時間</span><input type="datetime-local" name="end_at" value="<?= e($defaultEndAt) ?>" min="<?= e($defaultStartAt) ?>" required></label>
        </div>
        <p class="form-note">送出後狀態為「待審核」，AI 風險建議不會直接取代管理員判斷。</p>
        <button class="button button-full" type="submit">送交監察員審核</button>
    </form>
</dialog>
