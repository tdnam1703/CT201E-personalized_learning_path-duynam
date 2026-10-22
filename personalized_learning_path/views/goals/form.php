<div class="card card-wide">
    <h2><?= e($title) ?></h2>
    <form method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="title">Tên mục tiêu</label>
        <input type="text" id="title" name="title" value="<?= e($goal['title'] ?? '') ?>" required>
        <?php if (isset($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>

        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="4"><?= e($goal['description'] ?? '') ?></textarea>

        <div class="grid-2">
            <div>
                <label for="priority">Mức ưu tiên</label>
                <select id="priority" name="priority">
                    <?php foreach (PRIORITY_LABELS as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($goal['priority'] ?? 'MEDIUM') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['priority'])): ?><p class="field-error"><?= e($errors['priority']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <?php foreach (GOAL_STATUSES as $st): ?>
                        <option value="<?= e($st) ?>" <?= ($goal['status'] ?? GOAL_NOT_STARTED) === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><p class="field-error"><?= e($errors['status']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="start_date">Ngày bắt đầu</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($goal['start_date'] ?? '') ?>">
                <?php if (isset($errors['start_date'])): ?><p class="field-error"><?= e($errors['start_date']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="target_date">Hạn hoàn thành</label>
                <input type="date" id="target_date" name="target_date" value="<?= e($goal['target_date'] ?? '') ?>">
                <?php if (isset($errors['target_date'])): ?><p class="field-error"><?= e($errors['target_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn">Lưu</button>
        <p><a href="<?= url('/goals') ?>">← Quay lại danh sách</a></p>
    </form>
</div>