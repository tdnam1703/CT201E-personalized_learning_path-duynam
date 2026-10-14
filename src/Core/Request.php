<?php
declare(strict_types=1);

namespace src\Core;

use src\Exceptions\HttpException;

final class Request
{
    public array $attributes = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
    ) {}

    public static function capture(): self
    {
        $method = strtoupper(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        $uri = parse_url(
            $requestUri,
            PHP_URL_PATH
        );

        if (!is_string($uri) || $uri === '') {
            $uri = '/';
        }

        $path = self::normalizePath($uri);

        $body = self::parseBody(
            $method,
            $_SERVER['CONTENT_TYPE'] ?? ''
        );

        return new self(
            $method,
            $path,
            $_GET,
            $body
        );
    }

    private static function normalizePath(string $uri): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        $base = dirname($scriptName);

        if ($base === '.' || $base === '\\') {
            $base = '';
        }

        $base = '/' . trim(
            str_replace('\\', '/', $base),
            '/'
        );

        if (
            $base !== '/'
            && $base !== ''
            && (
                $uri === $base
                || str_starts_with($uri, $base . '/')
            )
        ) {
            $uri = substr(
                $uri,
                strlen($base)
            );
        }

        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    private static function parseBody(
        string $method,
        string $contentType
    ): array {
        if (!in_array(
            $method,
            ['POST', 'PUT', 'PATCH', 'DELETE'],
            true
        )) {
            return [];
        }

        $mediaType = strtolower(
            trim(explode(';', $contentType, 2)[0])
        );

        if ($mediaType === 'multipart/form-data' && $method === 'POST') {
            return $_POST;
        }

        $raw = file_get_contents('php://input');

        if ($raw === false) {
            throw new HttpException(
                400,
                'Không thể đọc request body.',
                'INVALID_BODY'
            );
        }

        if (trim($raw) === '') {
            return [];
        }

        if (preg_match(
            '/\Aapplication\/(?:[a-z0-9!#$&^_.+-]+\+)?json\z/i',
            $mediaType
        ) === 1) {
            try {
                $decoded = json_decode(
                    $raw,
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException) {
                throw new HttpException(
                    400,
                    'Body phải là JSON hợp lệ.',
                    'INVALID_JSON'
                );
            }

            if (!$decoded instanceof \stdClass) {
                throw new HttpException(
                    400,
                    'Body JSON phải là một object.',
                    'INVALID_JSON'
                );
            }

            $body = [];
            foreach ($decoded as $key => $value) {
                $body[$key] = self::normalizeJsonValue($value);
            }

            return $body;
        }

        if ($mediaType === 'application/x-www-form-urlencoded') {
            parse_str($raw, $body);
            return $body;
        }

        throw new HttpException(
            415,
            'Content-Type của request body không được hỗ trợ.',
            'UNSUPPORTED_MEDIA_TYPE'
        );
    }

    private static function normalizeJsonValue(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalizeJsonValue($item);
            }

            return $normalized;
        }

        if (is_array($value)) {
            return array_map(
                self::normalizeJsonValue(...),
                $value
            );
        }

        return $value;
    }

    public function input(
        string $key,
        mixed $default = null
    ): mixed {
        return array_key_exists(
            $key,
            $this->body
        )
            ? $this->body[$key]
            : $default;
    }

    public function filters(array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->query)) {
                continue;
            }

            $value = $this->query[$key];

            if (
                !is_scalar($value)
                || $value === ''
            ) {
                continue;
            }

            $out[$key] = (string) $value;
        }

        return $out;
    }

    public function pagination(
        int $default = 20,
        int $max = 100
    ): array {
        $default = max(1, $default);
        $max = max(1, $max);

        $page = self::positiveInteger(
            $this->query['page'] ?? 1,
            'page'
        );

        $perPage = self::positiveInteger(
            $this->query['per_page'] ?? $default,
            'per_page'
        );

        $page = max(1, $page);

        $perPage = min(
            $max,
            max(1, $perPage)
        );

        return [$page, $perPage];
    }

    private static function positiveInteger(mixed $value, string $name): int
    {
        if (is_int($value)) {
            $number = $value;
        } elseif (
            is_string($value)
            && preg_match('/\A\d+\z/', $value) === 1
        ) {
            $number = filter_var($value, FILTER_VALIDATE_INT);
        } else {
            $number = false;
        }

        if (!is_int($number) || $number < 1) {
            throw new HttpException(
                400,
                "Tham số {$name} phải là số nguyên dương.",
                'INVALID_PAGINATION'
            );
        }

        return $number;
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if (
            $header === ''
            && function_exists('getallheaders')
        ) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (
                        is_string($name)
                        && strcasecmp($name, 'Authorization') === 0
                        && is_string($value)
                    ) {
                        $header = $value;
                        break;
                    }
                }
            }
        }

        if (
            $header === ''
            && function_exists('apache_request_headers')
        ) {
            $headers = apache_request_headers();

            foreach ($headers as $name => $value) {
                if (
                    strcasecmp(
                        $name,
                        'Authorization'
                    ) === 0
                ) {
                    $header = $value;
                    break;
                }
            }
        }

        if (!is_string($header)) {
            return null;
        }

        $header = trim($header);

        if (
            !preg_match(
                '/^Bearer\s+(\S+)$/i',
                $header,
                $matches
            )
        ) {
            return null;
        }

        return $matches[1];
    }

    public function userId(): int
    {
        $userId = $this->attributes['user_id'] ?? null;

        if (
            (!is_int($userId) && !(is_string($userId) && ctype_digit($userId)))
            || (int) $userId < 1
        ) {
            throw new HttpException(
                401,
                'Request chưa được xác thực.',
                'UNAUTHENTICATED'
            );
        }

        return (int) $userId;
    }
}