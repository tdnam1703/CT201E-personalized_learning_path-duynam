<?php
declare(strict_types=1);

class PhaseController {
    private GoalModel $goals;
    private PhaseModel $phases;
    private int $userId;

    public function __construct() {
        require_login();
        $this->goals  = new GoalModel();
        $this->phases = new PhaseModel();
        $this->userId = (int) SessionHelper::userId();
    }

    #thêm giai đoạn mới thao tác từ user
    public function create(string $goalId): void {
        $goal = $this->goalOr404((int) $goalId);
        $this->renderForm($goal, 'Thêm giai đoạn', url("/goals/{$goal['id']}/phases"), [
            'title' => '', 'description' => '', 'status' => GOAL_NOT_STARTED,
            'order_no' => $this->phases->nextOrderNo((int) $goal['id']),
            'start_date' => '', 'end_date' => '',
        ]);
    }

    #thêm giai đoạn mới vào database
    public function store(string $goalId): void {
        $goal = $this->goalOr404((int) $goalId);
        $this->checkCsrf($goal);
        [$data, $errors] = $this->collectInput($goal);

        if (!$errors) {
            try {
                $this->phases->create((int) $goal['id'], $data);
                SessionHelper::flash('success', 'Đã thêm giai đoạn.');
                redirect("/goals/{$goal['id']}");
            } catch (PDOException $e) {
                if (!$this->isDuplicateOrder($e)) {
                    throw $e;
                }
                $errors['order_no'] = 'Thứ tự này đã được dùng trong mục tiêu.';
            }
        }
        $this->renderForm($goal, 'Thêm giai đoạn', url("/goals/{$goal['id']}/phases"), $data, $errors);
    }

    #cập nhật giai đoạn từ user
    public function edit(string $id): void {
        $phase = $this->phaseOr404((int) $id);
        $goal  = $this->goalOr404((int) $phase['goal_id']);
        $this->renderForm($goal, 'Sửa giai đoạn', url("/phases/{$phase['id']}/update"), $phase);
    }

    public function update(string $id): void {
        $phase = $this->phaseOr404((int) $id);
        $goal  = $this->goalOr404((int) $phase['goal_id']);
        $this->checkCsrf($goal);
        [$data, $errors] = $this->collectInput($goal);

        if (!$errors) {
            try {
                $this->phases->update((int) $phase['id'], $data);
                SessionHelper::flash('success', 'Đã cập nhật giai đoạn.');
                redirect("/goals/{$goal['id']}");
            } catch (PDOException $e) {
                if (!$this->isDuplicateOrder($e)) {
                    throw $e;
                }
                $errors['order_no'] = 'Thứ tự này đã được dùng trong mục tiêu.';
            }
        }
        $this->renderForm($goal, 'Sửa giai đoạn', url("/phases/{$phase['id']}/update"), $data, $errors);
    }

    #xóa giai đoạn từ user
    public function destroy(string $id): void {
        $phase = $this->phaseOr404((int) $id);
        $goal  = $this->goalOr404((int) $phase['goal_id']);
        $this->checkCsrf($goal);
        $this->phases->delete((int) $phase['id']);
        SessionHelper::flash('success', 'Đã xóa giai đoạn.');
        redirect("/goals/{$goal['id']}");
    }

    private function renderForm(array $goal, string $title, string $action, array $phase, array $errors = []): void {
        view('phases/form', compact('goal', 'title', 'action', 'phase', 'errors'));
    }

    private function goalOr404(int $id): array {
        $goal = $this->goals->findOwned($id, $this->userId);
        if (!$goal) {
            $this->notFound();
        }
        return $goal;
    }

    private function phaseOr404(int $id): array {
        $phase = $this->phases->findOwned($id, $this->userId);
        if (!$phase) {
            $this->notFound();
        }
        return $phase;
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

    #unique( goal_id, order_no)
    private function isDuplicateOrder(PDOException $e): bool {
        return ($e->errorInfo[1] ?? 0) === 1062;
    }

    private function collectInput(array $goal): array {
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'order_no' => (string) ($_POST['order_no'] ?? ''),
            'status' => (string) ($_POST['status'] ?? GOAL_NOT_STARTED),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')),
        ];
        $errors = [];

        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Tên giai đoạn bắt buộc và tối đa 200 ký tự.';
        }
        if (!ctype_digit($data['order_no']) || (int) $data['order_no'] < 1 || (int) $data['order_no'] > 65535) {
            $errors['order_no'] = 'Thứ tự phải là số nguyên từ 1 trở lên.';
        }
        if (!in_array($data['status'], PHASE_STATUSES, true)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        foreach (['start_date', 'end_date'] as $field) {
            if ($data[$field] !== '' && !is_valid_date($data[$field])) {
                $errors[$field] = 'Ngày không hợp lệ.';
            }
        }
        if (!isset($errors['start_date']) && !isset($errors['end_date'])) {
            if ($data['start_date'] !== '' && $data['end_date'] !== '' && $data['end_date'] < $data['start_date']) {
                $errors['end_date'] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
            }
            if ($data['end_date'] !== '' && $goal['target_date'] && $data['end_date'] > $goal['target_date']) {
                $errors['end_date'] = 'Giai đoạn không được kết thúc sau hạn của mục tiêu ('
                    . date('d/m/Y', strtotime($goal['target_date'])) . ').';
            }
        }

        $data['order_no']    = (int) $data['order_no'];
        $data['start_date']  = $data['start_date'] === '' ? null : $data['start_date'];
        $data['end_date']    = $data['end_date'] === '' ? null : $data['end_date'];
        $data['description'] = $data['description'] === '' ? null : $data['description'];

        return [$data, $errors];
    }
}