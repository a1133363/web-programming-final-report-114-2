<?php
$appConfig = require dirname(__DIR__, 2) . '/config/app.php';
$user = current_user();
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="地下黑市拍賣會：虛構物品競標與信任交易系統。">
    <meta name="theme-color" content="#0b090c">
    <title><?= e($pageTitle ?? '') ?>｜<?= e($appConfig['name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body data-page="<?= e($_GET['page'] ?? 'home') ?>">
    <a class="skip-link" href="#main-content">跳到主要內容</a>
    <header class="site-header">
        <a class="brand" href="<?= e(url('home')) ?>" aria-label="NOCTURNE 暗標局首頁">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" role="img"><path d="M24 3 38 10v15c0 9-5.7 16-14 20C15.7 41 10 34 10 25V10L24 3Z"/><path d="M17 20h14M18.5 27h11M24 14v19"/></svg>
            </span>
            <span><strong>NOCTURNE</strong><small>暗標局 / 虛構拍賣</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="開啟導覽選單">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <nav id="primary-nav" class="primary-nav" aria-label="主要導覽">
            <a href="<?= e(url('home')) ?>">拍賣目錄</a>
            <a href="<?= e(url('wanted')) ?>">通緝名冊</a>
            <?php if ($user): ?>
                <a href="<?= e(url('buyer')) ?>">我的席位</a>
                <?php if (has_role('seller') || has_role('admin')): ?>
                    <a href="<?= e(url('seller')) ?>">賣家控制室</a>
                <?php endif; ?>
                <?php if (has_role('admin')): ?>
                    <a class="nav-admin" href="<?= e(url('admin')) ?>">監察後台</a>
                <?php endif; ?>
                <span class="user-chip"><span aria-hidden="true"></span><?= e($user['username']) ?></span>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="nav-signout" type="submit">登出</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login')) ?>">登入</a>
                <a class="button button-small" href="<?= e(url('register')) ?>">取得席位</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($successMessage || $errorMessage): ?>
        <div class="toast-wrap" aria-live="polite">
            <div class="toast <?= $errorMessage ? 'toast-error' : 'toast-success' ?>">
                <span aria-hidden="true"><?= $errorMessage ? '!' : '✓' ?></span>
                <p><?= e($errorMessage ?: $successMessage) ?></p>
                <button type="button" aria-label="關閉通知" data-dismiss-toast>×</button>
            </div>
        </div>
    <?php endif; ?>

    <main id="main-content">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div>
            <a class="footer-brand" href="<?= e(url('home')) ?>">NOCTURNE / 暗標局</a>
            <p>本平台所有商品、人物與交易均為虛構，用於 PHP + MySQL 教學專題展示。</p>
        </div>
        <div class="footer-meta">
            <span>SESSION ENCRYPTED</span>
            <span>TRUST PROTOCOL v2.6</span>
            <span>© <?= date('Y') ?> NOCTURNE</span>
        </div>
    </footer>
</body>
</html>
