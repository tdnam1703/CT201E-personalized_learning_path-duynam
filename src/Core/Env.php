<?php
declare(strict_types=1); #kiểm tra kiểu dữ liệu truyền vào hàm

namespace src\Core;

final class Env #không được kế thừa
{
    private static array $vars = []; #lưu data cấu hình dùng chung cho cả class

    public static function load(string $file): void {
        if (!is_file($file)) {
            return;
        }

        #đọc toàn bộ file thành mảng từng dòng
        $lines = file (
            $file,
            #bỏ ký tự '\n' cuối mỗi dòng và các dòng rỗng
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        #đọc file thất bại nếu exception
        if ($lines === false) {
            throw new \RuntimeException (
                "Không thể đọc file cấu hình: {$file}"
            );
        }

        foreach ($lines as $line) {
            $line = trim($line);

            #xóa Byte Order Mark nếu file có ký tự BOM đầu dòng
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
            #trường hợp dòng rỗng, comment, không có dấu '=' thì bỏ qua
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            #chia dòng theo dấu '=' thành 2 phần
            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }
            #nếu giá trị đặt trong cặp ""/'' thì cắt bỏ
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $value = substr($value, 1, -1);
            }
            #lưu vào mảng $vars theo key
            self::$vars[$key] = $value;
        }
    }

    public static function get (
        string $key,
        mixed $default = null
    ): mixed {
        $value = getenv($key); #lấy biến môi trường từ system

        #nếu không có thì đọc trong mảng $vars
        if ($value === false) {
            $value = self::$vars[$key] ?? null;
        }
        
        if ($value === false || $value === null) {
            return $default;
        }
        #chuyển $value về kiểu boolean
        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}