<?php
declare(strict_types=1);

class Router {
    private array $routes = [];

    public function get(string $path, string $handler): void {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, string $handler): void {
        $this->routes[] = ['POST', $path, $handler];
    }

    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        if ($path === '/index.php') {
            $path = '/';
        }

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }
            #chuyển route có dạng /users/{id}
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $routePath);
            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$controller, $action] = explode('@', $handler);
                call_user_func_array([new $controller(), $action], $params);
                return;
            }
        }
        http_response_code(404);
        view('errors/404', ['title' => 'Không tìm thấy trang']);
    }
}