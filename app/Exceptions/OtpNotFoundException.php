<?php

namespace App\Exceptions;

use Exception;

class OtpNotFoundException extends Exception
{
    public function __construct(string $message = 'OTP tidak ditemukan. Silakan minta OTP baru.')
    {
        parent::__construct($message);
    }
}
