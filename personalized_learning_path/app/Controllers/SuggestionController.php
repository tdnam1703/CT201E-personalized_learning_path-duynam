<?php
declare(strict_types=1);

class SuggestionController {
    private SuggestionModel $suggestions;
    private int $userId;

    public function __construct() {
        require_login();
        $this->suggestions = new SuggestionModel();
        $this->userId      = (int) SessionHelper::userId();
    }

    public function index(): void {
        $this->suggestions->generate($this->userId);
        view('suggestions/index', [
            'title'   => 'Gợi ý điều chỉnh lộ trình',
            'pending' => $this->suggestions->listPending($this->userId),
            'history' => $this->suggestions->listHistory($this->userId, 15),
        ]);
    }

    public function accept(string $id): void {
        $this->checkCsrf();
        $error = $this->suggestions->accept((int) $id, $this->userId);
        if ($error) {
            SessionHelper::flash('error', $error);
        } else {
            SessionHelper::flash('success', 'Đã áp dụng gợi ý.');
        }
        redirect('/suggestions');
    }

    public function dismiss(string $id): void {
        $this->checkCsrf();
        if ($this->suggestions->dismiss((int) $id, $this->userId)) {
            SessionHelper::flash('success', 'Đã bỏ qua gợi ý.');
        } else {
            SessionHelper::flash('error', 'Gợi ý không còn hiệu lực.');
        }
        redirect('/suggestions');
    }

    private function checkCsrf(): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect('/suggestions');
        }
    }
}