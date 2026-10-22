<?php
declare(strict_types=1);

class ScheduleController
{
    private ScheduleModel $schedule;
    private TaskModel $tasks;
    private int $userId;

    public function __construct() {
        require_login();
        $this->schedule = new ScheduleModel();
        $this->tasks    = new TaskModel();
        $this->userId   = (int) SessionHelper::userId();
    }

    #lịch tháng
    public function index(): void {
        $ym = (string) ($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) {
            $ym = date('Y-m');
        }
        $weekStart = $this->schedule->weekStartDay($this->userId);
        $first     = new DateTimeImmutable($ym . '-01');

        #lưới lịch: từ đầu tuần chứa ngày 1 đến cuối tuần chứa ngày cuối tháng
        $offset = ((int) $first->format('N') - $weekStart + 7) % 7;
        $gridStart = $first->modify("-{$offset} day");
        $last = $first->modify('last day of this month');
        $endOffset = 6 - (((int) $last->format('N') - $weekStart + 7) % 7);
        $gridEnd = $last->modify("+{$endOffset} day");

        $today = date('Y-m-d');
        $days  = [];
        for ($d = $gridStart; $d <= $gridEnd; $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $days[$key] = [
                'date' => $key, 'day' => (int) $d->format('j'),
                'inMonth' => $d->format('Y-m') === $ym, 'isToday' => $key === $today,
                'events' => [], 'tasks' => [],
            ];
        }

        $from = $gridStart->format('Y-m-d');
        $to   = $gridEnd->format('Y-m-d');
        $this->placeEvents($days, $this->schedule->eventsInRange($this->userId, $from, $to), $gridEnd);

        foreach ($this->schedule->tasksDueInRange($this->userId, $from, $to) as $t) {
            $key = substr($t['due_date'], 0, 10);
            if (isset($days[$key])) {
                $days[$key]['tasks'][] = [
                    'goal_id' => $t['goal_id'], 'title' => $t['title'],
                    'status' => $t['status'], 'time' => substr($t['due_date'], 11, 5),
                ];
            }
        }

        $names = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];
        $heads = [];
        for ($i = 0; $i < 7; $i++) {
            $heads[] = $names[(($weekStart - 1 + $i) % 7) + 1];
        }

