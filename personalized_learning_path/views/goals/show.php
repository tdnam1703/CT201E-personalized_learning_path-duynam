<?php
$late = $goal['target_date'] && $goal['target_date'] < date('Y-m-d')
        && !in_array($goal['status'], [GOAL_COMPLETED, GOAL_CANCELLED], true);
?>
<p><a href="<?= url('/goals') ?>">← Danh sách mục tiêu</a></p>

<div class="card card-wide card-left">
    <div class="page-head">
        <h2><?= e($goal['title']) ?></h2>
        <a href="<?= url("/goals/{$goal['id']}/edit") ?>">Sửa mục tiêu</a>
    </div>
    <p class="goal-meta">
        <span class="badge prio-<?= strtolower($goal['priority']) ?>"><?= e(PRIORITY_LABELS[$goal['priority']] ?? $goal['priority']) ?></span>
        · <?= e($goal['status']) ?>
        · Bắt đầu: <?= $goal['start_date'] ? e(date('d/m/Y', strtotime($goal['start_date']))) : '—' ?>
        · Hạn: <span class="<?= $late ? 'late' : '' ?>"><?= $goal['target_date'] ? e(date('d/m/Y', strtotime($goal['target_date']))) : '—' ?></span>
    </p>
    <?php if ($goal['description']): ?><p><?= nl2br(e($goal['description'])) ?></p><?php endif; ?>
    <div class="bar"><div class="bar-fill" style="width: <?= (int) $goal['progress_percent'] ?>%"></div></div>
    <small>Tiến độ: <?= (int) $goal['progress_percent'] ?>%</small>
</div>

<div class="page-head">
    <h2>Các giai đoạn</h2>
    <a class="btn btn-inline" href="<?= url("/goals/{$goal['id']}/phases/create") ?>">+ Thêm giai đoạn</a>
</div>

<?php if (!$phases): ?>
    <p class="muted">Chưa có giai đoạn nào. Hãy chia mục tiêu thành các giai đoạn nhỏ để dễ theo dõi.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>#</th><th>Giai đoạn</th><th>Thời gian</th><th>Trạng thái</th><th>Nhiệm vụ</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($phases as $p): ?>
        <tr>
            <td><?= (int) $p['order_no'] ?></td>
            <td><?= e($p['title']) ?><?php if ($p['description']): ?><br><small class="muted"><?= e($p['description']) ?></small><?php endif; ?></td>
            <td>
                <?= $p['start_date'] ? e(date('d/m/Y', strtotime($p['start_date']))) : '—' ?>
                → <?= $p['end_date'] ? e(date('d/m/Y', strtotime($p['end_date']))) : '—' ?>
            </td>
            <td><?= e($p['status']) ?></td>
            <td><?= (int) $p['done_tasks'] ?>/<?= (int) $p['total_tasks'] ?></td>
            <td class="actions">
                <a href="<?= url("/phases/{$p['id']}/edit") ?>">Sửa</a>
                <form method="post" action="<?= url("/phases/{$p['id']}/delete") ?>" class="inline-form"
                      onsubmit="return confirm('Xóa giai đoạn này? Các nhiệm vụ thuộc giai đoạn cũng sẽ bị xóa.');">
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

<div class="page-head section-gap">
    <h2>Nhiệm vụ</h2>
    <a class="btn btn-inline" href="<?= url("/goals/{$goal['id']}/tasks/create") ?>">+ Thêm nhiệm vụ</a>
</div>

<?php
// Tạo form nhỏ đổi trạng thái nhanh cho 1 nhiệm vụ
$statusForm = function (array $t, string $status, string $label): string {
    return '<form method="post" action="' . e(url("/tasks/{$t['id']}/status")) . '" class="inline-form">'
        . csrf_field()
        . '<input type="hidden" name="status" value="' . e($status) . '">'
        . '<button type="submit" class="link-btn">' . e($label) . '</button></form>';
};
?>
<?php if (!$tasks): ?>
    <p class="muted">Chưa có nhiệm vụ nào. Hãy thêm các việc cụ thể cần làm để hoàn thành mục tiêu.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nhiệm vụ</th><th>Giai đoạn</th><th>Ưu tiên</th><th>Trạng thái / tiến độ</th><th>Hạn</th><th>Dự kiến</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tasks as $t):
        $taskLate = $t['due_date'] && $t['due_date'] < date('Y-m-d H:i:s')
                    && in_array($t['status'], [TASK_TODO, TASK_IN_PROGRESS], true);
    ?>
        <tr class="<?= $t['status'] === TASK_DONE || $t['status'] === TASK_CANCELLED ? 'row-muted' : '' ?>">
            <td><?= e($t['title']) ?><?php if ($t['description']): ?><br><small class="muted"><?= e($t['description']) ?></small><?php endif; ?></td>
            <td><?= $t['phase_title'] ? e($t['phase_title']) : '—' ?></td>
            <td><span class="badge prio-<?= strtolower($t['priority']) ?>"><?= e(PRIORITY_LABELS[$t['priority']] ?? $t['priority']) ?></span></td>
            <td class="status-cell">
                <?= e(TASK_STATUS_LABELS[$t['status']] ?? $t['status']) ?> · <?= (int) $t['progress_percent'] ?>%
                <div class="bar bar-mini"><div class="bar-fill" style="width: <?= (int) $t['progress_percent'] ?>%"></div></div>
                <div class="quick">
                <?php if ($t['status'] === TASK_TODO): ?>
                    <?= $statusForm($t, TASK_IN_PROGRESS, 'Bắt đầu') ?>
                    <?= $statusForm($t, TASK_DONE, 'Hoàn thành') ?>
                <?php elseif ($t['status'] === TASK_IN_PROGRESS): ?>
                    <form method="post" action="<?= url("/tasks/{$t['id']}/progress") ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="number" name="progress" min="0" max="100" value="<?= (int) $t['progress_percent'] ?>" class="mini-input"> %
                        <button type="submit" class="link-btn">Lưu</button>
                    </form>
                    <?= $statusForm($t, TASK_DONE, 'Hoàn thành') ?>
                <?php elseif ($t['status'] === TASK_DONE): ?>
                    <?= $statusForm($t, TASK_TODO, 'Mở lại') ?>
                <?php endif; ?>
                </div>
            </td>
            <td class="<?= $taskLate ? 'late' : '' ?>">
                <?= $t['due_date'] ? e(date('d/m/Y H:i', strtotime($t['due_date']))) : '—' ?>
                <?= $taskLate ? '<br><small>Quá hạn</small>' : '' ?>
            </td>
            <td><?= $t['estimated_minutes'] ? (int) $t['estimated_minutes'] . ' phút' : '—' ?></td>
            <td class="actions">
                <?php if (in_array($t['status'], [TASK_TODO, TASK_IN_PROGRESS], true)): ?>
                    <form method="post" action="<?= url('/study/start') ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                        <button type="submit" class="link-btn">▶ Học</button>
                    </form>
                <?php endif; ?>
                <a href="<?= url("/tasks/{$t['id']}/edit") ?>">Sửa</a>
                <form method="post" action="<?= url("/tasks/{$t['id']}/delete") ?>" class="inline-form"
                      onsubmit="return confirm('Xóa nhiệm vụ này?');">
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