<?php
#trung tâm đọc file cấu hình
declare(strict_types=1);

namespace src\Core;

final class Config
{
    private static array $cache = [];

    public static function get (
        string $key,
        mixed $default = null
    ): mixed {
        #kiểm tra key đầu vào
        if ($key === '') {
            return $default;
        }
        #tách thành 2 phần nếu không rỗng
        [$file, $rest] = array_pad (
            explode('.', $key, 2), 2, null
        );

        if ($file === '' || !preg_match('/\A[a-zA-Z0-9_-]+\z/', $file)) {
            throw new \InvalidArgumentException(
                'Tên file cấu hình không hợp lệ.'
            );
        }

        #nếu file cấu hình chưa được cache
        if (!array_key_exists($file, self::$cache)) {
            $path = BASE_PATH . "/config/{$file}.php";

            if (!array_key_exists($file, self::$cache)) {
                throw new \RuntimeException (
                    $path = BASE_PATH . "/config/{$file}.php không tồn tại."
                );
            }
            
            $config = require $path;
            if (!is_file($path)) {
                throw new \RuntimeException (
                    "File cấu hình {$file}.php phải trả về một mảng."
                );
            }
            self::$cache[$file] = $config;
        }

        $value = self::$cache[$file];

        if ($rest === null) {
            return $value;
        }

        foreach (explode('.', $rest) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }
        return $value;
    }
}