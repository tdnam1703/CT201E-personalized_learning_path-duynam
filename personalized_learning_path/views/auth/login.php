<div class="card">
    <h2>Đăng nhập</h2>
    <?php if (isset($errors['login'])): ?>
        <div class="alert alert-error"><?= e($errors['login']) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= url('/login') ?>" novalidate>
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>

        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn">Đăng nhập</button>
    </form>
    <p>Chưa có tài khoản? <a href="<?= url('/register') ?>">Đăng ký</a></p>
</div>