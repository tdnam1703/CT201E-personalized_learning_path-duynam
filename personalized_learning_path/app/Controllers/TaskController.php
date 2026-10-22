<?php
declare(strict_types=1);

class TaskController {
    private GoalModel $goals;
    private PhaseModel $phases;
    private TaskModel $tasks;
    private int $userId;

    public function __construct() {
        require_login();
        $this->goals  = new GoalModel();
        $this->phases = new PhaseModel();
        $this->tasks  = new TaskModel();
        $this->userId = (int) SessionHelper::userId();
    }

    #thêm nhiệm vụ mới từ user
    public function create(string $goalId): void {
        $goal    = $this->goalOr404((int) $goalId);
        $phaseId = (string) ($_GET['phase_id'] ?? '');

        $this->renderForm($goal, 'Thêm nhiệm vụ', url("/goals/{$goal['id']}/tasks"), [
            'title' => '', 'description' => '', 'priority' => 'MEDIUM', 'status' => TASK_TODO,
            'phase_id' => ctype_digit($phaseId) ? $phaseId : '',
            'start_date' => '', 'due_date' => '', 'estimated_minutes' => '',
        ]);
    }

    public function store(string $goalId): void {
        $goal = $this->goalOr404((int) $goalId);
        $this->checkCsrf($goal);
        [$form, $errors] = $this->collectInput($goal);

        if ($errors) {
            $this->renderForm($goal, 'Thêm nhiệm vụ', url("/goals/{$goal['id']}/tasks"), $form, $errors);
            return;
        }
        $this->tasks->create((int) $goal['id'], $this->toDb($form));
        $this->tasks->syncGoalStatus((int) $goal['id']);
        SessionHelper::flash('success', 'Đã thêm nhiệm vụ.');
        redirect("/goals/{$goal['id']}");
    }

    #cập nhật nhiệm vụ
    public function edit(string $id): void {
        $task = $this->taskOr404((int) $id);
        $goal = $this->goalOr404((int) $task['goal_id']);

        $this->renderForm($goal, 'Sửa nhiệm vụ', url("/tasks/{$task['id']}/update"), [
            'title' => $task['title'],
            'description' => (string) $task['description'],
            'priority' => $task['priority'],
            'status' => $task['status'],
            'phase_id' => $task['phase_id'] === null ? '' : (string) $task['phase_id'],
            'start_date' => $this->toLocal($task['start_date']),
            'due_date' => $this->toLocal($task['due_date']),
            'estimated_minutes' => $task['estimated_minutes'] === null ? '' : (string) $task['estimated_minutes'],
        ]);
    }

    public function update(string $id): void {
        $task = $this->taskOr404((int) $id);
        $goal = $this->goalOr404((int) $task['goal_id']);
        $this->checkCsrf($goal);
        [$form, $errors] = $this->collectInput($goal);

        if ($errors) {
            $this->renderForm($goal, 'Sửa nhiệm vụ', url("/tasks/{$task['id']}/update"), $form, $errors);
            return;
        }
        $data = $this->toDb($form);
        $data['progress_percent'] = $this->progressFor($data['status'], (int) $task['progress_percent']);
        $this->tasks->update((int) $task['id'], $data);
        $this->tasks->syncGoalStatus((int) $goal['id']);
        SessionHelper::flash('success', 'Đã cập nhật nhiệm vụ.');
        redirect("/goals/{$goal['id']}");
    }

    #xóa nhiệm vụ
    public function destroy(string $id): void {
        $task = $this->taskOr404((int) $id);
        $goal = $this->goalOr404((int) $task['goal_id']);
        $this->checkCsrf($goal);
        $this->tasks->delete((int) $task['id']);
        $this->tasks->syncGoalStatus((int) $goal['id']);
        SessionHelper::flash('success', 'Đã xóa nhiệm vụ.');
        redirect("/goals/{$goal['id']}");
    }

