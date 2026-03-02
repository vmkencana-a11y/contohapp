<?php

namespace App\Enums;

enum BlacklistTypeEnum: string
{
    case EMAIL = 'email';
    case DOMAIN = 'domain';
    case PHONE = 'phone';
    case IP = 'ip';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::DOMAIN => 'Domain Email',
            self::PHONE => 'No. Handphone',
            self::IP => 'IP Address',
        };
    }

    /**
     * Get badge class for UI.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::EMAIL => 'badge badge-info',
            self::DOMAIN => 'badge badge-warning',
            self::PHONE => 'badge badge-secondary',
            self::IP => 'badge badge-danger',
        };
    }

    /**
     * Get placeholder for input.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::EMAIL => 'contoh@email.com',
            self::DOMAIN => 'yopmail.com',
            self::PHONE => '081234567890',
            self::IP => '192.168.1.1',
        };
    }
}
