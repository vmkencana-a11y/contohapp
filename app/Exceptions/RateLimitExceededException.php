<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected int $retryAfterSeconds;

    public function __construct(string $message = 'Terlalu banyak permintaan. Silakan coba lagi nanti.', int $retryAfterSeconds = 60)
    {
        $this->retryAfterSeconds = $retryAfterSeconds;
        parent::__construct($message);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
