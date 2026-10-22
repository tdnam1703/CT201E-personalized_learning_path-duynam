<?php
declare(strict_types=1);

class SuggestionModel {
    public const TYPE_LABELS = [
        'EXTEND_DEADLINE' => 'Gia hạn mục tiêu',
        'RESCHEDULE' => 'Dời hạn nhiệm vụ',
        'SPLIT_TASK' => 'Tách nhiệm vụ',
        'REDUCE_LOAD' => 'Giảm tải',
        'OTHER' => 'Khác',
    ];

    #lấy danh sách gợi ý học tập đang chờ/đã được xử lý
    public function listPending(int $userId): array {
        return $this->list($userId, "s.status = 'PENDING'", 'ORDER BY s.created_at DESC, s.id DESC');
    }

    public function listHistory(int $userId, int $limit): array {
        return $this->list($userId, "s.status <> 'PENDING'", 'ORDER BY s.resolved_at DESC, s.id DESC LIMIT ' . (int) $limit);
    }

    private function list(int $userId, string $where, string $tail): array {
        $stmt = db()->prepare(
            "SELECT s.*, g.title AS goal_title, t.title AS task_title
             FROM roadmap_suggestions s
             JOIN goals g ON g.id = s.goal_id
             LEFT JOIN tasks t ON t.id = s.task_id
             WHERE s.user_id = :uid AND {$where} {$tail}"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    #áp dụng gợi ý
    public function accept(int $id, int $userId): ?string {
        $pdo  = db();
        $stmt = $pdo->prepare(
            "SELECT * FROM roadmap_suggestions WHERE id = :id AND user_id = :uid AND status = 'PENDING'"
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $s = $stmt->fetch();
        if (!$s) {
            return 'Gợi ý không còn hiệu lực.';
        }

        $pdo->beginTransaction();
        try {
            $claim = $pdo->prepare(
                "UPDATE roadmap_suggestions SET status = 'ACCEPTED', resolved_at = NOW()
                 WHERE id = :id AND status = 'PENDING'"
            );
            $claim->execute(['id' => $id]);
            if ($claim->rowCount() !== 1) {
                $pdo->rollBack();
                return 'Gợi ý đã được xử lý.';
            }

            if ($s['suggestion_type'] === 'EXTEND_DEADLINE' && $s['suggested_date']) {
                $pdo->prepare('UPDATE goals SET target_date = :d WHERE id = :gid AND user_id = :uid')
                    ->execute(['d' => $s['suggested_date'], 'gid' => $s['goal_id'], 'uid' => $userId]);

            } elseif ($s['suggestion_type'] === 'RESCHEDULE' && $s['suggested_date'] && $s['task_id']) {
                $g = $pdo->prepare('SELECT target_date FROM goals WHERE id = :gid AND user_id = :uid');
                $g->execute(['gid' => $s['goal_id'], 'uid' => $userId]);
                $target = $g->fetchColumn();
                if ($target && $s['suggested_date'] > $target) {
                    $pdo->rollBack();
                    return 'Hạn của mục tiêu đã thay đổi, ngày gợi ý không còn phù hợp. Hãy sửa nhiệm vụ thủ công.';
                }
                //giữ nguyên giờ trong ngày, chỉ đổi ngày
                $pdo->prepare(
                    "UPDATE tasks SET due_date = CONCAT(:d, ' ', TIME(due_date))
                     WHERE id = :tid AND goal_id = :gid AND due_date IS NOT NULL"
                )->execute(['d' => $s['suggested_date'], 'tid' => $s['task_id'], 'gid' => $s['goal_id']]);
            }

            $pdo->commit();
            return null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function dismiss(int $id, int $userId): bool {
        $stmt = db()->prepare(
            "UPDATE roadmap_suggestions SET status = 'DISMISSED', resolved_at = NOW()
             WHERE id = :id AND user_id = :uid AND status = 'PENDING'"
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    #sinh gợi ý
    public function generate(int $userId): void
    {
        $this->cleanup($userId);
        $this->ruleOverdueTasks($userId);
        $this->ruleGoalsAtRisk($userId);
        $this->ruleBigTasks($userId);
        $this->ruleTooManyDue($userId);
    }

    #tự đóng các gợi ý chờ xử lý
    private function cleanup(int $userId): void {
        $pdo = db();
        //nhiệm vụ đã xong/hủy
        $pdo->prepare(
            "UPDATE roadmap_suggestions s JOIN tasks t ON t.id = s.task_id
             SET s.status = 'DISMISSED', s.resolved_at = NOW()
             WHERE s.user_id = :uid AND s.status = 'PENDING' AND t.status IN ('DONE', 'CANCELLED')"
        )->execute(['uid' => $userId]);
        //gợi ý dời hạn nhưng nhiệm vụ không còn trễ
        $pdo->prepare(
            "UPDATE roadmap_suggestions s JOIN tasks t ON t.id = s.task_id
             SET s.status = 'DISMISSED', s.resolved_at = NOW()
             WHERE s.user_id = :uid AND s.status = 'PENDING' AND s.suggestion_type = 'RESCHEDULE'
               AND (t.due_date IS NULL OR t.due_date >= NOW())"
        )->execute(['uid' => $userId]);
        //mục tiêu đã hoàn thành/hủy
        $pdo->prepare(
            'UPDATE roadmap_suggestions s JOIN goals g ON g.id = s.goal_id
             SET s.status = \'DISMISSED\', s.resolved_at = NOW()
             WHERE s.user_id = :uid AND s.status = \'PENDING\' AND g.status IN (:s1, :s2)'
        )->execute(['uid' => $userId, 's1' => GOAL_COMPLETED, 's2' => GOAL_CANCELLED]);
    }

    #rule 1: nhiệm vụ đã trễ hạn -> gợi ý dời hạn
    private function ruleOverdueTasks(int $userId): void {
        $stmt = db()->prepare(
            "SELECT t.id, t.goal_id, t.title, g.title AS goal_title, g.target_date
             FROM tasks t JOIN goals g ON g.id = t.goal_id
             WHERE g.user_id = :uid AND g.status IN (:s1, :s2)
               AND t.status IN ('TODO', 'IN_PROGRESS') AND t.due_date < NOW()"
        );
        $stmt->execute(['uid' => $userId, 's1' => GOAL_NOT_STARTED, 's2' => GOAL_IN_PROGRESS]);

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        foreach ($stmt->fetchAll() as $t) {
            $date = date('Y-m-d', strtotime('+2 day'));
            if ($t['target_date'] && $t['target_date'] < $date) {
                $date = $t['target_date'];
            }
            if ($date < $tomorrow) {
                continue;
            }
            $this->add($userId, (int) $t['goal_id'], (int) $t['id'], 'RESCHEDULE',
                sprintf('Nhiệm vụ "%s" đã trễ hạn. Gợi ý dời hạn sang %s để theo kịp mục tiêu "%s".',
                    $t['title'], date('d/m/Y', strtotime($date)), $t['goal_title']),
                $date);
        }
    }

    #rule 2: mục tiêu quá hạn, hoặc sắp hết hạn (<= 7 ngày) mà tiến độ < 50% -> gợi ý gia hạn 
    private function ruleGoalsAtRisk(int $userId): void {
        $stmt = db()->prepare(
            'SELECT goal_id, title, target_date, progress_percent
             FROM v_goal_progress
             WHERE user_id = :uid AND status IN (:s1, :s2) AND target_date IS NOT NULL
               AND total_tasks > 0 AND done_tasks < total_tasks
               AND (target_date < CURDATE()
                    OR (target_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND progress_percent < 50))'
        );
        $stmt->execute(['uid' => $userId, 's1' => GOAL_NOT_STARTED, 's2' => GOAL_IN_PROGRESS]);

        $today = date('Y-m-d');
        foreach ($stmt->fetchAll() as $g) {
            $base = max($g['target_date'], $today);
            $date = date('Y-m-d', strtotime($base . ' +7 day'));
            $state = $g['target_date'] < $today ? 'đã quá hạn' : 'sắp đến hạn';
            $this->add($userId, (int) $g['goal_id'], null, 'EXTEND_DEADLINE',
                sprintf('Mục tiêu "%s" %s (%s) nhưng mới hoàn thành %d%%. Gợi ý gia hạn đến %s.',
                    $g['title'], $state, date('d/m/Y', strtotime($g['target_date'])),
                    (int) $g['progress_percent'], date('d/m/Y', strtotime($date))),
                $date);
        }
    }

    #rule 3: nhiệm vụ dự kiến từ 4 giờ trở lên -> gợi ý tách nhỏ
    private function ruleBigTasks(int $userId): void {
        $stmt = db()->prepare(
            "SELECT t.id, t.goal_id, t.title, t.estimated_minutes
             FROM tasks t JOIN goals g ON g.id = t.goal_id
             WHERE g.user_id = :uid AND g.status IN (:s1, :s2)
               AND t.status IN ('TODO', 'IN_PROGRESS') AND t.estimated_minutes >= 240"
        );
        $stmt->execute(['uid' => $userId, 's1' => GOAL_NOT_STARTED, 's2' => GOAL_IN_PROGRESS]);

        foreach ($stmt->fetchAll() as $t) {
            $this->add($userId, (int) $t['goal_id'], (int) $t['id'], 'SPLIT_TASK',
                sprintf('Nhiệm vụ "%s" dự kiến %s giờ. Nên tách thành các nhiệm vụ nhỏ (khoảng 1–2 giờ) để dễ theo dõi và dễ hoàn thành.',
                    $t['title'], rtrim(rtrim(number_format($t['estimated_minutes'] / 60, 1), '0'), '.')),
                null);
        }
    }

    #rule 4: một mục tiêu có từ 4 nhiệm vụ trở lên đến hạn trong 3 ngày tới -> gợi ý giảm tải
    private function ruleTooManyDue(int $userId): void {
        $stmt = db()->prepare(
            "SELECT t.goal_id, g.title, COUNT(*) AS n
             FROM tasks t JOIN goals g ON g.id = t.goal_id
             WHERE g.user_id = :uid AND g.status IN (:s1, :s2)
               AND t.status IN ('TODO', 'IN_PROGRESS')
               AND t.due_date >= NOW() AND t.due_date < DATE_ADD(NOW(), INTERVAL 3 DAY)
             GROUP BY t.goal_id, g.title
             HAVING COUNT(*) >= 4"
        );
        $stmt->execute(['uid' => $userId, 's1' => GOAL_NOT_STARTED, 's2' => GOAL_IN_PROGRESS]);

        foreach ($stmt->fetchAll() as $g) {
            $this->add($userId, (int) $g['goal_id'], null, 'REDUCE_LOAD',
                sprintf('Có %d nhiệm vụ của mục tiêu "%s" đến hạn trong 3 ngày tới. Hãy ưu tiên việc quan trọng và cân nhắc dời bớt việc chưa gấp.',
                    (int) $g['n'], $g['title']),
                null);
        }
    }

    private function add(int $userId, int $goalId, ?int $taskId, string $type, string $content, ?string $date): void {
        $pdo   = db();
        $check = $pdo->prepare(
            "SELECT 1 FROM roadmap_suggestions
             WHERE user_id = :uid AND goal_id = :gid AND suggestion_type = :type AND task_id <=> :tid
               AND (status = 'PENDING' OR created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY))
             LIMIT 1"
        );
        $check->execute(['uid' => $userId, 'gid' => $goalId, 'type' => $type, 'tid' => $taskId]);
        if ($check->fetchColumn()) {
            return;
        }

        $content = mb_substr($content, 0, 1000);
        $pdo->prepare(
            'INSERT INTO roadmap_suggestions (user_id, goal_id, task_id, suggestion_type, content, suggested_date)
             VALUES (:uid, :gid, :tid, :type, :content, :date)'
        )->execute(['uid' => $userId, 'gid' => $goalId, 'tid' => $taskId, 'type' => $type, 'content' => $content, 'date' => $date]);

        $pdo->prepare(
            "INSERT INTO notifications (user_id, goal_id, task_id, type, title, content, channel, sent_at)
             VALUES (:uid, :gid, :tid, 'SUGGESTION', :title, :content, 'APP', NOW())"
        )->execute([
            'uid' => $userId, 'gid' => $goalId, 'tid' => $taskId,
            'title' => 'Gợi ý điều chỉnh lộ trình', 'content' => mb_substr($content, 0, 1000),
        ]);
    }
}