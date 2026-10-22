<?php
declare(strict_types=1);

class GoalModel {
    #danh sách mục tiêu kèm số task từ view v_goal_progress
    public function listByUser(int $userId, ?string $status = null): array {
        $sql = 'SELECT v.goal_id AS id, v.title, v.status, v.target_date, v.progress_percent,
                       v.total_tasks, v.done_tasks, v.overdue_tasks, g.priority, g.start_date
                FROM v_goal_progress v
                JOIN goals g ON g.id = v.goal_id
                WHERE v.user_id = :uid';
        $params = ['uid' => $userId];
        if ($status !== null) {
            $sql .= ' AND v.status = :status';
            $params['status'] = $status;
        }
        $sql .= " ORDER BY (v.status = 'ĐÃ HỦY'), v.target_date IS NULL, v.target_date, v.goal_id DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    #đảm bảo mục tiêu thuộc user hiện tại
    public function findOwned(int $id, int $userId): ?array {
        $stmt = db()->prepare('SELECT * FROM goals WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    #insert vào table goals
    public function create(int $userId, array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO goals (user_id, title, description, priority, status, start_date, target_date, completed_at)
             VALUES (:uid, :title, :description, :priority, :status, :start_date, :target_date, :completed_at)'
        );
        $stmt->execute([
            'uid' => $userId,
            'title' => $d['title'],
            'description' => $d['description'],
            'priority' => $d['priority'],
            'status' => $d['status'],
            'start_date' => $d['start_date'],
            'target_date' => $d['target_date'],
            'completed_at' => $d['status'] === GOAL_COMPLETED ? date('Y-m-d H:i:s') : null,
        ]);
        return (int) db()->lastInsertId();
    }

    #update table goals
    public function update(int $id, int $userId, array $d): bool {
        $stmt = db()->prepare(
            'UPDATE goals
             SET title = :title, description = :description, priority = :priority, status = :status,
                 start_date = :start_date, target_date = :target_date,
                 completed_at = CASE WHEN :is_done = 1 THEN COALESCE(completed_at, NOW()) ELSE NULL END
             WHERE id = :id AND user_id = :uid'
        );
        return $stmt->execute([
            'title'       => $d['title'],
            'description' => $d['description'],
            'priority'    => $d['priority'],
            'status'      => $d['status'],
            'start_date'  => $d['start_date'],
            'target_date' => $d['target_date'],
            'is_done'     => $d['status'] === GOAL_COMPLETED ? 1 : 0,
            'id'          => $id,
            'uid'         => $userId,
        ]);
    }

    #xóa mục tiêu khỏi goals
    public function delete(int $id, int $userId): bool {
        $stmt = db()->prepare('DELETE FROM goals WHERE id = :id AND user_id = :uid');
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }
}