        view('schedule/index', [
            'title' => 'Lịch học tập',
            'monthLabel' => 'Tháng ' . $first->format('m/Y'),
            'prev' => $first->modify('-1 month')->format('Y-m'),
            'next' => $first->modify('+1 month')->format('Y-m'),
            'heads' => $heads,
            'weeks' => array_chunk(array_values($days), 7),
        ]);
    }

    #thêm sự kiện vào lịch trình từ user
    public function create(): void
    {
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        $date = is_valid_date($date) ? $date : date('Y-m-d');

        $this->renderForm('Thêm sự kiện', url('/schedule'), [
            'title' => '', 'description' => '', 'task_id' => '',
            'start_at' => $date . 'T08:00', 'end_at' => $date . 'T09:00',
            'repeat_type' => 'NONE', 'repeat_until' => '', 'status' => 'PLANNED',
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf();
        [$form, $errors, $ids] = $this->collectInput();

        if ($errors) {
            http_response_code(422);
            $this->renderForm('Thêm sự kiện', url('/schedule'), $form, $errors);
            return;
        }
        $this->schedule->create($this->userId, $this->toDb($form, $ids));
        SessionHelper::flash('success', 'Đã thêm sự kiện vào lịch.');
        redirect('/schedule?month=' . substr($form['start_at'], 0, 7));
    }

    #chỉnh sửa sự kiện trong lịch trình
    public function edit(string $id): void
    {
        $e = $this->schedule->findOwned((int) $id, $this->userId) ?? $this->notFound();
        $this->renderForm('Sửa sự kiện', url("/schedule/{$e['id']}/update"), [
            'title'        => $e['title'],
            'description'  => (string) $e['description'],
            'task_id'      => $e['task_id'] === null ? '' : (string) $e['task_id'],
            'start_at'     => str_replace(' ', 'T', substr($e['start_at'], 0, 16)),
            'end_at'       => str_replace(' ', 'T', substr($e['end_at'], 0, 16)),
            'repeat_type'  => $e['repeat_type'],
            'repeat_until' => (string) $e['repeat_until'],
            'status'       => $e['status'],
        ]);
    }

    public function update(string $id): void {
        $e = $this->schedule->findOwned((int) $id, $this->userId) ?? $this->notFound();
        $this->checkCsrf();
        [$form, $errors, $ids] = $this->collectInput();

        if ($errors) {
            http_response_code(422);
            $this->renderForm('Sửa sự kiện', url("/schedule/{$e['id']}/update"), $form, $errors);
            return;
        }
        $this->schedule->update((int) $e['id'], $this->userId, $this->toDb($form, $ids));
        SessionHelper::flash('success', 'Đã cập nhật sự kiện.');
        redirect('/schedule?month=' . substr($form['start_at'], 0, 7));
    }

    #xóa sự kiện khỏi lịch trình
    public function destroy(string $id): void {
        $e = $this->schedule->findOwned((int) $id, $this->userId) ?? $this->notFound();
        $this->checkCsrf();
        $this->schedule->delete((int) $e['id'], $this->userId);
        SessionHelper::flash('success', 'Đã xóa sự kiện.');
        redirect('/schedule?month=' . substr($e['start_at'], 0, 7));
    }

    private function placeEvents(array &$days, array $events, DateTimeImmutable $gridEnd): void {
        $limit = $gridEnd->setTime(23, 59, 59);
        $steps = ['DAILY' => 'day', 'WEEKLY' => 'week', 'MONTHLY' => 'month'];

        foreach ($events as $e) {
            $start = new DateTimeImmutable($e['start_at']);
            $until = $e['repeat_until'] ? new DateTimeImmutable($e['repeat_until'] . ' 23:59:59') : null;
            $n = 0;

            do {
                $occ = ($e['repeat_type'] === 'NONE' || $n === 0)
                    ? $start
                    : $start->modify("+{$n} " . $steps[$e['repeat_type']]);
                if ($occ > $limit || ($until && $occ > $until)) {
                    break;
                }
                $key = $occ->format('Y-m-d');
                if (isset($days[$key])) {
                    $days[$key]['events'][] = [
                        'id' => $e['id'], 'title' => $e['title'], 'time' => $occ->format('H:i'),
                        'status' => $e['status'], 'repeat' => $e['repeat_type'],
                    ];
                }
                $n++;
            } while ($e['repeat_type'] !== 'NONE' && $n < 5000);
        }
        foreach ($days as &$day) {
            usort($day['events'], fn ($a, $b) => strcmp($a['time'], $b['time']));
        }
    }

    private function renderForm(string $title, string $action, array $event, array $errors = []): void {
        view('schedule/form', [
            'title' => $title,
            'action' => $action,
            'event' => $event,
            'errors' => $errors,
            'taskOptions' => (new StudyModel())->activeTasks($this->userId),
        ]);
    }

    private function notFound(): never {
        http_response_code(404);
        view('errors/404', ['title' => 'Không tìm thấy sự kiện']);
        exit;
    }

    private function checkCsrf(): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect('/schedule');
        }
    }

    private function isValidLocal(string $v): bool {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $v);
        return $d !== false && $d->format('Y-m-d\TH:i') === $v;
    }

    private function collectInput(): array {
        $form = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'task_id' => trim((string) ($_POST['task_id'] ?? '')),
            'start_at' => trim((string) ($_POST['start_at'] ?? '')),
            'end_at' => trim((string) ($_POST['end_at'] ?? '')),
            'repeat_type' => (string) ($_POST['repeat_type'] ?? 'NONE'),
            'repeat_until' => trim((string) ($_POST['repeat_until'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'PLANNED'),
        ];
        $errors = [];
        $ids    = ['goal_id' => null, 'task_id' => null];

        if ($form['title'] === '' || mb_strlen($form['title']) > 200) {
            $errors['title'] = 'Tên sự kiện bắt buộc và tối đa 200 ký tự.';
        }
        if (mb_strlen($form['description']) > 1000) {
            $errors['description'] = 'Mô tả tối đa 1000 ký tự.';
        }
        if (!array_key_exists($form['repeat_type'], ReminderModel::REPEAT_LABELS)) {
            $errors['repeat_type'] = 'Kiểu lặp không hợp lệ.';
        }
        if (!array_key_exists($form['status'], ScheduleModel::STATUS_LABELS)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }

        $startOk = $this->isValidLocal($form['start_at']);
        $endOk   = $this->isValidLocal($form['end_at']);
        if (!$startOk) {
            $errors['start_at'] = 'Thời điểm bắt đầu không hợp lệ.';
        }
        if (!$endOk) {
            $errors['end_at'] = 'Thời điểm kết thúc không hợp lệ.';
        }
        if ($startOk && $endOk && $form['end_at'] <= $form['start_at']) {
            $errors['end_at'] = 'Thời điểm kết thúc phải sau thời điểm bắt đầu.';
        }

        if ($form['repeat_until'] !== '') {
            if (!is_valid_date($form['repeat_until'])) {
                $errors['repeat_until'] = 'Ngày kết thúc lặp không hợp lệ.';
            } elseif ($startOk && $form['repeat_until'] < substr($form['start_at'], 0, 10)) {
                $errors['repeat_until'] = 'Ngày kết thúc lặp phải từ ngày bắt đầu trở đi.';
            }
        }

        if ($form['task_id'] !== '') {
            $task = ctype_digit($form['task_id']) ? $this->tasks->findOwned((int) $form['task_id'], $this->userId) : null;
            if (!$task) {
                $errors['task_id'] = 'Nhiệm vụ không hợp lệ.';
            } else {
                $ids = ['goal_id' => (int) $task['goal_id'], 'task_id' => (int) $task['id']];
            }
        }

        return [$form, $errors, $ids];
    }

    private function toDb(array $f, array $ids): array {
        return [
            'goal_id' => $ids['goal_id'],
            'task_id' => $ids['task_id'],
            'title' => $f['title'],
            'description' => $f['description'] === '' ? null : $f['description'],
            'start_at' => str_replace('T', ' ', $f['start_at']) . ':00',
            'end_at' => str_replace('T', ' ', $f['end_at']) . ':00',
            'repeat_type' => $f['repeat_type'],
            'repeat_until' => ($f['repeat_type'] === 'NONE' || $f['repeat_until'] === '') ? null : $f['repeat_until'],
            'status' => $f['status'],
        ];
    }
}