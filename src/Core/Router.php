<?php

declare(strict_types=1);

namespace src\Core;

use src\Exceptions\HttpException;
use src\Exceptions\NotFoundException;

final class Router
{
    private array $routes = [];
    private array $prefixes = [];
    private array $groupMiddleware = [];

    public function group(
        array $options,
        callable $callback
    ): void {
        $prefix = $options['prefix'] ?? '';

        if (!is_string($prefix)) {
            throw new \InvalidArgumentException(
                'Prefix của route group phải là string.'
            );
        }

        $middleware = $options['middleware'] ?? [];

        if (!is_array($middleware)) {
            throw new \InvalidArgumentException(
                'Middleware của route group phải là array.'
            );
        }

        $this->prefixes[] = $prefix;
        $this->groupMiddleware[] = $middleware;

        try {
            $callback($this);
        } finally {
            array_pop($this->prefixes);
            array_pop($this->groupMiddleware);
        }
    }

    public function get(
        string $path,
        array|\Closure $handler
    ): void {
        $this->add('GET', $path, $handler);
    }

    public function post(
        string $path,
        array|\Closure $handler
    ): void {
        $this->add('POST', $path, $handler);
    }

    public function put(
        string $path,
        array|\Closure $handler
    ): void {
        $this->add('PUT', $path, $handler);
    }

    public function patch(
        string $path,
        array|\Closure $handler
    ): void {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(
        string $path,
        array|\Closure $handler
    ): void {
        $this->add('DELETE', $path, $handler);
    }

    private function add(
        string $method,
        string $path,
        array|\Closure $handler
    ): void {
        if ($path === '' || $path[0] !== '/') {
            throw new \InvalidArgumentException(
                'Route path phải bắt đầu bằng dấu /.'
            );
        }

        if (is_array($handler)) {
            if (
                !array_is_list($handler)
                || count($handler) !== 2
                || !is_string($handler[0])
                || $handler[0] === ''
                || !is_string($handler[1])
                || $handler[1] === ''
            ) {
                throw new \InvalidArgumentException(
                    'Controller handler phải có dạng [ClassName::class, "method"].'
                );
            }
        }

        $fullPath = $this->buildPath($path);

        $regex = $this->compilePath($fullPath);

        $middleware = [];

        foreach ($this->groupMiddleware as $group) {
            foreach ($group as $item) {
                if (!is_string($item) || $item === '') {
                    throw new \InvalidArgumentException(
                        'Middleware phải là tên class hợp lệ.'
                    );
                }

                $middleware[] = $item;
            }
        }

        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function buildPath(string $path): string
    {
        $parts = [
            ...$this->prefixes,
            $path,
        ];

        $parts = array_map(
            static fn (string $part): string => trim($part, '/'),
            $parts
        );

        $parts = array_filter(
            $parts,
            static fn (string $part): bool => $part !== ''
        );

        return '/' . implode('/', $parts);
    }

    private function compilePath(string $path): string
    {
        preg_match_all('/\{([^{}]*)\}/', $path, $placeholders);
        $parameterNames = [];

        foreach ($placeholders[1] as $name) {
            if (
                preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $name) !== 1
                || in_array($name, $parameterNames, true)
            ) {
                throw new \InvalidArgumentException(
                    "Tham số route không hợp lệ hoặc bị trùng: {{$name}}."
                );
            }

            $parameterNames[] = $name;
        }

        $pathWithoutPlaceholders = preg_replace(
            '/\{[^{}]*\}/',
            '',
            $path
        );

        if (
            $pathWithoutPlaceholders === null
            || strpbrk($pathWithoutPlaceholders, '{}') !== false
        ) {
            throw new \InvalidArgumentException(
                'Route path chứa dấu ngoặc tham số không hợp lệ.'
            );
        }

        $escaped = preg_quote(
            $path,
            '#'
        );

        $regex = preg_replace(
            '#\\\\\{(\w+)\\\\\}#',
            '(?P<$1>\d+)',
            $escaped
        );

        if ($regex === null) {
            throw new \RuntimeException(
                'Không thể compile route path.'
            );
        }

        $compiled = '#^' . $regex . '$#';

        if (@preg_match($compiled, '') === false) {
            throw new \InvalidArgumentException(
                'Route path tạo ra biểu thức chính quy không hợp lệ.'
            );
        }

        return $compiled;
    }

    public function dispatch(Request $request): Response
    {
        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (
                preg_match(
                    $route['regex'],
                    $request->path,
                    $matches
                ) !== 1
            ) {
                continue;
            }

            if ($route['method'] !== $request->method) {
                $methodMismatch = true;
                continue;
            }

            $params = $this->extractParams($matches);

            foreach ($route['middleware'] as $middleware) {
                if (!class_exists($middleware)) {
                    throw new \RuntimeException(
                        "Middleware không tồn tại: {$middleware}"
                    );
                }

                $instance = new $middleware();

                if (!is_callable([$instance, 'handle'])) {
                    throw new \RuntimeException(
                        "Middleware {$middleware} phải có phương thức handle() public và gọi được."
                    );
                }

                $instance->handle($request);
            }

            return $this->executeHandler(
                $route['handler'],
                $request,
                $params
            );
        }

        if ($methodMismatch) {
            throw new HttpException(
                405,
                'Phương thức HTTP không được hỗ trợ cho endpoint này.',
                'METHOD_NOT_ALLOWED'
            );
        }

        throw new NotFoundException(
            'Không tìm thấy endpoint.'
        );
    }

    private function extractParams(array $matches): array
    {
        $params = [];

        foreach ($matches as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $params[$key] = (int) $value;
        }

        return $params;
    }

    private function executeHandler(
        array|\Closure $handler,
        Request $request,
        array $params
    ): Response {
        if ($handler instanceof \Closure) {
            $response = $handler(
                $request,
                $params
            );

            if (!$response instanceof Response) {
                throw new \RuntimeException(
                    'Route handler phải trả về instance của Response.'
                );
            }

            return $response;
        }

        [$class, $action] = $handler;

        if (!class_exists($class)) {
            throw new \RuntimeException(
                "Controller không tồn tại: {$class}"
            );
        }

        $controller = new $class();

        if (!is_callable([$controller, $action])) {
            throw new \RuntimeException(
                "Action {$class}::{$action}() không tồn tại hoặc không public."
            );
        }

        $response = $controller->{$action}(
            $request,
            $params
        );

        if (!$response instanceof Response) {
            throw new \RuntimeException(
                "Action {$class}::{$action}() phải trả về Response."
            );
        }

        return $response;
    }
}