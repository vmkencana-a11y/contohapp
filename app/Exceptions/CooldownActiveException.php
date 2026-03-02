<?php

namespace App\Exceptions;

use Exception;

class CooldownActiveException extends Exception
{
    protected int $remainingSeconds;

    public function __construct(int $remainingSeconds, string $message = '')
    {
        $this->remainingSeconds = $remainingSeconds;
        
        if (empty($message)) {
            $message = "Silakan tunggu {$remainingSeconds} detik sebelum meminta OTP lagi.";
        }
        
        parent::__construct($message);
    }

    public function getRemainingSeconds(): int
    {
        return $this->remainingSeconds;
    }
}
