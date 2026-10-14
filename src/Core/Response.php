<?php
declare(strict_types=1);

namespace src\Core;

final class Response
{
    private function __construct(
        private ?array $payload,
        private int $status
    ) {}

    public static function success(
        mixed $data = null,
        int $status = 200,
        array $meta = []
    ): self {
        if ($status < 200 || $status >= 300 || in_array($status, [204, 205], true)) {
            throw new \InvalidArgumentException(
                'Status code phải là 2xx có nội dung; 204 và 205 không được dùng ở đây.'
            );
        }

        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return new self($payload, $status);
    }

    public static function created(mixed $data): self
    {
        return self::success($data, 201);
    }

    public static function noContent(): self
    {
        return new self(null, 204);
    }

    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage
    ): self {
        if ($page < 1) {
            throw new \InvalidArgumentException(
                'Page phải lớn hơn hoặc bằng 1.'
            );
        }

        if ($perPage < 1) {
            throw new \InvalidArgumentException(
                'Per page phải lớn hơn hoặc bằng 1.'
            );
        }

        if ($total < 0) {
            throw new \InvalidArgumentException(
                'Total không được nhỏ hơn 0.'
            );
        }

        return self::success(
            $items,
            200,
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ]
        );
    }

    public static function error(
        int $status,
        string $code,
        string $message,
        array $details = []
    ): self {
        if ($status < 400 || $status >= 600) {
            throw new \InvalidArgumentException(
                'Status code của error response phải nằm trong khoảng 400-599.'
            );
        }

        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return new self(
            [
                'success' => false,
                'error' => $error,
            ],
            $status
        );
    }

    public function send(): void
    {
        if (headers_sent($file, $line)) {
            throw new \RuntimeException(
                "Không thể gửi response vì header đã được gửi tại {$file}:{$line}."
            );
        }

        if ($this->status === 204 || $this->payload === null) {
            http_response_code($this->status);
            return;
        }

        $body = json_encode(
            $this->payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_THROW_ON_ERROR
        );

        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        echo $body;
    }
}