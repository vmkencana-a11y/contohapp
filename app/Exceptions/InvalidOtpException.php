<?php

namespace App\Exceptions;

use Exception;

class InvalidOtpException extends Exception
{
    protected int $remainingAttempts;

    public function __construct(string $message = 'OTP tidak valid.', int $remainingAttempts = 0)
    {
        $this->remainingAttempts = $remainingAttempts;
        
        if ($remainingAttempts > 0) {
            $message .= " Sisa percobaan: {$remainingAttempts}";
        }
        
        parent::__construct($message);
    }

    public function getRemainingAttempts(): int
    {
        return $this->remainingAttempts;
    }
}
