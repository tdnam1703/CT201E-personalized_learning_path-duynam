<?php
declare(strict_types=1);

class GoalController {
    private GoalModel $goals;
    private int $userId;

    public function __construct() {
        //bắt buộc đăng nhập
        require_login();
        $this->goals  = new GoalModel();

        //lấy id người dùng
        $this->userId = (int) SessionHelper::userId();
    }

    public function index(): void {
        $filter = $_GET['status'] ?? null;
        $filter = in_array($filter, GOAL_STATUSES, true) ? $filter : null;

        //render view (goals/index)
        view('goals/index', [
            'title' => 'Mục tiêu của tôi',
            'goals' => $this->goals->listByUser($this->userId, $filter),
            'filter' => $filter,
        ]);
    }

    public function show(string $id): void
    {
        $goal = $this->findOr404((int) $id);
        view('goals/show', [
            'title'  => $goal['title'],
            'goal'   => $goal,
            'phases' => (new PhaseModel())->listByGoal((int) $goal['id']),
            'tasks'  => (new TaskModel())->listByGoal((int) $goal['id']),
        ]);
    }
    
    #thêm mục tiêu mới
    public function create(): void {
        $this->renderForm('Thêm mục tiêu', url('/goals'), [
            'title' => '', 'description' => '', 'priority' => 'MEDIUM',
            'status' => GOAL_NOT_STARTED, 'start_date' => '', 'target_date' => '',
        ]);
    }

    #kiểm tra trước khi thêm vào
    public function store(): void {
        $this->checkCsrf();
        [$data, $errors] = $this->collectInput();

        if ($errors) {
            $this->renderForm('Thêm mục tiêu', url('/goals'), $data, $errors);
            return;
        }
        $this->goals->create($this->userId, $data);
        SessionHelper::flash('success', 'Đã thêm mục tiêu mới.');
        redirect('/goals');
    }

    #sửa mục tiêu
    public function edit(string $id): void {
        //kiểm tra mục tiêu có thuộc về user hiện tại không
        $goal = $this->findOr404((int) $id);
        $this->renderForm('Sửa mục tiêu', url("/goals/{$goal['id']}/update"), $goal);
    }

    #cập nhật mục tiêu
    public function update(string $id): void {
        $goal = $this->findOr404((int) $id);
        $this->checkCsrf();
        [$data, $errors] = $this->collectInput();

        if ($errors) {
            $this->renderForm('Sửa mục tiêu', url("/goals/{$goal['id']}/update"), $data, $errors);
            return;
        }
        $this->goals->update((int) $goal['id'], $this->userId, $data);
        SessionHelper::flash('success', 'Đã cập nhật mục tiêu.');
        redirect('/goals');
    }

    #xóa mục tiêu
    public function destroy(string $id): void {
        $goal = $this->findOr404((int) $id);
        $this->checkCsrf();
        $this->goals->delete((int) $goal['id'], $this->userId);
        SessionHelper::flash('success', 'Đã xóa mục tiêu.');
        redirect('/goals');
    }

    #dùng view('goals/form') để hiển thị
    private function renderForm(string $title, string $action, array $goal, array $errors = []): void {
        view('goals/form', compact('title', 'action', 'goal', 'errors'));
    }

    #báo lỗi 404 nếu không tìm thấy mục tiêu theo user
    private function findOr404(int $id): array {
        $goal = $this->goals->findOwned($id, $this->userId);
        if (!$goal) {
            http_response_code(404);
            view('errors/404', ['title' => 'Không tìm thấy mục tiêu']);
            exit;
        }
        return $goal;
    }

    #phòng tấn công giả mạo form
    private function checkCsrf(): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect('/goals');
        }
    }

    private function collectInput(): array {
        //lấy dữ liệu từ $_POST
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'priority' => (string) ($_POST['priority'] ?? 'MEDIUM'),
            'status' => (string) ($_POST['status'] ?? GOAL_NOT_STARTED),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'target_date' => trim((string) ($_POST['target_date'] ?? '')),
        ];
        $errors = [];

        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Tên mục tiêu bắt buộc và tối đa 200 ký tự.';
        }
        if (!in_array($data['priority'], PRIORITIES, true)) {
            $errors['priority'] = 'Mức ưu tiên không hợp lệ.';
        }
        if (!in_array($data['status'], GOAL_STATUSES, true)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        foreach (['start_date', 'target_date'] as $field) {
            if ($data[$field] !== '' && !is_valid_date($data[$field])) {
                $errors[$field] = 'Ngày không hợp lệ.';
            }
        }
        if (!isset($errors['start_date']) && !isset($errors['target_date']) && $data['start_date'] !== '' && $data['target_date'] !== '' && $data['target_date'] < $data['start_date']) {
            $errors['target_date'] = 'Hạn hoàn thành phải sau hoặc bằng ngày bắt đầu.';
        }

        //sau khi validate chuyển chuỗi rỗng thành null
        $data['start_date']  = $data['start_date'] === '' ? null : $data['start_date'];
        $data['target_date'] = $data['target_date'] === '' ? null : $data['target_date'];
        $data['description'] = $data['description'] === '' ? null : $data['description'];

        return [$data, $errors];
    }
}