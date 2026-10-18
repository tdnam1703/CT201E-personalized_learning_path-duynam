<?php
declare(strict_types=1);

class HomeController {
    public function index(): void {
        view('home', ['title' => 'Trang chủ']);
    }
}