<?php
// Định dạng số phút/giây thành "1 giờ 05 phút"
$fmtMinutes = function (int $m): string {
    if ($m < 60) {
        return $m . ' phút';
    }
    return intdiv($m, 60) . ' giờ ' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) . ' phút';
};
$fmtSeconds = function (int $s) use ($fmtMinutes): string {
    return $s < 60 ? '< 1 phút' : $fmtMinutes(intdiv($s, 60));
};
$percent = $dailyGoal > 0 ? min(100, (int) round(100 * $todayMinutes / $dailyGoal)) : 0;
?>
<h1>Học tập</h1>

<?php if ($running):
    $elapsed = max(0, time() - strtotime($running['started_at']));
?>
<div class="card card-wide card-left">
    <h2>Đang học: <?= $running['task_title'] ? e($running['task_title']) : 'Học tự do' ?></h2>
    <?php if ($running['goal_title']): ?><p class="muted">Mục tiêu: <?= e($running['goal_title']) ?></p><?php endif; ?>
    <div class="timer" id="live-timer" data-elapsed="<?= $elapsed ?>">00:00:00</div>
    <p class="muted">Bắt đầu lúc <?= e(date('H:i d/m/Y', strtotime($running['started_at']))) ?></p>

    <form method="post" action="<?= url("/study/{$running['id']}/stop") ?>">
        <?= csrf_field() ?>
        <label for="note">Ghi chú (không bắt buộc)</label>
        <input type="text" id="note" name="note" maxlength="500" placeholder="Hôm nay mình học được gì?">
        <button type="submit" class="btn">Kết thúc phiên học</button>
    </form>
    <form method="post" action="<?= url("/study/{$running['id']}/delete") ?>" onsubmit="return confirm('Hủy phiên học này? Thời gian sẽ không được tính.');">
        <?= csrf_field() ?>
        <button type="submit" class="link-btn danger">Hủy phiên (không tính giờ)</button>
    </form>
</div>
<script>
(function () {
    var el = document.getElementById('live-timer');
    if (!el) return;
    var base = parseInt(el.dataset.elapsed, 10) || 0;
    var t0 = Date.now();
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function tick() {
        var s = base + Math.floor((Date.now() - t0) / 1000);
        el.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s / 60) % 60) + ':' + pad(s % 60);
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
<?php else: ?>
<div class="card card-wide card-left">
    <h2>Bắt đầu phiên học</h2>
    <form method="post" action="<?= url('/study/start') ?>">
        <?= csrf_field() ?>
        <label for="task_id">Bạn sẽ học gì?</label>
        <select id="task_id" name="task_id">
            <option value="">— Học tự do (không gắn nhiệm vụ) —</option>
            <?php foreach ($taskOptions as $o): ?>
                <option value="<?= (int) $o['id'] ?>"><?= e($o['goal_title']) ?> › <?= e($o['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">▶ Bắt đầu học</button>
    </form>
</div>
<?php endif; ?>

<div class="card card-wide card-left">
    <h2>Hôm nay</h2>
    <p><b><?= e($fmtMinutes($todayMinutes)) ?></b> / mục tiêu <?= e($fmtMinutes($dailyGoal)) ?> (<?= $percent ?>%)</p>
    <div class="bar"><div class="bar-fill" style="width: <?= $percent ?>%"></div></div>
    <?php if ($running): ?><small class="muted">Phiên đang chạy sẽ được tính sau khi kết thúc.</small><?php endif; ?>
</div>

<h2>7 ngày gần nhất</h2>
<?php if (!$week): ?>
    <p class="muted">Chưa có dữ liệu. Hãy hoàn thành một phiên học để xem thống kê.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Ngày</th><th>Thời gian học</th><th>Số phiên</th></tr></thead>
    <tbody>
    <?php foreach ($week as $d): ?>
        <tr>
            <td><?= e(date('d/m/Y', strtotime($d['study_date']))) ?></td>
            <td><?= e($fmtMinutes((int) $d['total_minutes'])) ?></td>
            <td><?= (int) $d['session_count'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="section-gap">Các phiên học gần đây</h2>
<?php if (!$recent): ?>
    <p class="muted">Chưa có phiên học nào.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Bắt đầu</th><th>Nội dung</th><th>Thời lượng</th><th>Ghi chú</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($recent as $s): ?>
        <tr>
            <td><?= e(date('H:i d/m/Y', strtotime($s['started_at']))) ?></td>
            <td>
                <?php if ($s['task_title']): ?>
                    <?= e($s['task_title']) ?><br><small class="muted"><?= e($s['goal_title'] ?? '') ?></small>
                <?php else: ?>Học tự do<?php endif; ?>
            </td>
            <td><?= e($fmtSeconds((int) $s['seconds'])) ?></td>
            <td><?= e($s['note'] ?? '') ?></td>
            <td class="actions">
                <form method="post" action="<?= url("/study/{$s['id']}/delete") ?>" class="inline-form"
                      onsubmit="return confirm('Xóa phiên học này?');">
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