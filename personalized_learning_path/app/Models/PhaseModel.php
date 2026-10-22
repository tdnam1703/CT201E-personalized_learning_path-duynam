<?php
declare(strict_types=1);

class PhaseModel {
    #Các giai đoạn của 1 mục tiêu, tổng số nhiệm vụ/nhiệm vụ hoàn thành
    public function listByGoal(int $goalId): array {
        $stmt = db()->prepare(
            "SELECT p.*, COUNT(t.id) AS total_tasks, COALESCE(SUM(t.status = 'DONE'), 0) AS done_tasks
             FROM goal_phases p
             LEFT JOIN tasks t ON t.phase_id = p.id AND t.status <> 'CANCELLED'
             WHERE p.goal_id = :gid
             GROUP BY p.id
             ORDER BY p.order_no"
        );
        $stmt->execute(['gid' => $goalId]);
        return $stmt->fetchAll();
    }

    #các giai đoạn của 1 mục tiêu thuộc user hiện tại
    public function findOwned(int $id, int $userId): ?array {
        $stmt = db()->prepare(
            'SELECT p.* FROM goal_phases p
             JOIN goals g ON g.id = p.goal_id
             WHERE p.id = :id AND g.user_id = :uid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    #số thứ tự của giai đoạn mới
    public function nextOrderNo(int $goalId): int {
        $stmt = db()->prepare('SELECT COALESCE(MAX(order_no), 0) + 1 FROM goal_phases WHERE goal_id = :gid');
        $stmt->execute(['gid' => $goalId]);
        return (int) $stmt->fetchColumn();
    }

    #tạo giai đoạn
    public function create(int $goalId, array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO goal_phases (goal_id, title, description, order_no, start_date, end_date, status)
             VALUES (:gid, :title, :description, :order_no, :start_date, :end_date, :status)'
        );
        $stmt->execute([
            'gid' => $goalId,
            'title' => $d['title'],
            'description' => $d['description'],
            'order_no' => $d['order_no'],
            'start_date' => $d['start_date'],
            'end_date' => $d['end_date'],
            'status' => $d['status'],
        ]);
        return (int) db()->lastInsertId();
    }

    #cập nhật giai đoạn
    public function update(int $id, array $d): bool {
        $stmt = db()->prepare(
            'UPDATE goal_phases
             SET title = :title, description = :description, order_no = :order_no,
                 start_date = :start_date, end_date = :end_date, status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            'title' => $d['title'],
            'description' => $d['description'],
            'order_no' => $d['order_no'],
            'start_date' => $d['start_date'],
            'end_date' => $d['end_date'],
            'status' => $d['status'],
            'id' => $id,
        ]);
    }

    #xóa giai đoạn
    public function delete(int $id): bool {
        return db()->prepare('DELETE FROM goal_phases WHERE id = :id')->execute(['id' => $id]);
    }
}