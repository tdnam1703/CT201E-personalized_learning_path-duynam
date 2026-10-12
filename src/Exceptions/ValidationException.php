<?php
declare(strict_types=1);

namespace src\Exceptions;

final class ValidationException extends HttpException
{
    public function __contruct (
        #một trường trong mảng có thể chứa nhiều lỗi
        array $error,
        string $message = 'Dữ liệu không hợp lệ.'
    ) {
        parent::__construct (
            422,
            $message,
            'VALIDATION_ERROR',
            $error
        );
    }
}