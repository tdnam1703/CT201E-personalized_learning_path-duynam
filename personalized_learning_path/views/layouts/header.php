<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Lộ trình học tập') ?> | Personalized Learning Path</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= url('/') ?>">📚 Lộ trình học tập</a>
    <nav>
        <?php if (SessionHelper::isLoggedIn()): ?>
            <a href="<?= url('/dashboard') ?>">Tổng quan</a>
            <a href="<?= url('/goals') ?>">Mục tiêu</a>
            <a href="<?= url('/schedule') ?>">Lịch</a>
            <a href="<?= url('/study') ?>">Học tập</a>
            <a href="<?= url('/reminders') ?>">Nhắc nhở</a>
            <a href="<?= url('/suggestions') ?>">Gợi ý</a>
            <?php $unread = (new NotificationModel())->unreadCount((int) SessionHelper::userId()); ?>
            <a href="<?= url('/notifications') ?>" class="bell" title="Thông báo">🔔<?php if ($unread > 0): ?><span class="badge-count"><?= $unread > 99 ? '99+' : $unread ?></span><?php endif; ?></a>
            <span>Xin chào, <?= e(SessionHelper::get('user_name')) ?></span>
            <form method="post" action="<?= url('/logout') ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="link-btn">Đăng xuất</button>
            </form>
        <?php else: ?>
            <a href="<?= url('/login') ?>">Đăng nhập</a>
            <a href="<?= url('/register') ?>">Đăng ký</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php if ($msg = SessionHelper::flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = SessionHelper::flash('error')): ?>
    <div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>