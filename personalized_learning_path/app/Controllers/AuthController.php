<?php
declare(strict_types=1);

class AuthController {
    private UserModel $users;

    public function __construct() {
        $this->users = new UserModel();
    }

    #đăng ký
    public function showRegister(): void {
        $this->guestOnly();
        view('auth/register', ['title' => 'Đăng ký', 'errors' => [], 'old' => []]);
    }

    public function register(): void {
        $this->guestOnly();
        $this->checkCsrf('/register');

        $name = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) {
            $errors['full_name'] = 'Họ tên bắt buộc và tối đa 150 ký tự.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = 'Email không hợp lệ.';
        } elseif ($this->users->findByEmail($email)) {
            $errors['email'] = 'Email này đã được đăng ký.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Mật khẩu tối thiểu 8 ký tự.';
        }
        if ($password !== $confirm) {
            $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp.';
        }

        if ($errors) {
            http_response_code(422);
            view('auth/register', [
                'title' => 'Đăng ký',
                'errors' => $errors,
                'old' => ['full_name' => $name, 'email' => $email],
            ]);
            return;
        }

        $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
        SessionHelper::flash('success', 'Đăng ký thành công! Hãy đăng nhập.');
        redirect('/login');
    }

    #đăng nhập
    public function showLogin(): void {
        $this->guestOnly();
        view('auth/login', ['title' => 'Đăng nhập', 'errors' => [], 'old' => []]);
    }

    public function login(): void {
        $this->guestOnly();
        $this->checkCsrf('/login');

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $user = $this->users->findByEmail($email);

        //thông báo chung để không lộ email nào đã tồn tại
        if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
            http_response_code(422);
            view('auth/login', [
                'title' => 'Đăng nhập',
                'errors' => ['login' => 'Email hoặc mật khẩu không đúng.'],
                'old' => ['email' => $email],
            ]);
            return;
        }
        if (!(int) $user['is_active']) {
            http_response_code(403);
            view('auth/login', [
                'title' => 'Đăng nhập',
                'errors' => ['login' => 'Tài khoản đã bị vô hiệu hóa.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        session_regenerate_id(true);
        SessionHelper::set('user_id', (int) $user['id']);
        SessionHelper::set('user_name', $user['full_name']);
        SessionHelper::flash('success', 'Chào mừng ' . $user['full_name'] . '!');
        redirect('/');
    }

    #đăng xuất
    public function logout(): void {
        $this->checkCsrf('/');
        SessionHelper::destroy();
        SessionHelper::start();
        SessionHelper::flash('success', 'Bạn đã đăng xuất.');
        redirect('/login');
    }

    private function guestOnly(): void {
        if (SessionHelper::isLoggedIn()) {
            redirect('/');
        }
    }

    private function checkCsrf(string $backTo): void {
        if (!csrf_verify()) {
            SessionHelper::flash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            redirect($backTo);
        }
    }
}