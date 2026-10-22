<div class="page-head">
    <h1>Mục tiêu của tôi</h1>
    <a class="btn btn-inline" href="<?= url('/goals/create') ?>">+ Thêm mục tiêu</a>
</div>

<div class="filters">
    <a href="<?= url('/goals') ?>" class="<?= $filter === null ? 'active' : '' ?>">Tất cả</a>
    <?php foreach (GOAL_STATUSES as $st): ?>
        <a href="<?= url('/goals?status=' . urlencode($st)) ?>" class="<?= $filter === $st ? 'active' : '' ?>"><?= e($st) ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$goals): ?>
    <p class="muted">Chưa có mục tiêu nào. Hãy bấm "Thêm mục tiêu" để bắt đầu.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead>
        <tr><th>Mục tiêu</th><th>Ưu tiên</th><th>Trạng thái</th><th>Hạn</th><th>Tiến độ</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($goals as $g):
        $late = $g['target_date'] && $g['target_date'] < date('Y-m-d')
                && !in_array($g['status'], [GOAL_COMPLETED, GOAL_CANCELLED], true);
    ?>
        <tr>
            <td><a href="<?= url("/goals/{$g['id']}") ?>"><?= e($g['title']) ?></a></td>
            <td><span class="badge prio-<?= strtolower($g['priority']) ?>"><?= e(PRIORITY_LABELS[$g['priority']] ?? $g['priority']) ?></span></td>
            <td><?= e($g['status']) ?></td>
            <td class="<?= $late ? 'late' : '' ?>">
                <?= $g['target_date'] ? e(date('d/m/Y', strtotime($g['target_date']))) : '—' ?>
                <?= $late ? '<br><small>Quá hạn</small>' : '' ?>
            </td>
            <td>
                <div class="bar"><div class="bar-fill" style="width: <?= (int) $g['progress_percent'] ?>%"></div></div>
                <small><?= (int) $g['progress_percent'] ?>% (<?= (int) $g['done_tasks'] ?>/<?= (int) $g['total_tasks'] ?> nhiệm vụ)</small>
            </td>
            <td class="actions">
                <a href="<?= url("/goals/{$g['id']}/edit") ?>">Sửa</a>
                <form method="post" action="<?= url("/goals/{$g['id']}/delete") ?>" class="inline-form"
                      onsubmit="return confirm('Xóa mục tiêu này? Các giai đoạn và nhiệm vụ bên trong cũng sẽ bị xóa.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-btn danger">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>