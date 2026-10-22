<?php
declare(strict_types=1);

class NotificationModel {
    #đọc/đánh dấu đã đọc
    public function unreadCount(int $userId): int {
        $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function listByUser(int $userId, int $limit): array {
        $stmt = db()->prepare(
            'SELECT * FROM notifications WHERE user_id = :uid
             ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $userId): bool {
        $stmt = db()->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW()
             WHERE id = :id AND user_id = :uid AND is_read = 0'
        );
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function markAllRead(int $userId): bool {
        $stmt = db()->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :uid AND is_read = 0'
        );
        return $stmt->execute(['uid' => $userId]);
    }

    #sinh thông báo
    public function generate(int $userId, bool $force = false): void
    {
        if (!$force && time() - (int) SessionHelper::get('notif_checked_at', 0) < 60) {
            return;
        }
        SessionHelper::set('notif_checked_at', time());

        $this->fireDueReminders($userId);
        $this->warnOverdue($userId);
        $this->warnUpcoming($userId);
        (new SuggestionModel())->generate($userId);
    }

    #lời nhắc do người dùng tạo và đã đến giờ -> tạo thông báo
    private function fireDueReminders(int $userId): void {
        $pdo  = db();
        $stmt = $pdo->prepare(
            'SELECT r.*, t.title AS task_title, g.title AS goal_title
             FROM reminders r
             LEFT JOIN tasks t ON t.id = r.task_id
             LEFT JOIN goals g ON g.id = r.goal_id
             WHERE r.user_id = :uid AND r.is_active = 1 AND r.remind_at <= NOW()'
        );
        $stmt->execute(['uid' => $userId]);

        $claim = $pdo->prepare(
            'UPDATE reminders SET remind_at = :new, is_active = :active
             WHERE id = :id AND is_active = 1 AND remind_at = :old'
        );
        $insert = $pdo->prepare(
            "INSERT INTO notifications (user_id, reminder_id, goal_id, task_id, type, title, content, channel, sent_at)
             VALUES (:uid, :rid, :gid, :tid, 'REMINDER', :title, :content, 'APP', NOW())"
        );
        $steps = ['DAILY' => '+1 day', 'WEEKLY' => '+1 week', 'MONTHLY' => '+1 month'];
        $now   = new DateTime();

        foreach ($stmt->fetchAll() as $r) {
            $new    = $r['remind_at'];
            $active = 0;
            if ($r['repeat_type'] !== 'NONE') {
                $dt = new DateTime($r['remind_at']);
                $i  = 0;
                do {
                    $dt->modify($steps[$r['repeat_type']]);
                } while ($dt <= $now && ++$i < 5000);
                $new    = $dt->format('Y-m-d H:i:s');
                $active = 1;
            }

            #giành quyền xử lý: nếu 2 request cùng lúc thì chỉ 1 request tạo thông báo
            $claim->execute(['new' => $new, 'active' => $active, 'id' => $r['id'], 'old' => $r['remind_at']]);
            if ($claim->rowCount() !== 1) {
                continue;
            }

            $content = null;
            if ($r['task_title']) {
                $content = 'Nhiệm vụ: ' . $r['task_title'] . ($r['goal_title'] ? ' · Mục tiêu: ' . $r['goal_title'] : '');
            } elseif ($r['goal_title']) {
                $content = 'Mục tiêu: ' . $r['goal_title'];
            }
            $insert->execute([
                'uid' => $userId, 'rid' => $r['id'], 'gid' => $r['goal_id'], 'tid' => $r['task_id'],
                'title' => $r['title'], 'content' => $content,
            ]);
        }
    }

    #cảnh báo nhiệm vụ trễ hạn
    private function warnOverdue(int $userId): void {
        $sql = "INSERT INTO notifications (user_id, goal_id, task_id, type, title, content, channel, sent_at)
                SELECT o.user_id, o.goal_id, o.task_id, 'OVERDUE',
                       LEFT(CONCAT('Nhiệm vụ quá hạn: ', o.title), 200),
                       CONCAT('Hạn: ', DATE_FORMAT(o.due_date, '%d/%m/%Y %H:%i'), '. Hãy hoàn thành hoặc điều chỉnh lộ trình.'),
                       'APP', NOW()
                FROM v_overdue_tasks o
                WHERE o.user_id = :uid
                  AND NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.user_id = o.user_id AND n.type = 'OVERDUE'
                        AND n.task_id = o.task_id AND n.created_at >= o.due_date
                  )";
        db()->prepare($sql)->execute(['uid' => $userId]);
    }

    #nhắc nhiệm vụ sắp đến hạn, trước hạn bao nhiêu phút từ user_settings.default_remind_before_min
    private function warnUpcoming(int $userId): void {
        $stmt = db()->prepare('SELECT default_remind_before_min FROM user_settings WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        $mins = (int) ($stmt->fetchColumn() ?: 60);

        $sql = "INSERT INTO notifications (user_id, goal_id, task_id, type, title, content, channel, sent_at)
                SELECT g.user_id, t.goal_id, t.id, 'REMINDER',
                       LEFT(CONCAT('Sắp đến hạn: ', t.title), 200),
                       CONCAT('Hạn: ', DATE_FORMAT(t.due_date, '%d/%m/%Y %H:%i'), ' · Mục tiêu: ', g.title),
                       'APP', NOW()
                FROM tasks t
                JOIN goals g ON g.id = t.goal_id
                WHERE g.user_id = :uid
                  AND t.status IN ('TODO', 'IN_PROGRESS')
                  AND g.status IN (:s1, :s2)
                  AND t.due_date >= NOW()
                  AND t.due_date <= DATE_ADD(NOW(), INTERVAL {$mins} MINUTE)
                  AND NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.user_id = g.user_id AND n.type = 'REMINDER' AND n.reminder_id IS NULL
                        AND n.task_id = t.id AND n.created_at >= DATE_SUB(t.due_date, INTERVAL {$mins} MINUTE)
                  )";
        db()->prepare($sql)->execute(['uid' => $userId, 's1' => GOAL_NOT_STARTED, 's2' => GOAL_IN_PROGRESS]);
    }
}