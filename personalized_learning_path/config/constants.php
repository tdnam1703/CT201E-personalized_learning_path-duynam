<?php
declare(strict_types=1);

#định nghĩa các hằng số thay cho ENUM trong database
const GOAL_NOT_STARTED = 'CHƯA BẮT ĐẦU';
const GOAL_IN_PROGRESS = 'ĐANG THỰC HIỆN';
const GOAL_COMPLETED   = 'ĐÃ HOÀN THÀNH';
const GOAL_CANCELLED   = 'ĐÃ HỦY';

const GOAL_STATUSES  = [GOAL_NOT_STARTED, GOAL_IN_PROGRESS, GOAL_COMPLETED, GOAL_CANCELLED];
const PHASE_STATUSES = [GOAL_NOT_STARTED, GOAL_IN_PROGRESS, GOAL_COMPLETED];

const TASK_TODO        = 'TODO';
const TASK_IN_PROGRESS = 'IN_PROGRESS';
const TASK_DONE        = 'DONE';
const TASK_CANCELLED   = 'CANCELLED';

const TASK_STATUSES = [TASK_TODO, TASK_IN_PROGRESS, TASK_DONE, TASK_CANCELLED];

const PRIORITIES = ['LOW', 'MEDIUM', 'HIGH'];

#nhãn hiển thị trên giao diện
const PRIORITY_LABELS = ['LOW' => 'Thấp', 'MEDIUM' => 'Trung bình', 'HIGH' => 'Cao'];

const TASK_STATUS_LABELS = [
    'TODO' => 'Cần làm',
    'IN_PROGRESS' => 'Đang làm',
    'DONE' => 'Hoàn thành',
    'CANCELLED' => 'Đã hủy',
];