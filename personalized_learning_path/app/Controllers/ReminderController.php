<?php
declare(strict_types=1);

class ReminderController {
    private ReminderModel $reminders;
    private TaskModel $tasks;
    private int $userId;

    public function __construct() {
        require_login();
        $this->reminders = new ReminderModel();
        $this->tasks = new TaskModel();
        $this->userId = (int) SessionHelper::userId();
    }

    public function index(): void {
        $this->render(['title' => '', 'remind_at' => '', 'repeat_type' => 'NONE', 'task_id' => '']);
    }

    public function store(): void {
        $this->checkCsrf();

        $form = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'remind_at' => trim((string) ($_POST['remind_at'] ?? '')),
            'repeat_type' => (string) ($_POST['repeat_type'] ?? 'NONE'),
            'task_id' => trim((string) ($_POST['task_id'] ?? '')),
        ];
        $errors = [];
        $goalId = null;
        $taskId = null;

        if ($form['title'] === '' || mb_strlen($form['title']) > 200) {
            $errors['title'] = 'Nội dung nhắc bắt buộc và tối đa 200 ký tự.';
        }
        if (!array_key_exists($form['repeat_type'], ReminderModel::REPEAT_LABELS)) {
            $errors['repeat_type'] = 'Kiểu lặp không hợp lệ.';
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $form['remind_at']);
        if ($dt === false || $dt->format('Y-m-d\TH:i') !== $form['remind_at']) {
            $errors['remind_at'] = 'Thời điểm nhắc không hợp lệ.';
        } elseif ($dt <= new DateTime()) {
            $errors['remind_at'] = 'Thời điểm nhắc phải ở tương lai.';
        }

        if ($form['task_id'] !== '') {
            $task = ctype_digit($form['task_id']) ? $this->tasks->findOwned((int) $form['task_id'], $this->userId) : null;
            if (!$task) {
                $errors['task_id'] = 'Nhiệm vụ không hợp lệ.';
            } else {
                $taskId = (int) $task['id'];
                $goalId = (int) $task['goal_id'];
            }
        }

        if ($errors) {
            http_response_code(422);
            $this->render($form, $errors);
            return;
        }

        $this->reminders->create($this->userId, [
            'goal_id' => $goalId,
            'task_id' => $taskId,
            'title' => $form['title'],
            'remind_at' => $dt->format('Y-m-d H:i:s'),
            'repeat_type' => $form['repeat_type'],
        ]);
        SessionHelper::flash('success', 'Đã tạo lời nhắc.');
        redirect('/reminders');
    }

    public function toggle(string $id): void {
        $this->checkCsrf();
        $r = $this->reminders->findOwned((int) $id, $this->userId) ?? $this->notFound();

        if (!(int) $r['is_active']) {
            //bật lại lời nhắc một lần đã qua giờ thì vô nghĩa
            if ($r['repeat_type'] === 'NONE' && $r['remind_at'] <= date('Y-m-d H:i:s')) {
                SessionHelper::flash('error', 'Thời điểm nhắc đã qua. Hãy tạo lời nhắc mới.');
                redirect('/reminders');
            }
            $this->reminders->setActive((int) $r['id'], $this->userId, true);
            SessionHelper::flash('success', 'Đã bật lời nhắc.');
        } else {
            $this->reminders->setActive((int) $r['id'], $this->userId, false);
            SessionHelper::flash('success', 'Đã tắt lời nhắc.');
        }
        redirect('/reminders');
    }

    public function destroy(string $id): void {
        $this->checkCsrf();
        $r = $this->reminders->findOwned((int) $id, $this->userId) ?? $this->notFound();
        $this->reminders->delete((int) $r['id'], $this->userId);
        SessionHelper::flash('success', 'Đã xóa lời nhắc.');
        redirect('/reminders');
    }

    private function render(array $form, array $errors = []): void {
        view('reminders/index', [
            'title' => 'Nhắc nhở',
            'reminders' => $this->reminders->listByUser($this->userId),
            'taskOptions' => (new StudyModel())->activeTasks($this->userId),
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    private function notFound(): never {
        http_response_code(404);
        view('errors/404', ['title' => 'Không tìm thấy']);
        exit;
    }

    private function checkCsrf(): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect('/reminders');
        }
    }
}