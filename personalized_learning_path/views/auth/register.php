<div class="card">
    <h2>Đăng ký tài khoản</h2>
    <form method="post" action="<?= url('/register') ?>" novalidate>
        <?= csrf_field() ?>

        <label for="full_name">Họ và tên</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name'] ?? '') ?>" required>
        <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
        <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>

        <label for="password">Mật khẩu (tối thiểu 8 ký tự)</label>
        <input type="password" id="password" name="password" required>
        <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>

        <label for="password_confirm">Nhập lại mật khẩu</label>
        <input type="password" id="password_confirm" name="password_confirm" required>
        <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>

        <button type="submit" class="btn">Đăng ký</button>
    </form>
    <p>Đã có tài khoản? <a href="<?= url('/login') ?>">Đăng nhập</a></p>
</div>