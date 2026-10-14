<?php
declare(strict_types=1);

namespace src\Exceptions; #xác định class ở vị trí nào

final class NotFoundException extends HttpException #kế thừa từ HttpException, cấm kế thừa tiếp tục
{
    public function __construct (
        string $message = 'Không tìm thấy tài nguyên.'
    ) { #gọi constructor của HttpException truyền vào $status, $message, $errorCode
        parent::__construct(404, $message, 'NOT_FOUND');
    }
}