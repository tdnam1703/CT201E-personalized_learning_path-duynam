<?php
declare(strict_types=1);
#đăng ký cách tự tìm và nạp file class khi cần dùng

if (!defined('BASE_PATH')) {
    throw new RuntimeException(
        'BASE_PATH phải được định nghĩa trước khi nạp autoload.'
    );
}

$composerAutoload = BASE_PATH . DIRECTORY_SEPARATOR
    . 'vendor' . DIRECTORY_SEPARATOR
    . 'autoload.php';

if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

#định nghĩa hàm tự động nạp class
spl_autoload_register(
    static function (string $class): void {
        $prefix = 'src\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr(
            $class,
            strlen($prefix)
        );

        $file = BASE_PATH . DIRECTORY_SEPARATOR
            . 'src' . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
            . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
);