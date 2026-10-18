<?php
declare(strict_types=1);

#tạo kết nối database
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        env('DB_HOST', '127.0.0.1'),
        env('DB_PORT', '3306'),
        env('DB_NAME', ''),
        env('DB_CHARSET', 'utf8mb4')
    );

    $options = [
        //sql lỗi thì ném exception
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        //trả về mảng kết hợp tên cột -> giá trị
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        //tắt prepared statement
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, (string) env('DB_USER', 'root'), (string) env('DB_PASS', ''), $options);
        $pdo->exec("SET time_zone = '+07:00'");
    } catch (PDOException $e) {
        error_log('[DB] ' . $e->getMessage());
        http_response_code(500);
        exit(APP_DEBUG
            ? 'Không kết nối được CSDL: ' . htmlspecialchars($e->getMessage())
            : 'Lỗi hệ thống. Vui lòng thử lại sau.');
    }

    return $pdo;
}