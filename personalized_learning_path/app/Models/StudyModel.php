<?php
declare(strict_types=1);

class StudyModel {
    #phiên học đang chạy
    public function running(int $userId): ?array {
        $stmt = db()->prepare(
            'SELECT s.*, t.title AS task_title, g.title AS goal_title
             FROM study_sessions s
             LEFT JOIN tasks t ON t.id = s.task_id
             LEFT JOIN goals g ON g.id = t.goal_id
             WHERE s.user_id = :uid AND s.ended_at IS NULL
             ORDER BY s.started_at DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function start(int $userId, ?int $taskId): int {
        $stmt = db()->prepare(
            'INSERT INTO study_sessions (user_id, task_id, started_at) VALUES (:uid, :tid, NOW())'
        );
        $stmt->execute(['uid' => $userId, 'tid' => $taskId]);
        return (int) db()->lastInsertId();
    }

    #kết thúc phiên đang chạy
    public function stop(int $id, int $userId, ?string $note): bool {
        $stmt = db()->prepare(
            'UPDATE study_sessions SET ended_at = NOW(), note = :note
             WHERE id = :id AND user_id = :uid AND ended_at IS NULL'
        );
        $stmt->execute(['note' => $note, 'id' => $id, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool {
        $stmt = db()->prepare('DELETE FROM study_sessions WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    #nhiệm vụ chưa xong, thuộc mục tiêu chưa hủy: dùng cho ô chọn "học gì" */
    public function activeTasks(int $userId): array {
        $stmt = db()->prepare(
            "SELECT t.id, t.title, g.title AS goal_title
             FROM tasks t
             JOIN goals g ON g.id = t.goal_id
             WHERE g.user_id = :uid AND t.status IN ('TODO', 'IN_PROGRESS') AND g.status <> :cancelled
             ORDER BY g.title, t.due_date IS NULL, t.due_date, t.id"
        );
        $stmt->execute(['uid' => $userId, 'cancelled' => GOAL_CANCELLED]);
        return $stmt->fetchAll();
    }

    #tổng phút học hôm nay (các phiên đã kết thúc) từ view v_study_time_daily
    public function todayMinutes(int $userId): int {
        $stmt = db()->prepare(
            'SELECT total_minutes FROM v_study_time_daily WHERE user_id = :uid AND study_date = CURDATE()'
        );
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    #mục tiêu học mỗi ngày trong user_settings, default: 120 minutes
    public function dailyGoal(int $userId): int {
        $stmt = db()->prepare('SELECT daily_study_goal_minutes FROM user_settings WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        $value = $stmt->fetchColumn();
        return $value === false ? 120 : (int) $value;
    }

    #thời gian học của N ngày gần nhất
    public function lastDays(int $userId, int $days): array {
        $stmt = db()->prepare(
            'SELECT study_date, total_minutes, session_count
             FROM v_study_time_daily
             WHERE user_id = :uid AND study_date >= CURDATE() - INTERVAL ' . (int) ($days - 1) . ' DAY
             ORDER BY study_date DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    #các phiên học đã kết thúc gần đây
    public function recent(int $userId, int $limit): array {
        $stmt = db()->prepare(
            'SELECT s.id, s.started_at, s.ended_at, s.note,
                    TIMESTAMPDIFF(SECOND, s.started_at, s.ended_at) AS seconds,
                    t.title AS task_title, g.title AS goal_title
             FROM study_sessions s
             LEFT JOIN tasks t ON t.id = s.task_id
             LEFT JOIN goals g ON g.id = t.goal_id
             WHERE s.user_id = :uid AND s.ended_at IS NOT NULL
             ORDER BY s.started_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }
}