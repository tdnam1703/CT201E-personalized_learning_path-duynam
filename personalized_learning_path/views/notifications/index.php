<?php
$icons = ['REMINDER' => '⏰', 'OVERDUE' => '⚠️', 'SCHEDULE' => '📅', 'SUGGESTION' => '💡', 'SYSTEM' => '🔧'];
$hasUnread = (bool) array_filter($items, fn ($n) => !(int) $n['is_read']);
?>
<div class="page-head">
    <h1>Thông báo</h1>
    <?php if ($hasUnread): ?>
        <form method="post" action="<?= url('/notifications/read-all') ?>" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-inline">Đánh dấu tất cả đã đọc</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!$items): ?>
    <p class="muted">Chưa có thông báo nào.</p>
<?php else: ?>
<ul class="notif-list">
    <?php foreach ($items as $n): ?>
        <li class="<?= (int) $n['is_read'] ? '' : 'unread' ?> type-<?= strtolower($n['type']) ?>">
            <div class="notif-icon"><?= $icons[$n['type']] ?? '🔔' ?></div>
            <div class="notif-body">
                <b><?= e($n['title']) ?></b>
                <?php if ($n['content']): ?><div><?= e($n['content']) ?></div><?php endif; ?>
                <small class="muted"><?= e(date('H:i d/m/Y', strtotime($n['created_at']))) ?></small>
            </div>
            <div class="notif-actions">
                <?php if ($n['goal_id']): ?><a href="<?= url("/goals/{$n['goal_id']}") ?>">Xem</a><?php endif; ?>
                <?php if (!(int) $n['is_read']): ?>
                    <form method="post" action="<?= url("/notifications/{$n['id']}/read") ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-btn">Đã đọc</button>
                    </form>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>