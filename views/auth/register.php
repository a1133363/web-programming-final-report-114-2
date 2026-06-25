<section class="auth-shell">
    <div class="auth-aside register-aside">
        <span class="section-code">ENROLL / NEW SEAT</span>
        <h1>建立一個<br>可追溯的匿名。</h1>
        <p>暗標局不要求真名，但每次出價、交付與爭議都會累積為信用軌跡。</p>
        <dl class="auth-points"><div><dt>80</dt><dd>初始信用</dd></div><div><dt>1</dt><dd>統一身分</dd></div><div><dt>∞</dt><dd>虛構物件</dd></div></dl>
    </div>
    <div class="auth-card">
        <div class="auth-heading"><span>SEAT APPLICATION</span><h2>申請席位</h2><p>請使用可接收通知的電子信箱。</p></div>
        <form method="post" action="<?= e(url('register')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label><span>匿名代號</span><input type="text" name="username" minlength="2" maxlength="40" autocomplete="nickname" placeholder="例：霧港來客" required></label>
            <label><span>電子信箱</span><input type="email" name="email" autocomplete="email" placeholder="name@example.com" required></label>
            <label><span>密碼</span><input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="至少 8 個字元" required></label>
            <button class="button button-full" type="submit">建立席位</button>
        </form>
        <p class="auth-switch">已有席位？<a href="<?= e(url('login')) ?>">返回登入</a></p>
    </div>
</section>
