<?php

namespace App\Exceptions;

use Exception;

class OtpExpiredException extends Exception
{
    public function __construct(string $message = 'OTP sudah kadaluarsa. Silakan minta OTP baru.')
    {
        parent::__construct($message);
    }
}
