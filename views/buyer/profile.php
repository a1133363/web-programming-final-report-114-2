<?php
$profile = $profile ?? current_user();
$avatarPath = $profile['avatar_path'] ?? '';
$avatarLetter = mb_substr($profile['username'] ?? '?', 0, 1);
?>
<section class="auth-shell">
    <div class="auth-aside">
        <span class="section-code">ACCOUNT / SETTINGS</span>
        <h1>帳號<br>管理</h1>
        <p>更新你的匿名代號、信箱、頭像與密碼，或在此永久刪除席位。</p>
        <div class="auth-seal" aria-hidden="true"><span>U</span><small>USER<br>PROFILE</small></div>
    </div>
    <div class="auth-card">
        <div class="auth-heading"><span>SECURE GATEWAY</span><h2>帳號管理</h2><p>管理你的個人資料與安全設定。</p></div>

        <div class="profile-avatar-preview">
            <?php if (!empty($avatarPath)): ?>
                <img src="<?= e($avatarPath) ?>" alt="頭像">
            <?php else: ?>
                <span><?= e($avatarLetter) ?></span>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('buyer-profile-update')) ?>" enctype="multipart/form-data" class="stack-form">
            <?= csrf_field() ?>
            <label><span>匿名代號</span><input type="text" name="username" value="<?= e($profile['username'] ?? '') ?>" maxlength="40" required></label>
            <label><span>電子信箱</span><input type="email" name="email" value="<?= e($profile['email'] ?? '') ?>" required></label>
            <label><span>更換頭像（選填）</span><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
            <button class="button button-full" type="submit">儲存變更</button>
        </form>

        <div class="auth-heading" style="margin-top: 32px;"><span>SECURITY</span><h3>變更密碼</h3></div>
        <form method="post" action="<?= e(url('buyer-password-update')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label><span>目前密碼</span><input type="password" name="current_password" autocomplete="current-password" required></label>
            <label><span>新密碼</span><input type="password" name="new_password" autocomplete="new-password" placeholder="至少 8 個字元" required></label>
            <label><span>確認新密碼</span><input type="password" name="confirm_password" autocomplete="new-password" required></label>
            <button class="button button-full" type="submit">更新密碼</button>
        </form>

        <div class="auth-heading" style="margin-top: 32px;"><span>DANGER ZONE</span><h3>刪除帳號</h3></div>
        <form method="post" action="<?= e(url('buyer-delete-account')) ?>" class="stack-form danger-zone" onsubmit="return confirm('確定要永久刪除帳號？此操作無法復原，所有資料將被清除。');">
            <?= csrf_field() ?>
            <p class="form-note">刪除帳號將移除你的出價、收藏與評價紀錄。若有進行中的拍賣或訂單，則無法刪除。</p>
            <label><span>請輸入 DELETE 以確認</span><input type="text" name="confirm_text" placeholder="DELETE" required></label>
            <button class="button-danger button-full" type="submit">永久刪除帳號</button>
        </form>

        <p class="auth-switch"><a href="<?= e(url('buyer')) ?>">← 返回會員中心</a></p>
    </div>
</section>
