<?php

namespace App\Exceptions;

use App\Enums\UserStatusEnum;
use Exception;

class InvalidStateTransitionException extends Exception
{
    protected UserStatusEnum $fromStatus;
    protected UserStatusEnum $toStatus;

    public function __construct(
        UserStatusEnum $fromStatus,
        UserStatusEnum $toStatus,
        string $message = ''
    ) {
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        
        if (empty($message)) {
            $message = "Transisi status dari '{$fromStatus->value}' ke '{$toStatus->value}' tidak diizinkan.";
        }
        
        parent::__construct($message);
    }

    public function getFromStatus(): UserStatusEnum
    {
        return $this->fromStatus;
    }

    public function getToStatus(): UserStatusEnum
    {
        return $this->toStatus;
    }
}
