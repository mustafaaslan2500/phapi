<?php
namespace App\Exception;

use Exception;

class HttpException extends Exception
{
    private int $status;

    public function __construct(int $status, string $message = '')
    {
        parent::__construct($message, 0);
        $this->status = $status;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }
}
