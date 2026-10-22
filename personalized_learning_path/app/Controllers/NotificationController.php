<?php
declare(strict_types=1);

class NotificationController {
    private NotificationModel $notifications;
    private int $userId;

    public function __construct() {
        require_login();
        $this->notifications = new NotificationModel();
        $this->userId = (int) SessionHelper::userId();
    }

    public function index(): void {
        $this->notifications->generate($this->userId, true);
        view('notifications/index', [
            'title' => 'Thông báo',
            'items' => $this->notifications->listByUser($this->userId, 50),
        ]);
    }

    public function markRead(string $id): void {
        $this->checkCsrf();
        $this->notifications->markRead((int) $id, $this->userId);
        redirect('/notifications');
    }

    public function markAllRead(): void {
        $this->checkCsrf();
        $this->notifications->markAllRead($this->userId);
        SessionHelper::flash('success', 'Đã đánh dấu tất cả là đã đọc.');
        redirect('/notifications');
    }

    private function checkCsrf(): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect('/notifications');
        }
    }
}