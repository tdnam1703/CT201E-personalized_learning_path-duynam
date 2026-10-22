<?php
$fmtMinutes = function (int $m): string {
    if ($m < 60) {
        return $m . ' phút';
    }
    return intdiv($m, 60) . ' giờ ' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) . ' phút';
};
$lateText = fn (int $days): string => $days > 0 ? "trễ {$days} ngày" : 'trễ < 1 ngày';
$leftText = fn (int $hours): string => $hours < 24 ? "còn {$hours} giờ" : 'còn ' . intdiv($hours, 24) . ' ngày';

$scale = max($dailyGoal, max(array_column($week, 'minutes')), 1); // trục tung của biểu đồ
?>
<h1>Tổng quan</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Mục tiêu hoàn thành</div>
        <div class="stat-value"><?= $doneGoals ?>/<?= $totalGoals ?></div>
        <div class="bar"><div class="bar-fill" style="width: <?= $goalRate ?>%"></div></div>
        <small><?= $goalRate ?>%</small>
    </div>
    <div class="stat-card">
        <div class="stat-label">Nhiệm vụ hoàn thành</div>
        <div class="stat-value"><?= $taskDone ?>/<?= $taskTotal ?></div>
        <div class="bar"><div class="bar-fill" style="width: <?= $taskRate ?>%"></div></div>
        <small><?= $taskRate ?>%</small>
    </div>
    <div class="stat-card <?= $overdueCount > 0 ? 'stat-danger' : '' ?>">
        <div class="stat-label">Nhiệm vụ quá hạn</div>
        <div class="stat-value"><?= $overdueCount ?></div>
        <small><?= $overdueCount > 0 ? 'Cần xử lý sớm' : 'Bạn đang đúng tiến độ 👍' ?></small>
    </div>
    <div class="stat-card">
        <div class="stat-label">Học hôm nay</div>
        <div class="stat-value"><?= e($fmtMinutes($todayMinutes)) ?></div>
        <small>Mục tiêu: <?= e($fmtMinutes($dailyGoal)) ?></small>
    </div>
</div>

<div class="two-col">
    <section>
        <h2>Quá hạn</h2>
        <?php if (!$overdue): ?>
            <p class="muted">Không có nhiệm vụ nào quá hạn.</p>
        <?php else: ?>
            <ul class="task-list">
            <?php foreach ($overdue as $t): ?>
                <li>
                    <a href="<?= url("/goals/{$t['goal_id']}") ?>"><?= e($t['title']) ?></a>
                    <small class="muted"><?= e($t['goal_title']) ?></small>
                    <span class="late"><?= e(date('d/m H:i', strtotime($t['due_date']))) ?> · <?= e($lateText((int) $t['days_late'])) ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php if ($overdueCount > count($overdue)): ?><small class="muted">… và <?= $overdueCount - count($overdue) ?> nhiệm vụ khác.</small><?php endif; ?>
        <?php endif; ?>
    </section>

    <section>
        <h2>Sắp đến hạn (7 ngày tới)</h2>
        <?php if (!$upcoming): ?>
            <p class="muted">Không có nhiệm vụ nào sắp đến hạn.</p>
        <?php else: ?>
            <ul class="task-list">
            <?php foreach ($upcoming as $t): ?>
                <li>
                    <a href="<?= url("/goals/{$t['goal_id']}") ?>"><?= e($t['title']) ?></a>
                    <small class="muted"><?= e($t['goal_title']) ?></small>
                    <span><?= e(date('d/m H:i', strtotime($t['due_date']))) ?> · <?= e($leftText((int) $t['hours_left'])) ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<h2>Mục tiêu đang theo đuổi</h2>
<?php if (!$activeGoals): ?>
    <p class="muted">Chưa có mục tiêu nào đang thực hiện. <a href="<?= url('/goals/create') ?>">Tạo mục tiêu mới</a></p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Mục tiêu</th><th>Hạn</th><th>Tiến độ</th><th>Quá hạn</th></tr></thead>
    <tbody>
    <?php foreach ($activeGoals as $g): ?>
        <tr>
            <td><a href="<?= url("/goals/{$g['goal_id']}") ?>"><?= e($g['title']) ?></a></td>
            <td><?= $g['target_date'] ? e(date('d/m/Y', strtotime($g['target_date']))) : '—' ?></td>
            <td>
                <div class="bar"><div class="bar-fill" style="width: <?= (int) $g['progress_percent'] ?>%"></div></div>
                <small><?= (int) $g['progress_percent'] ?>% (<?= (int) $g['done_tasks'] ?>/<?= (int) $g['total_tasks'] ?>)</small>
            </td>
            <td class="<?= (int) $g['overdue_tasks'] > 0 ? 'late' : '' ?>"><?= (int) $g['overdue_tasks'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="section-gap">Thời gian học 7 ngày qua</h2>
<p class="muted">Tổng: <b><?= e($fmtMinutes($weekMinutes)) ?></b> · Đường nét đứt là mục tiêu mỗi ngày (<?= e($fmtMinutes($dailyGoal)) ?>)</p>
<div class="chart">
<div class="chart-area">
    <div class="chart-goal-line" style="bottom: <?= round(100 * $dailyGoal / $scale, 1) ?>%"></div>
    <?php foreach ($week as $d): ?>
        <div class="chart-col">
            <div class="chart-value"><?= $d['minutes'] > 0 ? (int) $d['minutes'] : '' ?></div>
            <div class="chart-bar" style="height: <?= round(100 * $d['minutes'] / $scale, 1) ?>%"></div>
            <div class="chart-label"><?= e(date('d/m', strtotime($d['date']))) ?></div>
        </div>
    <?php endforeach; ?>
</div>
</div>
<p><a href="<?= url('/study') ?>">Đi tới trang Học tập →</a></p>