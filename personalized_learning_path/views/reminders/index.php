<h1>Nhắc nhở</h1>

<div class="card card-wide card-left">
    <h2>Tạo lời nhắc</h2>
    <form method="post" action="<?= url('/reminders') ?>" novalidate>
        <?= csrf_field() ?>

        <label for="title">Nội dung nhắc</label>
        <input type="text" id="title" name="title" value="<?= e($form['title']) ?>" required>
        <?php if (isset($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>

        <div class="grid-2">
            <div>
                <label for="remind_at">Nhắc vào lúc</label>
                <input type="datetime-local" id="remind_at" name="remind_at" value="<?= e($form['remind_at']) ?>" required>
                <?php if (isset($errors['remind_at'])): ?><p class="field-error"><?= e($errors['remind_at']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="repeat_type">Lặp lại</label>
                <select id="repeat_type" name="repeat_type">
                    <?php foreach (ReminderModel::REPEAT_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $form['repeat_type'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['repeat_type'])): ?><p class="field-error"><?= e($errors['repeat_type']) ?></p><?php endif; ?>
            </div>
        </div>

        <label for="task_id">Gắn với nhiệm vụ (không bắt buộc)</label>
        <select id="task_id" name="task_id">
            <option value="">— Không gắn —</option>
            <?php foreach ($taskOptions as $o): ?>
                <option value="<?= (int) $o['id'] ?>" <?= (string) $form['task_id'] === (string) $o['id'] ? 'selected' : '' ?>>
                    <?= e($o['goal_title']) ?> › <?= e($o['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['task_id'])): ?><p class="field-error"><?= e($errors['task_id']) ?></p><?php endif; ?>

        <button type="submit" class="btn">Tạo lời nhắc</button>
    </form>
</div>

<h2>Lời nhắc của tôi</h2>
<p class="muted">Hệ thống cũng tự nhắc nhiệm vụ sắp đến hạn và cảnh báo nhiệm vụ quá hạn ở mục Thông báo 🔔.</p>

<?php if (!$reminders): ?>
    <p class="muted">Chưa có lời nhắc nào.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Nội dung</th><th>Nhắc lúc</th><th>Lặp</th><th>Gắn với</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reminders as $r): ?>
        <tr class="<?= (int) $r['is_active'] ? '' : 'row-muted' ?>">
            <td><?= e($r['title']) ?></td>
            <td><?= e(date('H:i d/m/Y', strtotime($r['remind_at']))) ?></td>
            <td><?= e(ReminderModel::REPEAT_LABELS[$r['repeat_type']] ?? $r['repeat_type']) ?></td>
            <td><?= $r['task_title'] ? e($r['task_title']) : '—' ?></td>
            <td><?= (int) $r['is_active'] ? 'Đang bật' : 'Đã tắt / đã nhắc' ?></td>
            <td class="actions">
                <form method="post" action="<?= url("/reminders/{$r['id']}/toggle") ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-btn"><?= (int) $r['is_active'] ? 'Tắt' : 'Bật' ?></button>
                </form>
                <form method="post" action="<?= url("/reminders/{$r['id']}/delete") ?>" class="inline-form"
                      onsubmit="return confirm('Xóa lời nhắc này?');">
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