<?php
declare(strict_types=1);

class StudyController {
    private StudyModel $study;
    private TaskModel $tasks;
    private int $userId;

    public function __construct() {
        require_login();
        $this->study  = new StudyModel();
        $this->tasks  = new TaskModel();
        $this->userId = (int) SessionHelper::userId();
    }

    public function index(): void {
        view('study/index', [
            'title' => 'Học tập',
            'running' => $this->study->running($this->userId),
            'taskOptions' => $this->study->activeTasks($this->userId),
            'todayMinutes' => $this->study->todayMinutes($this->userId),
            'dailyGoal' => $this->study->dailyGoal($this->userId),
            'week' => $this->study->lastDays($this->userId, 7),
            'recent' => $this->study->recent($this->userId, 10),
        ]);
    }

    public function start(): void {
        $this->checkCsrf();

        if ($this->study->running($this->userId)) {
            SessionHelper::flash('error', 'Bạn đang có một phiên học chưa kết thúc.');
            redirect('/study');
        }

        $raw    = trim((string) ($_POST['task_id'] ?? ''));
        $taskId = null;
        if ($raw !== '') {
            $task = ctype_digit($raw) ? $this->tasks->findOwned((int) $raw, $this->userId) : null;
            if (!$task || !in_array($task['status'], [TASK_TODO, TASK_IN_PROGRESS], true)) {
                SessionHelper::flash('error', 'Nhiệm vụ không hợp lệ hoặc đã hoàn thành.');
                redirect('/study');
            }
            $taskId = (int) $task['id'];

            //bắt đầu học một nhiệm vụ "Cần làm" thì chuyển nó sang "Đang làm"
            if ($task['status'] === TASK_TODO) {
                $this->tasks->setProgress($taskId, TASK_IN_PROGRESS, 0);
                $this->tasks->syncGoalStatus((int) $task['goal_id']);
            }
        }

        $this->study->start($this->userId, $taskId);
        SessionHelper::flash('success', 'Đã bắt đầu phiên học. Chúc bạn học tốt!');
        redirect('/study');
    }

    public function stop(string $id): void {
        $this->checkCsrf();

        $note = trim((string) ($_POST['note'] ?? ''));
        $note = $note === '' ? null : mb_substr($note, 0, 500);

        if ($this->study->stop((int) $id, $this->userId, $note)) {
            SessionHelper::flash('success', 'Đã kết thúc phiên học.');
        } else {
            SessionHelper::flash('error', 'Không tìm thấy phiên học đang chạy.');
        }
        redirect('/study');
    }

    public function destroy(string $id): void {
        $this->checkCsrf();

        if ($this->study->delete((int) $id, $this->userId)) {
            SessionHelper::flash('success', 'Đã xóa phiên học.');
        } else {
            SessionHelper::flash('error', 'Không tìm thấy phiên học.');
        }
        redirect('/study');
    }

    private function checkCsrf(): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect('/study');
        }
    }
}