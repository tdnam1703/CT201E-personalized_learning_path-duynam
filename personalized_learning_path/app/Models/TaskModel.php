<?php
declare(strict_types=1);

class TaskModel {
    #danh sách các nhiệm vụ thuộc 1 giai đoạn của 1 mục tiêu
    public function listByGoal(int $goalId): array {
        $stmt = db()->prepare(
            "SELECT t.*, p.title AS phase_title
             FROM tasks t
             LEFT JOIN goal_phases p ON p.id = t.phase_id
             WHERE t.goal_id = :gid
             ORDER BY (t.status IN ('DONE', 'CANCELLED')), t.due_date IS NULL, t.due_date, t.id"
        );
        $stmt->execute(['gid' => $goalId]);
        return $stmt->fetchAll();
    }

    #chi tiết nhiệm vụ thuộc 1 mục tiêu sở hữu bởi 1 user
    public function findOwned(int $id, int $userId): ?array {
        $stmt = db()->prepare(
            'SELECT t.* FROM tasks t
             JOIN goals g ON g.id = t.goal_id
             WHERE t.id = :id AND g.user_id = :uid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    #kiểm tra giai đoạn thuộc đúng mục tiêu
    public function phaseBelongsToGoal(int $phaseId, int $goalId): bool {
        $stmt = db()->prepare('SELECT 1 FROM goal_phases WHERE id = :pid AND goal_id = :gid');
        $stmt->execute(['pid' => $phaseId, 'gid' => $goalId]);
        return (bool) $stmt->fetchColumn();
    }

    #thêm nhiệm vụ mới vào database
    public function create(int $goalId, array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO tasks (goal_id, phase_id, title, description, priority, status, start_date, due_date, estimated_minutes)
             VALUES (:gid, :phase_id, :title, :description, :priority, :status, :start_date, :due_date, :est)'
        );
        $stmt->execute([
            'gid' => $goalId,
            'phase_id' => $d['phase_id'],
            'title' => $d['title'],
            'description' => $d['description'],
            'priority' => $d['priority'],
            'status' => $d['status'],
            'start_date' => $d['start_date'],
            'due_date' => $d['due_date'],
            'est' => $d['estimated_minutes'],
        ]);
        return (int) db()->lastInsertId();
    }

    #cập nhật nhiệm vụ
    public function update(int $id, array $d): bool {
        $stmt = db()->prepare(
            'UPDATE tasks
             SET phase_id = :phase_id, title = :title, description = :description, priority = :priority,
                 status = :status, start_date = :start_date, due_date = :due_date, estimated_minutes = :est
             WHERE id = :id'
        );
        return $stmt->execute([
            'phase_id' => $d['phase_id'],
            'title' => $d['title'],
            'description' => $d['description'],
            'priority' => $d['priority'],
            'status' => $d['status'],
            'start_date' => $d['start_date'],
            'due_date' => $d['due_date'],
            'est' => $d['estimated_minutes'],
            'id' => $id,
        ]);
    }

    #cập nhật trạng thái và % hoàn thành. Trigger tự điền/xóa completed_at khi DONE
    public function setProgress(int $id, string $status, int $progress): bool{
        $stmt = db()->prepare('UPDATE tasks SET status = :status, progress_percent = :progress WHERE id = :id');
        return $stmt->execute(['status' => $status, 'progress' => $progress, 'id' => $id]);
    }

    #xóa nhiệm vụ
    public function delete(int $id): bool {
        return db()->prepare('DELETE FROM tasks WHERE id = :id')->execute(['id' => $id]);
    }

    #đồng bộ trạng thái mục tiêu theo các nhiệm vụ (bỏ qua nhiệm vụ đã hủy)
    public function syncGoalStatus(int $goalId): void
    {
        $pdo = db();
 
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'DONE'), 0) AS done,
                    COALESCE(SUM(status = 'IN_PROGRESS'), 0) AS doing
             FROM tasks WHERE goal_id = :gid AND status <> 'CANCELLED'"
        );
        $stmt->execute(['gid' => $goalId]);
        $c = $stmt->fetch();
 
        $total = (int) $c['total'];
        if ($total === 0) {
            return;
        }
 
        $stmt = $pdo->prepare('SELECT status FROM goals WHERE id = :id');
        $stmt->execute(['id' => $goalId]);
        $current = $stmt->fetchColumn();
        if ($current === false || $current === GOAL_CANCELLED) {
            return;
        }
 
        if ((int) $c['done'] === $total) {
            $new = GOAL_COMPLETED;
        } elseif ((int) $c['done'] > 0 || (int) $c['doing'] > 0) {
            $new = GOAL_IN_PROGRESS;
        } else {
            //tất cả đều TODO: nếu mục tiêu từng "hoàn thành" thì đưa về đang thực hiện
            $new = $current === GOAL_COMPLETED ? GOAL_IN_PROGRESS : $current;
        }
        if ($new === $current) {
            return;
        }
 
        $stmt = $pdo->prepare(
            'UPDATE goals
             SET status = :status,
                 completed_at = CASE WHEN :is_done = 1 THEN COALESCE(completed_at, NOW()) ELSE NULL END
             WHERE id = :id'
        );
        $stmt->execute([
            'status'  => $new,
            'is_done' => $new === GOAL_COMPLETED ? 1 : 0,
            'id'      => $goalId,
        ]);
    }
}