<div class="card card-wide">
    <h2><?= e($title) ?></h2>
    <p class="muted">Mục tiêu: <b><?= e($goal['title']) ?></b></p>
    <form method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="title">Tên giai đoạn</label>
        <input type="text" id="title" name="title" value="<?= e($phase['title'] ?? '') ?>" required>
        <?php if (isset($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>

        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3"><?= e($phase['description'] ?? '') ?></textarea>

        <div class="grid-2">
            <div>
                <label for="order_no">Thứ tự</label>
                <input type="number" id="order_no" name="order_no" min="1" value="<?= e((string) ($phase['order_no'] ?? '')) ?>" required>
                <?php if (isset($errors['order_no'])): ?><p class="field-error"><?= e($errors['order_no']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <?php foreach (PHASE_STATUSES as $st): ?>
                        <option value="<?= e($st) ?>" <?= ($phase['status'] ?? GOAL_NOT_STARTED) === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><p class="field-error"><?= e($errors['status']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="start_date">Ngày bắt đầu</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($phase['start_date'] ?? '') ?>">
                <?php if (isset($errors['start_date'])): ?><p class="field-error"><?= e($errors['start_date']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="end_date">Ngày kết thúc</label>
                <input type="date" id="end_date" name="end_date" value="<?= e($phase['end_date'] ?? '') ?>">
                <?php if (isset($errors['end_date'])): ?><p class="field-error"><?= e($errors['end_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn">Lưu</button>
        <p><a href="<?= url("/goals/{$goal['id']}") ?>">← Quay lại mục tiêu</a></p>
    </form>
</div>