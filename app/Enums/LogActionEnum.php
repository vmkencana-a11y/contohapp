<?php

namespace App\Enums;

/**
 * Log action enum for user status changes.
 */
enum LogActionEnum: string
{
    case ACTIVATED = 'activated';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
    case REACTIVATED = 'reactivated';

    /**
     * Get display label in Indonesian.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVATED => 'Diaktifkan',
            self::SUSPENDED => 'Ditangguhkan',
            self::BANNED => 'Diblokir',
            self::REACTIVATED => 'Diaktifkan Kembali',
        };
    }
}
