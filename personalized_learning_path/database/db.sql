-- Nhóm quản lý người dùng
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) UNIQUE,
    password_hash VARCHAR(255),
    full_name VARCHAR(150) NOT NULL,
    avatar_url VARCHAR(500),
    date_of_birth DATE,
    university VARCHAR(200),
    major VARCHAR(150),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE social_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('GOOGLE', 'FACEBOOK', 'GITHUB', 'APPLE') NOT NULL,
    provider_user_id VARCHAR(191) NOT NULL,
    provider_email VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (provider, provider_user_id), -- mỗi acc mxh thuộc 1 provider chỉ được liên kết với 1 user
    UNIQUE (user_id, provider), -- mỗi user chỉ được liên kết với 1 acc mxh của 1 provider
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE verification_codes ( -- xác minh account
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    purpose ENUM('REGISTER', 'RESET_PASSWORD') NOT NULL,
    channel ENUM('EMAIL', 'SMS') NOT NULL, -- gửi tbao qua email hoặc sdt
    code_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, -- Số lần nhập mã
    expires_at DATETIME NOT NULL, -- Mã hết hạn
    used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY (user_id, purpose, expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_settings (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    language VARCHAR(10) NOT NULL DEFAULT 'vi',
    timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Ho_Chi_Minh',
    theme ENUM('LIGHT', 'DARK', 'SYSTEM') NOT NULL DEFAULT 'SYSTEM',
    daily_study_goal_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 120, -- mục tiêu học mỗi ngày
    week_start_day TINYINT UNSIGNED NOT NULL DEFAULT 1, -- 1: thứ 2, ..., 7: chủ nhật
    notify_app TINYINT(1) NOT NULL DEFAULT 1,
    notify_email TINYINT(1) NOT NULL DEFAULT 1,
    notify_sms TINYINT(1) NOT NULL DEFAULT 0,
    default_remind_before_min SMALLINT UNSIGNED NOT NULL DEFAULT 60, -- nhắc trước deadline
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Nhóm quản lý học tập
CREATE TABLE goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL DEFAULT 'MEDIUM', -- ưu tiên
    status ENUM('CHƯA BẮT ĐẦU', 'ĐANG THỰC HIỆN', 'ĐÃ HOÀN THÀNH', 'ĐÃ HỦY') NOT NULL DEFAULT 'CHƯA BẮT ĐẦU', -- trạng thái mục tiêu
    start_date DATE,
    target_date DATE,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0, -- % hoàn thành
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY (user_id, status),
    KEY (target_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE goal_phases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    order_no SMALLINT UNSIGNED NOT NULL DEFAULT 1, -- thứ tự các phase
    start_date DATE,
    end_date DATE,
    status ENUM('CHƯA BẮT ĐẦU', 'ĐANG THỰC HIỆN', 'ĐÃ HOÀN THÀNH') NOT NULL DEFAULT 'CHƯA BẮT ĐẦU',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (goal_id, order_no),
    UNIQUE (goal_id, id),
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

CREATE TABLE tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id BIGINT UNSIGNED NOT NULL,
    phase_id BIGINT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL DEFAULT 'MEDIUM',
    status ENUM('TODO', 'IN_PROGRESS', 'DONE', 'CANCELLED') NOT NULL DEFAULT 'TODO',
    start_date DATETIME,
    due_date DATETIME, -- hạn hoàn thành nhiệm vụ
    estimated_minutes INT UNSIGNED, -- dự kiến tốn bao lâu
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY (goal_id, status),
    KEY (status, due_date),
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id, phase_id) REFERENCES goal_phases(goal_id, id) ON DELETE CASCADE
);

CREATE TABLE study_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED,
    started_at DATETIME NOT NULL,
    ended_at DATETIME,
    note VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY (user_id, started_at),
    KEY (task_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

CREATE TABLE notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    goal_id BIGINT UNSIGNED,
    task_id BIGINT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    content MEDIUMTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY (user_id, updated_at),
    KEY (goal_id),
    KEY (task_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

-- Nhóm quản lý tiện ích
CREATE TABLE reminders ( -- lời nhắc
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    goal_id BIGINT UNSIGNED,
    task_id BIGINT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    remind_at DATETIME NOT NULL, -- thời gian nhắc
    repeat_type ENUM('NONE', 'DAILY', 'WEEKLY', 'MONTHLY') NOT NULL DEFAULT 'NONE', -- Lặp lại
    channel ENUM('APP', 'EMAIL', 'SMS') NOT NULL DEFAULT 'APP',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY (is_active, remind_at),
    KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE notifications ( -- thông báo
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    reminder_id BIGINT UNSIGNED,
    goal_id BIGINT UNSIGNED,
    task_id BIGINT UNSIGNED,
    type ENUM('REMINDER', 'OVERDUE', 'SCHEDULE', 'SUGGESTION', 'SYSTEM') NOT NULL,
    title VARCHAR(200) NOT NULL,
    content VARCHAR(1000),
    channel ENUM('APP', 'EMAIL', 'SMS') NOT NULL DEFAULT 'APP', -- gửi qua
    is_read TINYINT(1) NOT NULL DEFAULT 0, -- đã đọc hay chưa
    read_at DATETIME, -- đọc lúc nào
    sent_at DATETIME, -- hệ thống gửi lúc nào
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY (user_id, is_read, created_at),
    KEY (user_id, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE SET NULL,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

CREATE TABLE schedule_events ( -- sự kiện
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    goal_id BIGINT UNSIGNED,
    task_id BIGINT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(1000),
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    repeat_type ENUM('NONE', 'DAILY', 'WEEKLY', 'MONTHLY') NOT NULL DEFAULT 'NONE',
    repeat_until DATE, -- lặp cho đến khi nào
    status ENUM('PLANNED', 'DONE', 'SKIPPED') NOT NULL DEFAULT 'PLANNED', -- trạng thái
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY (user_id, start_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

CREATE TABLE roadmap_suggestions ( -- đề xuất điều chỉnh
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    goal_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED,
    suggestion_type ENUM('EXTEND_DEADLINE', 'RESCHEDULE', 'SPLIT_TASK', 'REDUCE_LOAD', 'OTHER') NOT NULL,
    content VARCHAR(1000) NOT NULL,
    suggested_date DATE, -- ngày đề xuất
    status ENUM('PENDING', 'ACCEPTED', 'DISMISSED') NOT NULL DEFAULT 'PENDING', -- trạng thái chờ xử lý
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,

    KEY (user_id, status),
    KEY (goal_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

CREATE TABLE report_exports ( -- xuất báo cáo
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    report_type ENUM('PROGRESS', 'STUDY_TIME', 'COMPLETION') NOT NULL,
    file_format ENUM('PDF', 'XLSX', 'CSV') NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- VIEW
CREATE OR REPLACE VIEW v_goal_progress AS -- tổng hợp tiến độ từng goal dựa trên các task
SELECT g.id AS goal_id, g.user_id, g.title, g.status, g.target_date, g.progress_percent,
         COUNT(t.id) AS total_tasks,
         COALESCE(SUM(t.status = 'DONE'), 0) AS done_tasks,
         COALESCE(SUM(t.status IN ('TODO', 'IN_PROGRESS') AND t.due_date < NOW()), 0) AS overdue_tasks
FROM goals g
LEFT JOIN tasks t ON t.goal_id = g.id AND t.status <> 'CANCELLED'
GROUP BY g.id;

CREATE OR REPLACE VIEW v_overdue_tasks AS --  các task đã quá hạn
SELECT t.id AS task_id, g.user_id, g.id AS goal_id, t.title, t.due_date, t.status, t.priority,
         TIMESTAMPDIFF(DAY, t.due_date, NOW()) AS days_late
FROM tasks t JOIN goals g ON g.id = t.goal_id
WHERE t.status IN ('TODO', 'IN_PROGRESS') AND t.due_date < NOW() AND g.status <> 'ĐÃ HỦY';

CREATE OR REPLACE VIEW v_upcoming_tasks AS -- các task sắp đến hạn nhưng chưa xong
SELECT t.id AS task_id, g.user_id, g.id AS goal_id, t.title, t.due_date, t.status, t.priority,
         TIMESTAMPDIFF(HOUR, NOW(), t.due_date) AS hours_left
FROM tasks t JOIN goals g ON g.id = t.goal_id
WHERE t.status IN ('TODO', 'IN_PROGRESS') AND t.due_date >= NOW() AND g.status IN ('CHƯA BẮT ĐẦU', 'ĐANG THỰC HIỆN');

CREATE OR REPLACE VIEW v_study_time_daily AS -- tổng hợp thời gian học theo từng ngày
SELECT user_id, DATE(started_at) AS study_date,
            SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)) AS total_minutes,
            COUNT(*) AS session_count -- đếm các phiên học đã kết thúc trong ngày
FROM study_sessions
WHERE ended_at IS NOT NULL
GROUP BY user_id, DATE(started_at);

-- PROCEDURE
DELIMITER $$

CREATE PROCEDURE sp_recalc_goal_progress(IN p_goal_id BIGINT UNSIGNED) -- tính tiến độ cho 1 goal
BEGIN
    UPDATE goals
    SET progress_percent = COALESCE((
        SELECT ROUND(100 * SUM(status = 'DONE') / NULLIF(COUNT(*), 0))
        FROM tasks WHERE goal_id = p_goal_id AND status <> 'CANCELLED'), 0)
    WHERE id = p_goal_id;
END$$

DELIMITER ;

-- TRIGGER
DELIMITER $$

CREATE TRIGGER trg_tasks_bi BEFORE INSERT ON tasks FOR EACH ROW
BEGIN
    SET NEW.progress_percent = LEAST(NEW.progress_percent, 100); -- giới hạn tiến độ nhiệm vụ ở mức 100%
    IF NEW.status = 'DONE' THEN
        SET NEW.progress_percent = 100;
        SET NEW.completed_at = COALESCE(NEW.completed_at, NOW()); -- xong thì giữ nguyên thời điểm complete, chưa thì update tiếp
    ELSE
        SET NEW.completed_at = NULL;
    END IF;
END$$

CREATE TRIGGER trg_tasks_bu BEFORE UPDATE ON tasks FOR EACH ROW
BEGIN
    SET NEW.progress_percent = LEAST(NEW.progress_percent, 100);
    IF NEW.status = 'DONE' THEN
        SET NEW.progress_percent = 100;
        IF OLD.status <> 'DONE' THEN -- Chuyển sang DONE thì lưu thời điểm hoàn thành.
            SET NEW.completed_at = COALESCE(NEW.completed_at, NOW());
        ELSEIF NEW.completed_at IS NULL THEN
            SET NEW.completed_at = NOW();
        END IF;
    ELSEIF OLD.status = 'DONE' THEN -- Bỏ trạng thái DONE thì xóa thời điểm hoàn thành.
        SET NEW.completed_at = NULL;
    END IF;
END$$

CREATE TRIGGER trg_tasks_ai AFTER INSERT ON tasks FOR EACH ROW
BEGIN
    CALL sp_recalc_goal_progress(NEW.goal_id); -- tính toán lại tiến độ sau khi add task mới
END$$

CREATE TRIGGER trg_tasks_au AFTER UPDATE ON tasks FOR EACH ROW -- Nếu 1 task chuyển từ goal này sang gold khác
BEGIN
    CALL sp_recalc_goal_progress(NEW.goal_id); -- update tiến độ goal khi task mới add vào
    IF OLD.goal_id <> NEW.goal_id THEN
        CALL sp_recalc_goal_progress(OLD.goal_id); -- update tiến độ goal cũ khi delete 1 task
    END IF;
END$$

CREATE TRIGGER trg_tasks_ad AFTER DELETE ON tasks FOR EACH ROW -- tính lại tiến độ goal sau khi delete 1 task
BEGIN
    CALL sp_recalc_goal_progress(OLD.goal_id);
END$$

DELIMITER ;