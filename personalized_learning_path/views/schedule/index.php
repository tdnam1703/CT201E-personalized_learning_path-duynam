<div class="page-head">
    <h1>Lịch học tập</h1>
    <a class="btn btn-inline" href="<?= url('/schedule/create') ?>">+ Thêm sự kiện</a>
</div>

<div class="cal-nav">
    <a href="<?= url('/schedule?month=' . $prev) ?>">← Tháng trước</a>
    <b><?= e($monthLabel) ?></b>
    <a href="<?= url('/schedule?month=' . $next) ?>">Tháng sau →</a>
    <a href="<?= url('/schedule') ?>" class="cal-today">Hôm nay</a>
</div>

<div class="table-wrap">
<table class="calendar">
    <thead><tr><?php foreach ($heads as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($weeks as $week): ?>
        <tr>
        <?php foreach ($week as $c): ?>
            <td class="<?= $c['inMonth'] ? '' : 'out' ?> <?= $c['isToday'] ? 'today' : '' ?>">
                <div class="cal-daynum">
                    <span><?= (int) $c['day'] ?></span>
                    <a href="<?= url('/schedule/create?date=' . $c['date']) ?>" class="cal-add" title="Thêm sự kiện">+</a>
                </div>
                <?php foreach ($c['events'] as $ev): ?>
                    <a class="chip chip-event st-<?= strtolower($ev['status']) ?>" href="<?= url("/schedule/{$ev['id']}/edit") ?>"
                       title="<?= e(ScheduleModel::STATUS_LABELS[$ev['status']] ?? '') ?>">
                        <?= e($ev['time']) ?> <?= e($ev['title']) ?><?= $ev['repeat'] !== 'NONE' ? ' ↻' : '' ?>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($c['tasks'] as $t): ?>
                    <a class="chip chip-task <?= $t['status'] === 'DONE' ? 'is-done' : '' ?>" href="<?= url("/goals/{$t['goal_id']}") ?>" title="Hạn nhiệm vụ">
                        ⏳ <?= e($t['time']) ?> <?= e($t['title']) ?>
                    </a>
                <?php endforeach; ?>
            </td>
        <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<p class="muted">
    <span class="chip chip-event legend">Sự kiện</span>
    <span class="chip chip-task legend">⏳ Hạn nhiệm vụ</span>
    ↻ = sự kiện lặp lại. Bấm vào sự kiện để sửa hoặc xóa.
</p>