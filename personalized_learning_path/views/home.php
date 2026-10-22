<h1>Quản lý lộ trình học tập cá nhân</h1>
<p>Đặt mục tiêu, chia giai đoạn, theo dõi nhiệm vụ và hoàn thành đúng hạn.</p>
<?php if (SessionHelper::isLoggedIn()): ?>
    <p><a class="btn btn-inline" href="<?= url('/goals') ?>">Xem mục tiêu của tôi</a></p>
<?php endif; ?>