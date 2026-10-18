<?php
declare(strict_types=1);

// Front Controller - mọi request đi qua file này
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/config/database.php';
require_once APP_PATH . '/Helpers/functions.php';

// Tự động nạp class theo tên file
spl_autoload_register(function (string $class): void {
    foreach (['Core', 'Controllers', 'Models', 'Helpers'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

SessionHelper::start();

$router = new Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);