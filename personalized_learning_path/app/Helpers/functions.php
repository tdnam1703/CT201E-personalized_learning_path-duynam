<?php
declare(strict_types=1);

#Escape HTML để chống XSS. Dùng
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

#Tạo URL tuyệt đối
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

#tạo đường dẫn tới tài nguyên tĩnh: CSS, image,...
function asset(string $path): string {
    return url($path);
}

function redirect(string $path): never {
    header('Location: ' . url($path));
    exit;
}

function view(string $name, array $data = []): void {
    extract($data, EXTR_SKIP);
    require VIEW_PATH . '/layouts/header.php';
    require VIEW_PATH . '/' . $name . '.php';
    require VIEW_PATH . '/layouts/footer.php';
}

#token chống CSRF
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
 
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
 
function csrf_verify(): bool {
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf_token'] ?? '', $sent);
}

#bắt buộc đăng nhập, nếu chưa thì chuyển về trang đăng nhập
function require_login(): void {
    if (!SessionHelper::isLoggedIn()) {
        SessionHelper::flash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('/login');
    }
}

#kiểm tra định dạng ngày YYYY-MM-DD
function is_valid_date(string $value): bool {
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}