<?php
declare(strict_types=1);

namespace src\Exceptions; #xác định class ở vị trí nào

class HttpException extends \RuntimeException #kế thừa class RuntimeException của php
{
    public function __construct ( #create a new object
        private int $status,
        string $message,
        private string $errorCode = 'ERROR',
        private array $details = []
    ) { #ngăn truyền mã ngoài phạm vi quy ước
        if ($status < 400 || $status > 599) {
            throw new \InvalidArgumentException(
                'HTTP error status must be between 400 and 599.'
            );
        }
        parent::__construct($message); #khởi tạo thông tin exception của PHP
    }
    
    #lấy mã HTTP
    public function getStatus(): int {
        return $this->status;
    }
    
    #lấy mã lỗi app
    public function getErrorCode(): string {
        return $this->errorCode;
    }

    #lấy dữ liệu chi tiết
    public function getDetails(): array {
        return $this->details;
    }
}