    #cập nhật nhanh tiến độ trang chi tiết mục tiêu
    public function changeStatus(string $id): void {
        $task = $this->taskOr404((int) $id);
        $goal = $this->goalOr404((int) $task['goal_id']);
        $this->checkCsrf($goal);

        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, [TASK_TODO, TASK_IN_PROGRESS, TASK_DONE], true)) {
            SessionHelper::flash('error', 'Trạng thái không hợp lệ.');
            redirect("/goals/{$goal['id']}");
        }
        $this->rejectIfCancelled($task, $goal);

        $progress = $this->progressFor($status, (int) $task['progress_percent']);
        $this->tasks->setProgress((int) $task['id'], $status, $progress);
        $this->tasks->syncGoalStatus((int) $goal['id']);

        SessionHelper::flash('success', 'Đã cập nhật nhiệm vụ "' . $task['title'] . '".');
        redirect("/goals/{$goal['id']}");
    }

    public function changeProgress(string $id): void {
        $task = $this->taskOr404((int) $id);
        $goal = $this->goalOr404((int) $task['goal_id']);
        $this->checkCsrf($goal);
        $this->rejectIfCancelled($task, $goal);

        $raw = trim((string) ($_POST['progress'] ?? ''));
        if (!ctype_digit($raw) || (int) $raw > 100) {
            SessionHelper::flash('error', 'Tiến độ phải là số nguyên từ 0 đến 100.');
            redirect("/goals/{$goal['id']}");
        }
        $progress = (int) $raw;
        $status   = $progress === 100 ? TASK_DONE : ($progress === 0 ? TASK_TODO : TASK_IN_PROGRESS);

        $this->tasks->setProgress((int) $task['id'], $status, $progress);
        $this->tasks->syncGoalStatus((int) $goal['id']);

        SessionHelper::flash('success', "Đã cập nhật tiến độ: {$progress}%.");
        redirect("/goals/{$goal['id']}");
    }

    private function renderForm(array $goal, string $title, string $action, array $task, array $errors = []): void {
        $phases = $this->phases->listByGoal((int) $goal['id']);
        view('tasks/form', compact('goal', 'title', 'action', 'task', 'errors', 'phases'));
    }

    private function goalOr404(int $id): array {
        $goal = $this->goals->findOwned($id, $this->userId);
        if (!$goal) {
            $this->notFound();
        }
        return $goal;
    }

    private function taskOr404(int $id): array {
        $task = $this->tasks->findOwned($id, $this->userId);
        if (!$task) {
            $this->notFound();
        }
        return $task;
    }

    # % hoàn thành phù hợp với trạng thái
    private function progressFor(string $status, int $current): int {
        return match ($status) {
            TASK_DONE        => 100,
            TASK_TODO        => 0,
            TASK_IN_PROGRESS => $current === 100 ? 0 : $current,
            default          => $current,
        };
    }

    private function rejectIfCancelled(array $task, array $goal): void {
        if ($task['status'] === TASK_CANCELLED) {
            SessionHelper::flash('error', 'Nhiệm vụ đã hủy. Hãy dùng nút Sửa để đổi trạng thái.');
            redirect("/goals/{$goal['id']}");
        }
    }

    private function notFound(): never {
        http_response_code(404);
        view('errors/404', ['title' => 'Không tìm thấy']);
        exit;
    }

    private function checkCsrf(array $goal): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect("/goals/{$goal['id']}");
        }
    }

    #kiểm tra giá trị từ <input type="datetime-local"> (dạng 2026-10-25T14:30)
    private function isValidLocal(string $value): bool {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        return $d !== false && $d->format('Y-m-d\TH:i') === $value;
    }

    #database (2026-10-25 14:30:00) -> input datetime-local (2026-10-25T14:30)
    private function toLocal(?string $value): string {
        return $value ? str_replace(' ', 'T', substr($value, 0, 16)) : '';
    }

    #đọc và kiểm tra form
    private function collectInput(array $goal): array {
        $form = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'priority' => (string) ($_POST['priority'] ?? 'MEDIUM'),
            'status' => (string) ($_POST['status'] ?? TASK_TODO),
            'phase_id' => trim((string) ($_POST['phase_id'] ?? '')),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'due_date' => trim((string) ($_POST['due_date'] ?? '')),
            'estimated_minutes' => trim((string) ($_POST['estimated_minutes'] ?? '')),
        ];
        $errors = [];

        if ($form['title'] === '' || mb_strlen($form['title']) > 200) {
            $errors['title'] = 'Tên nhiệm vụ bắt buộc và tối đa 200 ký tự.';
        }
        if (!in_array($form['priority'], PRIORITIES, true)) {
            $errors['priority'] = 'Mức ưu tiên không hợp lệ.';
        }
        if (!in_array($form['status'], TASK_STATUSES, true)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        if ($form['phase_id'] !== '') {
            if (!ctype_digit($form['phase_id'])
                || !$this->tasks->phaseBelongsToGoal((int) $form['phase_id'], (int) $goal['id'])) {
                $errors['phase_id'] = 'Giai đoạn không hợp lệ.';
            }
        }
        foreach (['start_date', 'due_date'] as $field) {
            if ($form[$field] !== '' && !$this->isValidLocal($form[$field])) {
                $errors[$field] = 'Ngày giờ không hợp lệ.';
            }
        }
        if (!isset($errors['start_date']) && !isset($errors['due_date'])) {
            if ($form['start_date'] !== '' && $form['due_date'] !== '' && $form['due_date'] < $form['start_date']) {
                $errors['due_date'] = 'Hạn hoàn thành phải sau thời điểm bắt đầu.';
            }
            if ($form['due_date'] !== '' && $goal['target_date'] && substr($form['due_date'], 0, 10) > $goal['target_date']) {
                $errors['due_date'] = 'Hạn nhiệm vụ không được sau hạn của mục tiêu ('
                    . date('d/m/Y', strtotime($goal['target_date'])) . ').';
            }
        }
        if ($form['estimated_minutes'] !== '') {
            if (!ctype_digit($form['estimated_minutes']) || (int) $form['estimated_minutes'] < 1
                || (int) $form['estimated_minutes'] > 100000) {
                $errors['estimated_minutes'] = 'Thời gian dự kiến là số phút nguyên từ 1 đến 100000.';
            }
        }

        return [$form, $errors];
    }

    #chuyển dữ liệu form hợp lệ sang dạng lưu database
    private function toDb(array $form): array
    {
        $dt = static fn (string $v): ?string => $v === '' ? null : str_replace('T', ' ', $v) . ':00';

        return [
            'title' => $form['title'],
            'description' => $form['description'] === '' ? null : $form['description'],
            'priority' => $form['priority'],
            'status' => $form['status'],
            'phase_id' => $form['phase_id'] === '' ? null : (int) $form['phase_id'],
            'start_date' => $dt($form['start_date']),
            'due_date' => $dt($form['due_date']),
            'estimated_minutes' => $form['estimated_minutes'] === '' ? null : (int) $form['estimated_minutes'],
        ];
    }
}