<?php
declare(strict_types=1);

class ScheduleModel {
    public const STATUS_LABELS = ['PLANNED' => 'Dự kiến', 'DONE' => 'Đã xong', 'SKIPPED' => 'Bỏ qua'];

    #ngày bắt đầu tuần (1 = thứ 2 ... 7 = chủ nhật) trong user_settings
    public function weekStartDay(int $userId): int {
        $stmt = db()->prepare('SELECT week_start_day FROM user_settings WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        $v = (int) $stmt->fetchColumn();
        return ($v >= 1 && $v <= 7) ? $v : 1;
    }

    public function eventsInRange(int $userId, string $from, string $to): array {
        $stmt = db()->prepare(
            "SELECT e.*, t.title AS task_title
             FROM schedule_events e
             LEFT JOIN tasks t ON t.id = e.task_id
             WHERE e.user_id = :uid
               AND (
                    (e.repeat_type = 'NONE' AND e.start_at BETWEEN :f1 AND :t1)
                 OR (e.repeat_type <> 'NONE' AND e.start_at <= :t2
                     AND (e.repeat_until IS NULL OR e.repeat_until >= :f2))
               )
             ORDER BY e.start_at"
        );
        $stmt->execute([
            'uid' => $userId,
            'f1' => $from . ' 00:00:00',
            't1' => $to . ' 23:59:59',
            't2' => $to . ' 23:59:59',
            'f2' => $from,
        ]);
        return $stmt->fetchAll();
    }

    #hạn nhiệm vụ rơi vào khoảng ngày này (để hiển thị trên lịch)
    public function tasksDueInRange(int $userId, string $from, string $to): array {
        $stmt = db()->prepare(
            "SELECT t.id, t.goal_id, t.title, t.status, t.due_date
             FROM tasks t
             JOIN goals g ON g.id = t.goal_id
             WHERE g.user_id = :uid AND t.status <> 'CANCELLED' AND g.status <> :cancelled
               AND t.due_date BETWEEN :f AND :t
             ORDER BY t.due_date"
        );
        $stmt->execute([
            'uid' => $userId, 'cancelled' => GOAL_CANCELLED,
            'f' => $from . ' 00:00:00', 't' => $to . ' 23:59:59',
        ]);
        return $stmt->fetchAll();
    }

    public function findOwned(int $id, int $userId): ?array {
        $stmt = db()->prepare('SELECT * FROM schedule_events WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, array $d): int {
        $stmt = db()->prepare(
            'INSERT INTO schedule_events
                (user_id, goal_id, task_id, title, description, start_at, end_at, repeat_type, repeat_until, status)
             VALUES (:uid, :gid, :tid, :title, :description, :start_at, :end_at, :repeat, :until, :status)'
        );
        $stmt->execute([
            'uid' => $userId,
            'gid' => $d['goal_id'],
            'tid' => $d['task_id'],
            'title' => $d['title'],
            'description' => $d['description'],
            'start_at' => $d['start_at'],
            'end_at' => $d['end_at'],
            'repeat' => $d['repeat_type'],
            'until' => $d['repeat_until'],
            'status' => $d['status'],
        ]);
        return (int) db()->lastInsertId();
    }

    public function update(int $id, int $userId, array $d): bool {
        $stmt = db()->prepare(
            'UPDATE schedule_events
             SET goal_id = :gid, task_id = :tid, title = :title, description = :description,
                 start_at = :start_at, end_at = :end_at, repeat_type = :repeat,
                 repeat_until = :until, status = :status
             WHERE id = :id AND user_id = :uid'
        );
        return $stmt->execute([
            'gid' => $d['goal_id'],
            'tid' => $d['task_id'],
            'title' => $d['title'],
            'description' => $d['description'],
            'start_at' => $d['start_at'],
            'end_at' => $d['end_at'],
            'repeat' => $d['repeat_type'],
            'until' => $d['repeat_until'],
            'status' => $d['status'],
            'id' => $id,
            'uid' => $userId,
        ]);
    }

    public function delete(int $id, int $userId): bool {
        $stmt = db()->prepare('DELETE FROM schedule_events WHERE id = :id AND user_id = :uid');
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }
}