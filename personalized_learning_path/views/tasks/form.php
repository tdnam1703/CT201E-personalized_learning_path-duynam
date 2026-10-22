<div class="card card-wide">
    <h2><?= e($title) ?></h2>
    <p class="muted">Mục tiêu: <b><?= e($goal['title']) ?></b></p>
    <form method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="title">Tên nhiệm vụ</label>
        <input type="text" id="title" name="title" value="<?= e($task['title'] ?? '') ?>" required>
        <?php if (isset($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>

        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3"><?= e($task['description'] ?? '') ?></textarea>

        <div class="grid-2">
            <div>
                <label for="phase_id">Giai đoạn</label>
                <select id="phase_id" name="phase_id">
                    <option value="">— Không thuộc giai đoạn nào —</option>
                    <?php foreach ($phases as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (string) ($task['phase_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
                            <?= (int) $p['order_no'] ?>. <?= e($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['phase_id'])): ?><p class="field-error"><?= e($errors['phase_id']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="estimated_minutes">Thời gian dự kiến (phút)</label>
                <input type="number" id="estimated_minutes" name="estimated_minutes" min="1" value="<?= e((string) ($task['estimated_minutes'] ?? '')) ?>">
                <?php if (isset($errors['estimated_minutes'])): ?><p class="field-error"><?= e($errors['estimated_minutes']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="priority">Mức ưu tiên</label>
                <select id="priority" name="priority">
                    <?php foreach (PRIORITY_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($task['priority'] ?? 'MEDIUM') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['priority'])): ?><p class="field-error"><?= e($errors['priority']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <?php foreach (TASK_STATUS_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($task['status'] ?? TASK_TODO) === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><p class="field-error"><?= e($errors['status']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="start_date">Bắt đầu</label>
                <input type="datetime-local" id="start_date" name="start_date" value="<?= e($task['start_date'] ?? '') ?>">
                <?php if (isset($errors['start_date'])): ?><p class="field-error"><?= e($errors['start_date']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="due_date">Hạn hoàn thành</label>
                <input type="datetime-local" id="due_date" name="due_date" value="<?= e($task['due_date'] ?? '') ?>">
                <?php if (isset($errors['due_date'])): ?><p class="field-error"><?= e($errors['due_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn">Lưu</button>
        <p><a href="<?= url("/goals/{$goal['id']}") ?>">← Quay lại mục tiêu</a></p>
    </form>
</div>