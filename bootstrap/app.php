<?php
declare(strict_types=1);

use src\Core\Config;
use src\Core\Env;
use src\Core\Request;
use src\Core\Response;
use src\Core\Router;
use src\Exceptions\HttpException;

#định nghĩa đường dẫn gốc dự án
if (!defined('BASE_PATH')) {
    //lấy thư mục cha hiện tại(bootstrap) xác định gốc dự án
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/bootstrap/autoload.php';

#bật theo dõi mọi mức lỗi php
error_reporting(E_ALL);

#không in trực tiếp lỗi PHP vào response -> client
ini_set(
    'display_errors',
    '0'
);

#chuyển phần lớn warning/error -> ErrorException
set_error_handler(
    static function (
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        if (
            in_array(
                $severity,
                [
                    E_DEPRECATED,
                    E_USER_DEPRECATED,
                ],
                true
            )
        ) {
            return false;
        }

        throw new \ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );
    }
);

try {
    Env::load(BASE_PATH . '/.env');

    #set timezone mặc định cho php
    date_default_timezone_set(
        (string) Config::get(
            'app.timezone',
            'Asia/Ho_Chi_Minh'
        )
    );

    if (function_exists('mb_internal_encoding')) {
        //đặt encoding mặc định thành UTF-8
        mb_internal_encoding('UTF-8');
    }

    $corsOrigin = (string) Config::get(
        'app.cors_origin',
        '*'
    );
    #thông báo cho trình duyệt về origin, method và header cụ thể
    header('Access-Control-Allow-Origin: ' . $corsOrigin);
    header(
        'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS'
    );
    header(
        'Access-Control-Allow-Headers: Content-Type, Authorization'
    );

    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    #tạo object sau khi đọc request http hiện tại
    $request = Request::capture();

    $router = new Router();

    require BASE_PATH . '/routes/api.php';

    $response = $router->dispatch($request);

    $response->send();
} catch (HttpException $e) {
    Response::error(
        $e->getStatus(),
        $e->getErrorCode(),
        $e->getMessage(),
        $e->getDetails()
    )->send();

} catch (\Throwable $e) {
    error_log(sprintf(
        '[%s] %s in %s:%d',
        $e::class,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    $debug = Env::bool(
        'APP_DEBUG',
        false
    );

    if ($debug) {
        #tạo response JSON báo lỗi
        Response::error(
            500,
            'SERVER_ERROR',
            $e->getMessage(),
            [
                'trace' => explode(
                    "\n",
                    $e->getTraceAsString()
                ),
            ]
        )->send();

        return;
    }
    #tạo response JSON báo lỗi
    Response::error(
        500,
        'SERVER_ERROR',
        'Lỗi máy chủ nội bộ.'
    )->send();
}