<?php
declare(strict_types=1);

class ReminderModel {
    public const REPEAT_LABELS = [
        'NONE' => 'Một lần',
        'DAILY' => 'Hằng ngày',
        'WEEKLY' => 'Hằng tuần',
        'MONTHLY' => 'Hằng tháng',
    ];

    public function listByUser(int $userId): array {
        $stmt = db()->prepare(
            'SELECT r.*, t.title AS task_title, g.title AS goal_title
             FROM reminders r
             LEFT JOIN tasks t ON t.id = r.task_id
             LEFT JOIN goals g ON g.id = r.goal_id
             WHERE r.user_id = :uid
             ORDER BY r.is_active DESC, r.remind_at'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findOwned(int $id, int $userId): ?array {
        $stmt = db()->prepare('SELECT * FROM reminders WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, array $d): int {
        $stmt = db()->prepare(
            "INSERT INTO reminders (user_id, goal_id, task_id, title, remind_at, repeat_type, channel)
             VALUES (:uid, :gid, :tid, :title, :remind_at, :repeat, 'APP')"
        );
        $stmt->execute([
            'uid' => $userId,
            'gid' => $d['goal_id'],
            'tid' => $d['task_id'],
            'title' => $d['title'],
            'remind_at' => $d['remind_at'],
            'repeat' => $d['repeat_type'],
        ]);
        return (int) db()->lastInsertId();
    }

    public function setActive(int $id, int $userId, bool $active): bool {
        $stmt = db()->prepare('UPDATE reminders SET is_active = :a WHERE id = :id AND user_id = :uid');
        return $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id, 'uid' => $userId]);
    }

    public function delete(int $id, int $userId): bool {
        $stmt = db()->prepare('DELETE FROM reminders WHERE id = :id AND user_id = :uid');
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }
}