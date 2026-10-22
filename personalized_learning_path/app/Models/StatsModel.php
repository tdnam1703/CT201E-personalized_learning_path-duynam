<?php
declare(strict_types=1);

class StatsModel {
    #số mục tiêu theo trạng thái
    public function goalCounts(int $userId): array {
        $stmt = db()->prepare('SELECT status, COUNT(*) AS n FROM goals WHERE user_id = :uid GROUP BY status');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    #tổng nhiệm vụ và số đã xong
    public function taskTotals(int $userId): array {
        $stmt = db()->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(t.status = 'DONE'), 0) AS done
             FROM tasks t
             JOIN goals g ON g.id = t.goal_id
             WHERE g.user_id = :uid AND t.status <> 'CANCELLED' AND g.status <> :cancelled"
        );
        $stmt->execute(['uid' => $userId, 'cancelled' => GOAL_CANCELLED]);
        $row = $stmt->fetch();
        return ['total' => (int) $row['total'], 'done' => (int) $row['done']];
    }

    public function overdueCount(int $userId): int {
        $stmt = db()->prepare('SELECT COUNT(*) FROM v_overdue_tasks WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    #nhiệm vụ quá hạn (view v_overdue_tasks)
    public function overdue(int $userId, int $limit): array {
        $stmt = db()->prepare(
            'SELECT o.*, g.title AS goal_title
             FROM v_overdue_tasks o
             JOIN goals g ON g.id = o.goal_id
             WHERE o.user_id = :uid
             ORDER BY o.due_date LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    #nhiệm vụ sắp đến hạn trong N giờ tới (view v_upcoming_tasks)
    public function upcoming(int $userId, int $withinHours, int $limit): array {
        $stmt = db()->prepare(
            'SELECT u.*, g.title AS goal_title
             FROM v_upcoming_tasks u
             JOIN goals g ON g.id = u.goal_id
             WHERE u.user_id = :uid AND u.hours_left <= :hours
             ORDER BY u.due_date LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId, 'hours' => $withinHours]);
        return $stmt->fetchAll();
    }

    #mục tiêu đang theo đuổi (view v_goal_progress)
    public function activeGoals(int $userId, int $limit): array {
        $stmt = db()->prepare(
            'SELECT goal_id, title, status, target_date, progress_percent, total_tasks, done_tasks, overdue_tasks
             FROM v_goal_progress
             WHERE user_id = :uid AND status IN (:s1, :s2)
             ORDER BY target_date IS NULL, target_date LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId, 's1' => GOAL_NOT_STARTED, 's2' => GOAL_IN_PROGRESS]);
        return $stmt->fetchAll();
    }
}