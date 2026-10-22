<?php
declare(strict_types=1);

class DashboardController {
    public function __construct() {
        require_login();
    }

    public function index(): void {
        $userId = (int) SessionHelper::userId();
        $stats = new StatsModel();
        $study = new StudyModel();

        #mục tiêu
        $counts = $stats->goalCounts($userId);
        $cancelled = (int) ($counts[GOAL_CANCELLED] ?? 0);

        //không tính mục tiêu đã hủy
        $totalGoals = array_sum($counts) - $cancelled;
        $doneGoals = (int) ($counts[GOAL_COMPLETED] ?? 0);
        $goalRate = $totalGoals > 0 ? (int) round(100 * $doneGoals / $totalGoals) : 0;

        #nhiệm vụ
        $tasks = $stats->taskTotals($userId);
        $taskRate = $tasks['total'] > 0 ? (int) round(100 * $tasks['done'] / $tasks['total']) : 0;

        #thời gian học
        $byDate = [];
        foreach ($study->lastDays($userId, 7) as $row) {
            $byDate[$row['study_date']] = (int) $row['total_minutes'];
        }
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = date('Y-m-d', strtotime("-{$i} day"));
            $week[] = ['date' => $date, 'minutes' => $byDate[$date] ?? 0];
        }

        view('dashboard/index', [
            'title' => 'Tổng quan',
            'totalGoals' => $totalGoals,
            'doneGoals' => $doneGoals,
            'goalRate' => $goalRate,
            'taskTotal' => $tasks['total'],
            'taskDone' => $tasks['done'],
            'taskRate' => $taskRate,
            'overdueCount' => $stats->overdueCount($userId),
            'overdue' => $stats->overdue($userId, 8),
            'upcoming' => $stats->upcoming($userId, 24 * 7, 8),
            'activeGoals' => $stats->activeGoals($userId, 5),
            'week' => $week,
            'weekMinutes' => array_sum(array_column($week, 'minutes')),
            'todayMinutes' => $study->todayMinutes($userId),
            'dailyGoal' => $study->dailyGoal($userId),
        ]);
    }
}