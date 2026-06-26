<section class="auth-shell">
    <div class="auth-aside">
        <span class="section-code">ACCESS / NIGHT SESSION</span>
        <h1>驗證你的<br>匿名席位</h1>
        <p>登入後可參與出價、設定代理上限，並追蹤付款、交付與信用紀錄。</p>
        <div class="auth-seal" aria-hidden="true"><span>N</span><small>VERIFIED<br>SESSION</small></div>
    </div>
    <div class="auth-card">
        <div class="auth-heading"><span>SECURE GATEWAY</span><h2>會員登入</h2><p>請輸入您的信箱與密碼以驗證席位。</p></div>
        <form method="post" action="<?= e(url('login')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label><span>電子信箱</span><input type="email" name="email" autocomplete="email" placeholder="name@example.com" required></label>
            <label><span>密碼</span><input type="password" name="password" autocomplete="current-password" placeholder="至少 8 個字元" required></label>
            <button class="button button-full" type="submit">驗證並登入</button>
        </form>
        <div class="demo-accounts">
            <span>快速填入示範身分</span>
            <div>
                <button type="button" data-demo-email="user@example.com">使用者</button>
                <button type="button" data-demo-email="admin@example.com">管理員</button>
            </div>
        </div>
        <p class="auth-switch">尚未取得席位？<a href="<?= e(url('register')) ?>">建立匿名席位</a></p>
    </div>
</section>
