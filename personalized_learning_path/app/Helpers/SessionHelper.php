<?php
declare(strict_types=1);

#gom các thao tác php session vào 1 class
class SessionHelper {
    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        #cấu hình cookie nếu chưa hoạt động
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        #hết hạn nếu không hoạt động quá SESSION_TIMEOUT
        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        //xóa và tạo lại    
        self::destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    #thông báo dùng 1 lần
    public static function flash(string $key, ?string $message = null): ?string {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function userId(): ?int {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function isLoggedIn(): bool {
        return self::userId() !== null;
    }

    public static function destroy(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}