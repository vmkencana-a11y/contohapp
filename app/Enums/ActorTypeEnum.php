<?php

namespace App\Enums;

/**
 * Actor type enum for audit logging.
 * Identifies whether an action was performed by admin or system.
 */
enum ActorTypeEnum: string
{
    case ADMIN = 'admin';
    case SYSTEM = 'system';

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::SYSTEM => 'Sistem',
        };
    }
}
