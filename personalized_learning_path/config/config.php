<?php
declare(strict_types=1);

#định nghĩa các hằng số
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', BASE_PATH . '/views');
define('PUBLIC_PATH', BASE_PATH . '/public');

#đọc file .env
function load_env(string $file): void {
    if (!is_file($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        //bỏ các dòng chú thích hoặc viết sai cú pháp
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }

        //tách đôi dòng lấy tên và giá trị
        [$key, $value] = explode('=', $line, 2);

        //làm sạch
        $key = trim($key);
        $value = trim($value, " \t\"'");

        //lưu vào biến môi trường
        $_ENV[$key] = $value;
    }
}

function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $default;
}

load_env(BASE_PATH . '/.env');

#định nghĩa hằng số ứng dụng
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));
define('BASE_URL', rtrim((string) env('APP_URL', ''), '/'));
//1 giờ
define('SESSION_TIMEOUT', 60 * 60);

#múi giờ/hiển thị lỗi
date_default_timezone_set('Asia/Ho_Chi_Minh');

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);