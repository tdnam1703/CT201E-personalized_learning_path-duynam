<div class="card card-wide">
    <h2><?= e($title) ?></h2>
    <form method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="title">Tên sự kiện</label>
        <input type="text" id="title" name="title" value="<?= e($event['title']) ?>" required>
        <?php if (isset($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>

        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3"><?= e($event['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><p class="field-error"><?= e($errors['description']) ?></p><?php endif; ?>

        <div class="grid-2">
            <div>
                <label for="start_at">Bắt đầu</label>
                <input type="datetime-local" id="start_at" name="start_at" value="<?= e($event['start_at']) ?>" required>
                <?php if (isset($errors['start_at'])): ?><p class="field-error"><?= e($errors['start_at']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="end_at">Kết thúc</label>
                <input type="datetime-local" id="end_at" name="end_at" value="<?= e($event['end_at']) ?>" required>
                <?php if (isset($errors['end_at'])): ?><p class="field-error"><?= e($errors['end_at']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="repeat_type">Lặp lại</label>
                <select id="repeat_type" name="repeat_type">
                    <?php foreach (ReminderModel::REPEAT_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $event['repeat_type'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['repeat_type'])): ?><p class="field-error"><?= e($errors['repeat_type']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="repeat_until">Lặp đến ngày (bỏ trống = không giới hạn)</label>
                <input type="date" id="repeat_until" name="repeat_until" value="<?= e($event['repeat_until']) ?>">
                <?php if (isset($errors['repeat_until'])): ?><p class="field-error"><?= e($errors['repeat_until']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <?php foreach (ScheduleModel::STATUS_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $event['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><p class="field-error"><?= e($errors['status']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="task_id">Gắn với nhiệm vụ</label>
                <select id="task_id" name="task_id">
                    <option value="">— Không gắn —</option>
                    <?php foreach ($taskOptions as $o): ?>
                        <option value="<?= (int) $o['id'] ?>" <?= (string) $event['task_id'] === (string) $o['id'] ? 'selected' : '' ?>>
                            <?= e($o['goal_title']) ?> › <?= e($o['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['task_id'])): ?><p class="field-error"><?= e($errors['task_id']) ?></p><?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn">Lưu</button>
    </form>

    <?php if (str_contains($action, '/update')): ?>
        <form method="post" action="<?= e(str_replace('/update', '/delete', $action)) ?>"
              onsubmit="return confirm('Xóa sự kiện này? Nếu là sự kiện lặp, tất cả các lần lặp sẽ bị xóa.');">
            <?= csrf_field() ?>
            <button type="submit" class="link-btn danger">Xóa sự kiện</button>
        </form>
    <?php endif; ?>
    <p><a href="<?= url('/schedule') ?>">← Quay lại lịch</a></p>
</div>