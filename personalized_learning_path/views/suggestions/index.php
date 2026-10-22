<?php
$icons  = ['EXTEND_DEADLINE' => '📆', 'RESCHEDULE' => '🔁', 'SPLIT_TASK' => '✂️', 'REDUCE_LOAD' => '⚖️', 'OTHER' => '💡'];
$autoApply = ['EXTEND_DEADLINE', 'RESCHEDULE']; // loại gợi ý mà nút "Áp dụng" tự sửa dữ liệu
$statusText = ['ACCEPTED' => 'Đã áp dụng', 'DISMISSED' => 'Đã bỏ qua'];
?>
<h1>Gợi ý điều chỉnh lộ trình</h1>
<p class="muted">Hệ thống phân tích mục tiêu và nhiệm vụ của bạn để đề xuất cách điều chỉnh. Bạn luôn là người quyết định.</p>

<?php if (!$pending): ?>
    <div class="card card-wide card-left"><p>🎉 Hiện chưa có gợi ý nào. Lộ trình của bạn đang ổn!</p></div>
<?php else: ?>
    <?php foreach ($pending as $s): ?>
    <div class="suggest-card">
        <div class="suggest-head">
            <span><?= $icons[$s['suggestion_type']] ?? '💡' ?></span>
            <b><?= e(SuggestionModel::TYPE_LABELS[$s['suggestion_type']] ?? $s['suggestion_type']) ?></b>
            <small class="muted"><?= e(date('H:i d/m/Y', strtotime($s['created_at']))) ?></small>
        </div>
        <p><?= e($s['content']) ?></p>
        <?php if ($s['suggested_date']): ?>
            <p class="muted">Ngày đề xuất: <b><?= e(date('d/m/Y', strtotime($s['suggested_date']))) ?></b></p>
        <?php endif; ?>
        <div class="suggest-actions">
            <form method="post" action="<?= url("/suggestions/{$s['id']}/accept") ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-inline">
                    <?= in_array($s['suggestion_type'], $autoApply, true) ? 'Áp dụng' : 'Đã xử lý' ?>
                </button>
            </form>
            <form method="post" action="<?= url("/suggestions/{$s['id']}/dismiss") ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="link-btn">Bỏ qua</button>
            </form>
            <a href="<?= url("/goals/{$s['goal_id']}") ?>">Mở mục tiêu "<?= e($s['goal_title']) ?>"</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($history): ?>
<h2 class="section-gap">Đã xử lý gần đây</h2>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Loại</th><th>Nội dung</th><th>Kết quả</th><th>Lúc</th></tr></thead>
    <tbody>
    <?php foreach ($history as $s): ?>
        <tr class="row-muted">
            <td><?= e(SuggestionModel::TYPE_LABELS[$s['suggestion_type']] ?? $s['suggestion_type']) ?></td>
            <td><?= e($s['content']) ?></td>
            <td><?= e($statusText[$s['status']] ?? $s['status']) ?></td>
            <td><?= $s['resolved_at'] ? e(date('H:i d/m/Y', strtotime($s['resolved_at']))) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>